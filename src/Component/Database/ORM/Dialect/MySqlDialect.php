<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Dialect;

class MySqlDialect extends SqlDialect
{
    /**
     * Quote a value in keyword identifiers for MySQL (backticks).
     */
    public function quote(string $value): string
    {
        if (str_contains(strtolower($value), ' as ')) {
            $parts = preg_split('/\s+as\s+/i', $value);
            return $this->quote($parts[0]) . ' AS ' . $this->quote($parts[1]);
        }

        if (str_contains($value, '.')) {
            return implode('.', array_map([$this, 'quote'], explode('.', $value)));
        }

        if ($value === '*') {
            return $value;
        }

        return '`' . str_replace('`', '``', $value) . '`';
    }

    public function buildUpsertQuery(string $table, array $columns, array $update): string
    {
        $sql = $this->buildInsertQuery($table, $columns, ['']);
        return $sql;
    }
}
