<?php

declare(strict_types=1);

namespace Strux\Component\Form\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class FieldAttribute
{
    public function __construct(
        public string  $type = 'text',
        public ?string $label = null,
        public array   $rules = [],
        public array   $attributes = [],
        public array   $options = [],
        public mixed   $default = null
    )
    {
    }
}