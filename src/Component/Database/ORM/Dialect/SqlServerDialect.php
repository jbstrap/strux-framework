<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Dialect;

class SqlServerDialect extends SqlDialect
{
    /**
     * Quote a value in keyword identifiers for SQL Server (brackets).
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

        return '[' . str_replace(']', ']]', $value) . ']';
    }

    public function buildSelectQuery(array $query): string
    {
        $sql = "SELECT ";

        if (!empty($query['distinct'])) {
            $sql .= "DISTINCT ";
        }

        if (empty($query['selects'])) {
            $sql .= $this->quoteTable($query['from']) . ".*";
        } else {
            $selectParts = [];
            foreach ($query['selects'] as $select) {
                $selectParts[] = (string) $select['sql'];
            }
            $sql .= implode(', ', $selectParts);
        }

        $sql .= " FROM " . $this->quoteTable($query['from']);

        if (!empty($query['joins'])) {
            foreach ($query['joins'] as $join) {
                $sql .= " {$join['type']} JOIN " . $this->quoteTable($join['table']) . " ON " . $this->quote($join['first']) . " {$join['operator']} " . $this->quote($join['second']);
            }
        }

        if (!empty($query['wheres'])) {
            $sql .= " WHERE " . $this->compileWheres($query['wheres']);
        }

        if (!empty($query['groups'])) {
            $sql .= " GROUP BY " . implode(', ', array_map([$this, 'quote'], $query['groups']));
        }

        if (!empty($query['havings'])) {
            $sql .= " HAVING ";
            foreach ($query['havings'] as $i => $having) {
                if ($i > 0) {
                    $sql .= " {$having['boolean']} ";
                }
                $sql .= $this->quote($having['column']) . " {$having['operator']} ?";
            }
        }

        if (!empty($query['orders'])) {
            $orderParts = array_map(function ($o) {
                return $this->quote($o['column']) . " {$o['direction']}";
            }, $query['orders']);
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }

        if ($query['take'] !== null || $query['skip'] !== null) {
            if (empty($query['orders'])) {
                // SQL server requires an ORDER BY for OFFSET/FETCH
                $sql .= " ORDER BY (SELECT 0)";
            }

            $skip = (int) ($query['skip'] ?? 0);
            $sql .= " OFFSET {$skip} ROWS";

            if ($query['take'] !== null) {
                $take = (int) $query['take'];
                $sql .= " FETCH NEXT {$take} ROWS ONLY";
            }
        }

        return $sql;
    }

    public function buildUpsertQuery(string $table, array $columns, array $update): string
    {
        // SQL Server uses MERGE
        $sql = $this->buildInsertQuery($table, $columns, ['']); 
        return $sql; 
    }
}
