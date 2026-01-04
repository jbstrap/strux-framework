<?php

declare(strict_types=1);

namespace Strux\Component\Database\Attributes;

use Attribute;
use Strux\Component\Database\Types\Field;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Column
{
    public function __construct(
        public ?string $name = null,
        public ?Field  $type = null, // Changed from Field::integer to null for auto-inference
        public int     $length = 255,
        public bool    $nullable = false,
        public bool    $unique = false,
        public mixed   $default = null,
        public ?array  $enums = null,

        // New Flags for Timestamps
        public bool    $currentTimestamp = false,        // Adds DEFAULT CURRENT_TIMESTAMP
        public bool    $onUpdateCurrentTimestamp = false // Adds ON UPDATE CURRENT_TIMESTAMP
    )
    {
    }
}