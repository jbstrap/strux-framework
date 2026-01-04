<?php

declare(strict_types=1);

namespace Strux\Component\Form\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class PasswordField extends FieldAttribute
{
    public function __construct(
        ?string $label = null,
        array   $rules = [],
        array   $attributes = []
    )
    {
        parent::__construct(
            type: 'password',
            label: $label,
            rules: $rules,
            attributes: $attributes
        );
    }
}