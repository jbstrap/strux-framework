<?php

declare(strict_types=1);

namespace Strux\Component\Model\Relations;

use InvalidArgumentException;
use Strux\Component\Model\Model;
use Strux\Support\Collection;

class HasMany extends Relation
{
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $related, Model $parent, string $foreignKey, string $localKey)
    {
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        parent::__construct($related, $parent);

        // Ensure the related model is set up correctly
        if (!$related->getTable()) {
            throw new InvalidArgumentException('Related model must have a table defined.');
        }
    }

    public function getResults(): Collection
    {
        return $this->getQuery()
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->get();
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_map(fn($model) => $model->{$this->localKey}, $models);
        $this->getQuery()->whereIn($this->foreignKey, array_unique($keys));
    }

    public function match(array $models, Collection $results, string $relation): array
    {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->foreignKey}][] = $result;
        }

        foreach ($models as $model) {
            $key = $model->{$this->localKey};
            $model->setRelation($relation, new Collection($dictionary[$key] ?? []));
        }
        return $models;
    }
}