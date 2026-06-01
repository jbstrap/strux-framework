<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Dialect;

class SqliteDialect extends SqlDialect
{
    public function buildUpsertQuery(string $table, array $columns, array $update): string
    {
        $sql = $this->buildInsertQuery($table, $columns, ['']); 
        // SQLite uses ON CONFLICT DO UPDATE (since 3.24)
        return $sql; 
    }
}
