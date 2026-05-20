<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

use PDO;
use Strux\Support\ContainerBridge;
use Throwable;

class Unique implements RulesInterface
{
    private string $table;
    private string $column;
    private mixed $exceptValue;
    private string $exceptColumn;
    private ?string $message;

    public function __construct(
        string $table,
        string $column,
        mixed $exceptValue = null,
        string $exceptColumn = 'id',
        ?string $message = null
    ) {
        $this->table = $table;
        $this->column = $column;
        $this->exceptValue = $exceptValue;
        $this->exceptColumn = $exceptColumn;
        $this->message = $message ?? 'The value has already been taken.';
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null; // Let the Required rule handle empty values
        }

        try {
            /** @var PDO $db */
            $db = ContainerBridge::resolve(PDO::class);

            $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE `{$this->column}` = :value";
            $params = [':value' => $value];

            if ($this->exceptValue !== null && $this->exceptValue !== '') {
                $sql .= " AND `{$this->exceptColumn}` != :except";
                $params[':except'] = $this->exceptValue;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $count = (int) $stmt->fetchColumn();

            if ($count > 0) {
                return $this->message;
            }

        } catch (Throwable $e) {
            error_log("Unique Validation Error: " . $e->getMessage());
            return "Unable to verify uniqueness due to a system error.";
        }

        return null;
    }
}
