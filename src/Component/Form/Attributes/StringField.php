<?php

declare(strict_types=1);

namespace Strux\Component\Form\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class StringField extends FieldAttribute
{
    public function __construct(
        ?string $label = null,
        array   $rules = [], // Maps to 'validation' in your example
        array   $attributes = [],
        mixed   $default = null
    )
    {
        parent::__construct(
            type: 'text',
            label: $label,
            rules: $rules,
            attributes: $attributes,
            default: $default
        );
    }
}