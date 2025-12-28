<?php

declare(strict_types=1);

namespace Strux\Component\Model\Attributes;

use Attribute;
use Strux\Component\Database\Types\KeyAction;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo extends RelationAttribute
{
    public function __construct(
        public string    $related,
        public ?string   $foreignKey = null,
        public ?string   $ownerKey = null,
        public KeyAction $onDelete = KeyAction::CASCADE,
        public KeyAction $onUpdate = KeyAction::CASCADE
    )
    {
    }
}