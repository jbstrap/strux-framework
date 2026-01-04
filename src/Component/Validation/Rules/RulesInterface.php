<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

interface RulesInterface
{
    public function validate($value, mixed $data = null): ?string;
}