<?php

declare(strict_types=1);

namespace Strux\Component\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Rule
{
    /** @var string[] */
    public array $rules;

    public function __construct(string ...$rules)
    {
        $this->rules = $rules;
    }
}