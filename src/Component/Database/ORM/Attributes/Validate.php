<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Validate
{
    /** @var array<int, mixed> */
    public array $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }
}