<?php

namespace Strux\Component\Database\Migration;

class TableBlueprint
{
    private string $table;
    private array $columns = [];
    private array $commands = [];
    private string $engine = 'InnoDB';
    private string $charset = 'utf8mb4';

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    // --- Column Types ---
    public function id(string $column = 'id'): self
    {
        $this->columns[] = "`$column` INT AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }

    public function string(string $column, int $length = 255): self
    {
        $this->columns[] = "`$column` VARCHAR($length)";
        return $this;
    }

    public function integer(string $column): self
    {
        $this->columns[] = "`$column` INT";
        return $this;
    }

    public function text(string $column): self
    {
        $this->columns[] = "`$column` TEXT";
        return $this;
    }

    public function boolean(string $column): self
    {
        $this->columns[] = "`$column` TINYINT(1)";
        return $this;
    }

    // --- Modifiers ---

    public function default(string|int $value): self
    {
        $last = array_pop($this->columns);
        $formatted = is_string($value) ? "'$value'" : $value;
        $this->columns[] = $last . " DEFAULT $formatted";
        return $this;
    }

    public function unique(): self
    {
        $last = array_pop($this->columns);
        $this->columns[] = $last . " UNIQUE";
        return $this;
    }

    // --- Compilation ---

    /**
     * Compiles the blueprints into a raw SQL string.
     */
    public function build(): string
    {
        if (empty($this->columns)) {
            return '';
        }

        $cols = implode(', ', $this->columns);

        return "CREATE TABLE IF NOT EXISTS `{$this->table}` ($cols) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset};";
    }
}