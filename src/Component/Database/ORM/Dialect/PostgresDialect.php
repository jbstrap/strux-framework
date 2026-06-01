<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Dialect;

class PostgresDialect extends SqlDialect
{
    public function buildUpsertQuery(string $table, array $columns, array $update): string
    {
        $sql = $this->buildInsertQuery($table, $columns, ['']); 
        // Postgres uses ON CONFLICT DO UPDATE
        return $sql; 
    }
}
