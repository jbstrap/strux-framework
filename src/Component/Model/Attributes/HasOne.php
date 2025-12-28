<?php

declare(strict_types=1);

namespace Strux\Component\Model\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasOne extends RelationAttribute
{
    public function __construct(
        public string  $related,
        public ?string $foreignKey = null,
        public ?string $localKey = null,
        public string  $onDelete = 'restrict',
        public string  $onUpdate = 'cascade'
    )
    {
    }
}