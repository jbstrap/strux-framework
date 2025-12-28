<?php

declare(strict_types=1);

namespace Strux\Component\Model;

use PDO;
use PDOException;
use PDOStatement;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Strux\Component\Database\Attributes\Id;
use Strux\Component\Database\Attributes\Table;
use Strux\Component\Exceptions\DatabaseException;
use Strux\Component\Model\Attributes\RelationAttribute;
use Strux\Component\Model\Behavior\HasAttributes;
use Strux\Component\Model\Behavior\HasQueryBuilder;
use Strux\Component\Model\Behavior\HasRelationships;
use Strux\Component\Model\Behavior\HasTimestamps;
use Strux\Support\ContainerBridge;
use Throwable;

abstract class Model
{
    use HasAttributes, HasQueryBuilder, HasRelationships, HasTimestamps;

    protected ?PDO $db = null;
    private ?string $_tableName = null;
    private ?string $_primaryKeyName = null;
    private bool $_exists = false;

    private static array $globalScopes = [];
    private array $removedScopes = [];

    public function __construct(array $attributes = [])
    {
        try {
            $this->db = ContainerBridge::resolve(PDO::class);
        } catch (Throwable $e) {
            error_log("Model Constructor: Failed to resolve PDO: " . $e->getMessage());
        }

        $this->bootTraits();

        $this->fill($attributes);

        $pk = $this->getPrimaryKey();
        if (!empty($attributes) && isset($attributes[$pk]) && $attributes[$pk] !== null) {
            $this->_exists = true;
            $this->_original = $attributes;
        }
        $this->_isQueryBuilderInstance = false;
    }

    protected function bootTraits(): void
    {
        $class = static::class;
        foreach (class_uses_recursive($class) as $trait) {
            $method = 'initialize' . class_basename($trait);
            if (method_exists($this, $method)) {
                $this->{$method}();
            }
        }
    }

    public static function create(array $attributes = []): static
    {
        $instance = new static();
        $instance->fill($attributes);
        $instance->save();
        return $instance;
    }

    public static function fromStorage(array $data): static
    {
        $instance = new static();
        $instance->fill($data);
        $instance->_exists = true;
        $instance->_original = $data;
        $instance->_isQueryBuilderInstance = false;
        return $instance;
    }

    public function applyGlobalScopes(Model $instance): void
    {
        if (isset(static::$globalScopes[static::class])) {
            foreach (static::$globalScopes[static::class] as $scope => $implementation) {
                if (!in_array($scope, $instance->removedScopes)) {
                    $implementation($instance);
                }
            }
        }
    }

    public function __get(string $key)
    {
        if (array_key_exists($key, $this->_relations)) {
            return $this->_relations[$key];
        }

        if (array_key_exists($key, $this->_original)) {
            return $this->_original[$key];
        }

        if (property_exists($this, $key)) {
            $prop = new ReflectionProperty($this, $key);
            if ($prop->isPublic()) {
                $attributes = $prop->getAttributes(RelationAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);
                if (!empty($attributes)) {
                    $relation = $this->initializeRelationFromAttribute($attributes[0]);
                    $result = $relation->getResults();
                    $this->setRelation($key, $result);
                    return $result;
                }
                if ($prop->isInitialized($this)) return $this->{$key};
            }
        }

        throw new RuntimeException("Property '$key' does not exist on " . static::class);
    }

    public function getTable(): string
    {
        if ($this->_tableName !== null) return $this->_tableName;
        $attributes = $this->reflection()->getAttributes(Table::class);
        if (!empty($attributes)) return $this->_tableName = $attributes[0]->newInstance()->name;
        $className = $this->reflection()->getShortName();
        return $this->_tableName = strtolower(preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $className)) . 's';
    }

    public function getPrimaryKey(): string
    {
        if ($this->_primaryKeyName !== null) return $this->_primaryKeyName;
        foreach ($this->reflection()->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!empty($property->getAttributes(Id::class))) {
                return $this->_primaryKeyName = $property->getName();
            }
        }
        return $this->_primaryKeyName = 'id';
    }

    private function reflection(): ReflectionClass
    {
        static $reflectionCache = [];
        $class = static::class;
        if (!isset($reflectionCache[$class])) {
            $reflectionCache[$class] = new ReflectionClass($this);
        }
        return $reflectionCache[$class];
    }

    // --- Persistence Methods ---

    /**
     * @throws DatabaseException
     */
    private function _execute(string $sql, array $bindings = []): PDOStatement
    {
        if ($this->db === null) {
            throw new DatabaseException("PDO connection not available in model " . static::class);
        }
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindings);
            return $stmt;
        } catch (PDOException $e) {
            error_log("DB Exec Error: " . $e->getMessage() . " SQL: $sql");
            throw new DatabaseException("Query failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function save(): bool
    {
        if ($this->_isQueryBuilderInstance) throw new RuntimeException("Cannot call save() on query builder.");

        $attributes = $this->_getPublicPropertiesForDb();
        $this->handleTimestamps($attributes);

        if ($this->_exists) {
            $success = $this->_performUpdate($attributes);
        } else {
            $success = $this->_performInsert($attributes);
        }

        if ($success) {
            $this->_original = $this->_getPublicPropertiesForDb();
        }
        return $success;
    }

    /**
     * @throws DatabaseException
     */
    protected function _performUpdate(array $attributes): bool
    {
        $attributesToSave = array_filter($attributes, fn($v) => is_scalar($v) || is_null($v));
        $dirty = [];

        foreach ($attributesToSave as $key => $value) {
            if ($key !== $this->getPrimaryKey() && (!array_key_exists($key, $this->_original) || $this->_original[$key] !== $value)) {
                $dirty[$key] = $value;
            }
        }

        if (empty($dirty)) return true;

        $pkValue = $this->{$this->getPrimaryKey()} ?? null;
        if ($pkValue === null) throw new RuntimeException("Cannot update without primary key.");

        $setClauses = array_map(fn($col) => "`{$col}` = ?", array_keys($dirty));
        $bindings = array_values($dirty);
        $bindings[] = $pkValue;

        $sql = "UPDATE `{$this->getTable()}` SET " . implode(', ', $setClauses) . " WHERE `{$this->getPrimaryKey()}` = ?";
        $stmt = $this->_execute($sql, $bindings);
        return $stmt->rowCount() >= 0;
    }

    /**
     * @throws DatabaseException
     */
    protected function _performInsert(array $attributes): bool
    {
        $attributesToSave = array_filter($attributes, fn($v) => is_scalar($v) || is_null($v));

        if (array_key_exists($this->getPrimaryKey(), $attributesToSave) && $attributesToSave[$this->getPrimaryKey()] === null) {
            unset($attributesToSave[$this->getPrimaryKey()]);
        }

        if (empty($attributesToSave)) return false;

        $columns = array_keys($attributesToSave);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $bindings = array_values($attributesToSave);

        $sql = "INSERT INTO `{$this->getTable()}` (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";
        $this->_execute($sql, $bindings);

        $id = $this->db->lastInsertId();
        if ($id && property_exists($this, $this->getPrimaryKey())) {
            if (!isset($attributesToSave[$this->getPrimaryKey()]) || $attributesToSave[$this->getPrimaryKey()] === null) {
                $this->{$this->getPrimaryKey()} = is_numeric($id) ? (int)$id : $id;
            }
        }
        $this->_exists = true;
        return true;
    }

    public function delete(): bool
    {
        if ($this->_isQueryBuilderInstance) throw new RuntimeException("Cannot call delete() on query builder.");
        if (!$this->_exists) return false;

        $sql = "DELETE FROM `{$this->getTable()}` WHERE `{$this->getPrimaryKey()}` = ?";
        $stmt = $this->_execute($sql, [$this->{$this->getPrimaryKey()}]);

        if ($stmt->rowCount() > 0) {
            $this->_exists = false;
            return true;
        }
        return false;
    }

    public static function destroy(mixed $ids): int
    {
        $instance = static::query();
        $ids = is_array($ids) ? $ids : [$ids];
        if (empty($ids)) return 0;

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM `{$instance->getTable()}` WHERE `{$instance->getPrimaryKey()}` IN ($placeholders)";
        try {
            $stmt = $instance->_execute($sql, $ids);
        } catch (DatabaseException $e) {
            throw new RuntimeException("An error occurred while destroying records: " . $e->getMessage());
        }
        return $stmt->rowCount();
    }

    public function __sleep(): array
    {
        $properties = (new ReflectionClass($this))->getProperties();
        $propertiesToSerialize = [];
        foreach ($properties as $property) {
            if ($property->getName() !== 'db') {
                $propertiesToSerialize[] = $property->getName();
            }
        }
        return $propertiesToSerialize;
    }

    /**
     * @throws DatabaseException
     */
    public function __wakeup(): void
    {
        if (function_exists('container')) {
            try {
                $this->db = container(PDO::class);
            } catch (Throwable $e) {
                throw new DatabaseException("Failed to re-establish PDO connection on model wakeup: " . $e->getMessage());
            }
        }
    }

    public function getLastInsertId(?string $name = null): string|false
    {
        return $this->db->lastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        if ($this->db->inTransaction()) {
            return true;
        }
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollback(): bool
    {
        return $this->db->rollBack();
    }
}