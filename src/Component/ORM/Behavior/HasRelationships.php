<?php

declare(strict_types=1);

namespace Strux\Component\Model\Behavior;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use Strux\Component\Database\Attributes\Table;
use Strux\Component\Model\Attributes\BelongsTo as BelongsToAttr;
use Strux\Component\Model\Attributes\BelongsToMany as BelongsToManyAttr;
use Strux\Component\Model\Attributes\HasMany as HasManyAttr;
use Strux\Component\Model\Attributes\HasOne as HasOneAttr;
use Strux\Component\Model\Attributes\RelationAttribute;
use Strux\Component\Model\Model;
use Strux\Component\Model\Relations\BelongsTo;
use Strux\Component\Model\Relations\BelongsToMany;
use Strux\Component\Model\Relations\HasMany;
use Strux\Component\Model\Relations\HasOne;
use Strux\Component\Model\Relations\Relation;
use Strux\Support\Collection;
use Strux\Support\Helpers\Utils;

trait HasRelationships
{
    private array $_relations = [];
    private array $_with = [];

    /**
     * Magic method to handle dynamic relationship method calls.
     * Allows $model->someMethod() to return the Relation object if the property $someMethod exists with a RelationAttribute.
     * @throws ReflectionException
     */
    public function __call(string $method, array $arguments)
    {
        if (property_exists($this, $method)) {
            $reflection = new ReflectionClass($this);
            $property = $reflection->getProperty($method);

            $attributes = $property->getAttributes(RelationAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

            if (!empty($attributes)) {
                return $this->initializeRelationFromAttribute($attributes[0]);
            }
        }

        throw new RuntimeException("Relation method '$method' does not exist on " . static::class);
    }

    public function with(string ...$relations): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_with = array_merge($builder->_with, $relations);
        return $builder;
    }

    protected function eagerLoadRelations(array $models, array $relations): void
    {
        $nestedRelations = $this->parseNestedRelations($relations);

        foreach ($nestedRelations as $relationName => $nestedChildren) {
            if (!property_exists($models[0], $relationName)) {
                throw new RuntimeException("Relationship property '$relationName' not found on model " . get_class($models[0]));
            }

            $prop = new ReflectionProperty($models[0], $relationName);
            $attributes = $prop->getAttributes(RelationAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

            if (empty($attributes)) {
                throw new RuntimeException("Property '$relationName' is not a valid relationship.");
            }

            $relationInstance = $this->initializeRelationFromAttribute($attributes[0]);
            $relationInstance->addEagerConstraints($models);
            $relatedModels = $relationInstance->getQuery()->get();

            if (!empty($nestedChildren) && !$relatedModels->isEmpty()) {
                $nextLevelRelations = $this->buildRelationsForNextLevel($nestedChildren);
                $relatedModels->first()->eagerLoadRelations($relatedModels->all(), $nextLevelRelations);
            }

            $this->matchEagerlyLoadedRelations($relationInstance, $models, $relatedModels, $relationName);
        }
    }

    private function initializeRelationFromAttribute(ReflectionAttribute $attribute): Relation
    {
        $attrInstance = $attribute->newInstance();
        return match ($attribute->getName()) {
            HasOneAttr::class => $this->hasOne($attrInstance->related, $attrInstance->foreignKey, $attrInstance->localKey),
            HasManyAttr::class => $this->hasMany($attrInstance->related, $attrInstance->foreignKey, $attrInstance->localKey),
            BelongsToAttr::class => $this->belongsTo($attrInstance->related, $attrInstance->foreignKey, $attrInstance->ownerKey),
            BelongsToManyAttr::class => $this->belongsToMany($attrInstance->related, $attrInstance->pivotTable, $attrInstance->foreignPivotKey, $attrInstance->relatedPivotKey),
            default => throw new RuntimeException("Unknown relationship attribute: " . $attribute->getName()),
        };
    }

    private function parseNestedRelations(array $relations): array
    {
        $parsed = [];
        foreach ($relations as $name) {
            $keys = explode('.', $name);
            $temp = &$parsed;
            foreach ($keys as $key) {
                if (!isset($temp[$key])) $temp[$key] = [];
                $temp = &$temp[$key];
            }
        }
        return $parsed;
    }

    private function buildRelationsForNextLevel(array $nestedTree): array
    {
        $relations = [];
        $buildPath = function (array $tree, string $prefix = '') use (&$buildPath, &$relations) {
            foreach ($tree as $name => $nested) {
                $currentPath = $prefix ? "{$prefix}.{$name}" : $name;
                if (empty($nested)) $relations[] = $currentPath;
                else $buildPath($nested, $currentPath);
            }
        };
        $buildPath($nestedTree);
        return $relations;
    }

    protected function matchEagerlyLoadedRelations(Relation $relation, array &$parents, Collection $results, string $relationName): void
    {
        $relation->match($parents, $results, $relationName);
    }

    public function setRelation(string $relation, mixed $value): void
    {
        $this->_relations[$relation] = $value;
        try {
            $reflection = new ReflectionClass($this);
            if ($reflection->hasProperty($relation)) {
                $property = $reflection->getProperty($relation);
                if ($property->isPublic()) {
                    $property->setValue($this, $value);
                }
            }
        } catch (ReflectionException $e) {
        }
    }

    // --- Definition Methods ---

    protected function hasOne(string $relatedModel, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        /** @var Model $relatedInstance */
        $relatedInstance = new $relatedModel();
        $foreignKey = $foreignKey ?: strtolower((new ReflectionClass($this))->getShortName()) . '_' . $this->getPrimaryKey();
        $localKey = $localKey ?: $this->getPrimaryKey();

        return new HasOne($relatedInstance, $this, $foreignKey, $localKey);
    }

    protected function hasMany(string $relatedModel, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        /** @var Model $relatedInstance */
        $relatedInstance = new $relatedModel();
        $foreignKey = $foreignKey ?: strtolower((new ReflectionClass($this))->getShortName()) . '_' . $this->getPrimaryKey();
        $localKey = $localKey ?: $this->getPrimaryKey();
        return new HasMany($relatedInstance, $this, $foreignKey, $localKey);
    }

    protected function belongsTo(string $relatedModel, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        /** @var Model $relatedInstance */
        $relatedInstance = new $relatedModel();
        $foreignKey = $foreignKey ?: strtolower($relatedInstance->reflection()->getShortName()) . '_' . $relatedInstance->getPrimaryKey();
        $ownerKey = $ownerKey ?: $relatedInstance->getPrimaryKey();
        return new BelongsTo($relatedInstance, $this, $foreignKey, $ownerKey);
    }

    protected function belongsToMany(string $relatedModel, ?string $pivotTable = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null): BelongsToMany
    {
        /** @var Model $relatedInstance */
        $relatedInstance = new $relatedModel();

        // 1. Resolve Pivot Table Name if it's a Class
        if ($pivotTable && class_exists($pivotTable)) {
            $pivotReflection = new ReflectionClass($pivotTable);
            $tableAttr = $pivotReflection->getAttributes(Table::class)[0] ?? null;
            if ($tableAttr) {
                $pivotTable = $tableAttr->newInstance()->name;
            }
        }

        // 2. Default Pivot Table Logic
        if ($pivotTable === null) {
            $models = [
                $this->getTable() ?? Utils::getPluralName($this->reflection()->getShortName()),
                $relatedInstance->getTable() ?? Utils::getPluralName($relatedInstance->reflection()->getShortName())
            ];
            sort($models);
            $pivotTable = implode('_', $models);
        }

        $foreignPivotKey = $foreignPivotKey ?? $this->getPrimaryKey() ?? strtolower($this->reflection()->getShortName()) . '_' . $this->getPrimaryKey();
        $relatedPivotKey = $relatedPivotKey ?? $relatedInstance->getPrimaryKey() ?? strtolower($relatedInstance->reflection()->getShortName()) . '_' . $relatedInstance->getPrimaryKey();

        return new BelongsToMany($relatedInstance, $this, $pivotTable, $foreignPivotKey, $relatedPivotKey, $this->getPrimaryKey(), $relatedInstance->getPrimaryKey());
    }
}