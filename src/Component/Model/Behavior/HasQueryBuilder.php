<?php

declare(strict_types=1);

namespace Strux\Component\Model\Behavior;

use RuntimeException;
use Strux\Component\Database\Expression;
use Strux\Component\Database\Paginator;
use Strux\Component\Exceptions\DatabaseException;
use Strux\Component\Model\Model;
use Strux\Support\Collection;

trait HasQueryBuilder
{
    use HasRelationships;

    private string $_queryAction = 'SELECT';
    private bool $_distinct = false;
    private array $_selects = [];
    private array $_joins = [];
    private array $_wheres = [];
    private array $_groups = [];
    private array $_havings = [];
    private array $_orders = [];
    private ?int $_limit = null;
    private ?int $_offset = null;
    private array $_bindings = [];
    private bool $_isQueryBuilderInstance = false;

    public static function query(): static
    {
        /** @var Model $instance */
        $instance = new static();
        $instance->_resetQueryState();
        $instance->_isQueryBuilderInstance = true;

        if (method_exists($instance, 'applyGlobalScopes')) {
            $instance->applyGlobalScopes($instance);
        }

        return $instance;
    }

    private function _resetQueryState(): void
    {
        $this->_queryAction = 'SELECT';
        $this->_distinct = false;
        $this->_selects = [];
        $this->_joins = [];
        $this->_wheres = [];
        $this->_groups = [];
        $this->_havings = [];
        $this->_orders = [];
        $this->_limit = null;
        $this->_offset = null;
        $this->_bindings = [];
        $this->_with = [];
    }

    private function _getQueryBuilderInstance(): static
    {
        if ($this->_isQueryBuilderInstance) {
            return $this;
        }
        return static::query();
    }

    public static function raw(string $expression): Expression
    {
        return new Expression($expression);
    }

    public function select(array|string $columns = ['*']): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_queryAction = 'SELECT';
        $builder->_selects = is_array($columns) ? $columns : func_get_args();
        return $builder;
    }

    public function selectRaw(string $expression, array $bindings = []): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_selects[] = new Expression($expression);
        if ($bindings) {
            $builder->_bindings = array_merge($builder->_bindings, $bindings);
        }
        return $builder;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null, string $boolean = 'AND'): static
    {
        $builder = $this->_getQueryBuilderInstance();
        if (func_num_args() === 2 && !in_array(strtoupper((string)$operatorOrValue), ['IS NULL', 'IS NOT NULL'])) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue;
        }

        $operator = strtoupper((string)$operator);
        $needsBinding = !in_array($operator, ['IS NULL', 'IS NOT NULL']);

        $builder->_wheres[] = ['column' => $column, 'operator' => $operator, 'boolean' => $boolean, 'needs_binding' => $needsBinding];

        if ($needsBinding) {
            $builder->_bindings[] = $value;
        }
        return $builder;
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean
        ];
        if ($bindings) {
            $builder->_bindings = array_merge($builder->_bindings, $bindings);
        }
        return $builder;
    }

    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        return $this->where($column, $operatorOrValue, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): static
    {
        if (empty($values)) {
            return $this->whereRaw('1 = 0', [], $boolean);
        }
        $builder = $this->_getQueryBuilderInstance();
        $builder->_wheres[] = ['type' => 'in', 'column' => $column, 'boolean' => $boolean, 'count' => count($values)];
        $builder->_bindings = array_merge($builder->_bindings, $values);
        return $builder;
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static
    {
        if (empty($values)) {
            return $this;
        }
        $builder = $this->_getQueryBuilderInstance();
        $builder->_wheres[] = ['type' => 'not_in', 'column' => $column, 'boolean' => $boolean, 'count' => count($values)];
        $builder->_bindings = array_merge($builder->_bindings, $values);
        return $builder;
    }

    public function distinct(): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_distinct = true;
        return $builder;
    }

    protected function _addJoin(string $type, string $table, string $first, ?string $operatorOrSecond = null, ?string $second = null): static
    {
        if ($second === null) {
            $second = $operatorOrSecond;
            $operator = '=';
        } else {
            $operator = $operatorOrSecond;
        }
        $this->_joins[] = compact('type', 'table', 'first', 'operator', 'second');
        return $this;
    }

    public function join(string $table, string $first, mixed $operatorOrSecond, mixed $second = null): static
    {
        $builder = $this->_getQueryBuilderInstance();
        return $builder->_addJoin('INNER', $table, $first, $operatorOrSecond, $second);
    }

    public function leftJoin(string $table, string $first, ?string $operatorOrSecond = null, ?string $second = null): static
    {
        $builder = $this->_getQueryBuilderInstance();
        return $builder->_addJoin('LEFT', $table, $first, $operatorOrSecond, $second);
    }

    public function rightJoin(string $table, string $first, ?string $operatorOrSecond = null, ?string $second = null): static
    {
        $builder = $this->_getQueryBuilderInstance();
        return $builder->_addJoin('RIGHT', $table, $first, $operatorOrSecond, $second);
    }

    public function limit(int $limit): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_limit = $limit;
        return $builder;
    }

    public function offset(int $offset): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_offset = $offset;
        return $builder;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_orders[] = compact('column', 'direction');
        return $builder;
    }

    public function groupBy(string ...$columns): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_groups = array_merge($builder->_groups, $columns);
        return $builder;
    }

    public function having(string $column, string $operator, mixed $value, string $boolean = 'AND'): static
    {
        $builder = $this->_getQueryBuilderInstance();
        $builder->_havings[] = compact('column', 'operator', 'value', 'boolean');
        $builder->_bindings[] = $value;
        return $builder;
    }

    public function get(): Collection
    {
        $builder = $this->_getQueryBuilderInstance();
        $sql = $builder->_buildSelectSQL();
        try {
            $stmt = $builder->_execute($sql, $builder->_bindings);
        } catch (DatabaseException $e) {
            throw new RuntimeException("An error occurred: " . $e);
        }
        $results = $stmt->fetchAll();

        $models = array_map(fn($row) => static::fromStorage($row), $results ?: []);

        if (!empty($models) && !empty($builder->_with)) {
            $this->eagerLoadRelations($models, $builder->_with);
        }

        $builder->_resetQueryState();
        return new Collection($models);
    }

    public function all(): Collection
    {
        return $this->get();
    }

    public static function find(mixed $id, array $with = []): ?static
    {
        $instance = new static();
        $query = static::query()->where($instance->getPrimaryKey(), $id);

        if (!empty($with)) {
            $query->with(...$with);
        }

        return $query->first();
    }

    public static function findOrFail(mixed $id, array $relations = []): static
    {
        $model = static::find($id, $relations);
        if ($model === null) {
            throw new RuntimeException(static::class . " with primary key $id not found.");
        }
        return $model;
    }

    public function first(): ?static
    {
        if (!$this->_isQueryBuilderInstance) {
            throw new RuntimeException("first() must be called on a query builder instance.");
        }

        $this->limit(1);
        $sql = $this->_buildSelectSQL();
        try {
            $stmt = $this->_execute($sql, $this->_bindings);
        } catch (DatabaseException $e) {
            throw new RuntimeException("An error occurred: " . $e);
        }
        $result = $stmt->fetch();

        $relationsToLoad = $this->_with;
        $this->_resetQueryState();

        if (!$result) return null;

        $model = static::fromStorage($result);
        if (!empty($relationsToLoad)) {
            $this->eagerLoadRelations([$model], $relationsToLoad);
        }
        return $model;
    }

    public function latest(?string $column = null): static
    {
        $column = $column ?? $this->getCreatedAtColumn();
        if (!$column) throw new RuntimeException("Cannot use latest() on a model without timestamps enabled.");
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(?string $column = null): static
    {
        $column = $column ?? $this->getCreatedAtColumn();
        if (!$column) throw new RuntimeException("Cannot use oldest() on a model without timestamps enabled.");
        return $this->orderBy($column, 'ASC');
    }

    /**
     * @throws DatabaseException
     */
    protected function _aggregate(string $function, string $column): mixed
    {
        $builder = $this->_getQueryBuilderInstance();
        $aggregateBuilder = static::query();

        $aggregateBuilder->_joins = $builder->_joins;

        $aggregateBuilder->_wheres = $builder->_wheres;
        $aggregateBuilder->_bindings = $builder->_bindings;
        $aggregateBuilder->selectRaw("{$function}({$column}) as aggregate");

        $sql = $aggregateBuilder->_buildSelectSQL();
        $stmt = $aggregateBuilder->_execute($sql, $aggregateBuilder->_bindings);

        $this->_resetQueryState();
        return $stmt->fetchColumn();
    }

    public function count(string $column = '*'): int
    {
        return (int)$this->_aggregate('COUNT', $column);
    }

    public function max(string $column): mixed
    {
        return $this->_aggregate('MAX', $column);
    }

    public function min(string $column): mixed
    {
        return $this->_aggregate('MIN', $column);
    }

    public function avg(string $column): mixed
    {
        return $this->_aggregate('AVG', $column);
    }

    public function sum(string $column): mixed
    {
        return $this->_aggregate('SUM', $column);
    }

    /**
     * Get the current SQL statement.
     */
    public function toSql(): string
    {
        return $this->_getQueryBuilderInstance()->_buildSelectSQL();
    }

    /**
     * Get the current SQL statement with bindings interpolated (Approximation).
     */
    public function toRawSql(): string
    {
        $builder = $this->_getQueryBuilderInstance();
        $sql = $builder->_buildSelectSQL();
        $bindings = $builder->_bindings;

        foreach ($bindings as $bind) {
            if (is_string($bind)) {
                $bind = "'" . addslashes($bind) . "'";
            } elseif (is_null($bind)) {
                $bind = 'NULL';
            } elseif (is_bool($bind)) {
                $bind = $bind ? '1' : '0';
            } elseif ($bind instanceof \DateTimeInterface) {
                $bind = "'" . $bind->format('Y-m-d H:i:s') . "'";
            }

            // Replace the first occurrence of '?' with the binding value
            $pos = strpos($sql, '?');
            if ($pos !== false) {
                $sql = substr_replace($sql, (string)$bind, $pos, 1);
            }
        }

        return $sql;
    }

    /**
     * Paginate the given query.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @param string|null $path
     * @param array $query
     * @return Paginator
     */
    public function paginate(
        int     $perPage = 15,
        array   $columns = ['*'],
        string  $pageName = 'page',
        ?int    $page = null,
        ?string $path = '',
        array   $query = []
    ): Paginator
    {
        $page = $page ?: (int)($_GET[$pageName] ?? 1);
        $query = $query ?: $_GET['query'] ?? [];
        if ($page < 1) $page = 1;

        // 1. Get Total Count
        // Use a fresh instance and copy state manually to avoid clone issues and state pollution
        $countBuilder = static::query();
        $currentBuilder = $this->_getQueryBuilderInstance();

        $countBuilder->_wheres = $currentBuilder->_wheres;
        $countBuilder->_bindings = $currentBuilder->_bindings;
        $countBuilder->_joins = $currentBuilder->_joins;
        $countBuilder->_groups = $currentBuilder->_groups;
        $countBuilder->_havings = $currentBuilder->_havings;
        $countBuilder->_distinct = $currentBuilder->_distinct;

        $total = $countBuilder->count();

        // 2. Get Items for current page
        // Use a fresh builder to preserve the state of $currentBuilder (prevents reset)
        $itemBuilder = static::query();
        $itemBuilder->_wheres = $currentBuilder->_wheres;
        $itemBuilder->_bindings = $currentBuilder->_bindings;
        $itemBuilder->_joins = $currentBuilder->_joins;
        $itemBuilder->_groups = $currentBuilder->_groups;
        $itemBuilder->_havings = $currentBuilder->_havings;
        $itemBuilder->_orders = $currentBuilder->_orders;
        $itemBuilder->_selects = $currentBuilder->_selects;
        $itemBuilder->_distinct = $currentBuilder->_distinct;
        $itemBuilder->_with = $currentBuilder->_with;

        $itemBuilder->limit($perPage);
        $itemBuilder->offset(($page - 1) * $perPage);

        if ($columns !== ['*']) {
            $itemBuilder->select($columns);
        }

        // Executing get() on $itemBuilder resets $itemBuilder, but $currentBuilder remains untouched
        $results = $itemBuilder->get();

        // 3. Return Paginator
        return new Paginator(
            $results,
            $total,
            $perPage,
            $page,
            $path,
            (array)$query
        );
    }

    private function _buildSelectSQL(): string
    {
        if (!$this->_isQueryBuilderInstance || $this->_queryAction !== 'SELECT') {
            throw new RuntimeException("Query building methods called out of sequence.");
        }

        $table = $this->getTable();
        if (empty($this->_selects)) $this->_selects = ["`$table`.*"];

        $distinct = $this->_distinct ? 'DISTINCT ' : '';
        $sql = "SELECT $distinct" . implode(', ', $this->_selects) . " FROM `$table`";

        foreach ($this->_joins as $join) {
            $sql .= " {$join['type']} JOIN `{$join['table']}` ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        if (!empty($this->_wheres)) {
            $sql .= " WHERE ";
            foreach ($this->_wheres as $i => $where) {
                if ($i > 0) $sql .= " {$where['boolean']} ";
                $type = $where['type'] ?? 'basic';

                if ($type === 'raw') {
                    $sql .= $where['sql'];
                } elseif ($type === 'in' || $type === 'not_in') {
                    $placeholders = implode(', ', array_fill(0, $where['count'], '?'));
                    $operator = ($type === 'in') ? 'IN' : 'NOT IN';
                    $sql .= "{$where['column']} {$operator} ({$placeholders})";
                } else {
                    $sql .= "{$where['column']} {$where['operator']}" . ($where['needs_binding'] ? " ?" : "");
                }
            }
        }

        if (!empty($this->_groups)) $sql .= " GROUP BY " . implode(', ', $this->_groups);

        if (!empty($this->_havings)) {
            $sql .= " HAVING ";
            foreach ($this->_havings as $i => $having) {
                if ($i > 0) $sql .= " {$having['boolean']} ";
                $sql .= "{$having['column']} {$having['operator']} ?";
            }
        }

        if (!empty($this->_orders)) $sql .= " ORDER BY " . implode(', ', array_map(fn($o) => "{$o['column']} {$o['direction']}", $this->_orders));

        if ($this->_limit !== null) $sql .= " LIMIT $this->_limit";
        if ($this->_offset !== null) $sql .= " OFFSET $this->_offset";
        // dump($sql, $this->_bindings);

        return $sql;
    }
}