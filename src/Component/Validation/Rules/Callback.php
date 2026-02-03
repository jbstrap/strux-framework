<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

use Closure;

class Callback implements RulesInterface
{
    private Closure $callback;
    private ?string $message;

    public function __construct(callable $callback, ?string $message = null)
    {
        $this->callback = $callback instanceof Closure ? $callback : $callback(...);
        $this->message = $message ?? 'Custom validation failed.';
    }

    public function validate($value, $data = null): ?string
    {
        $result = ($this->callback)($value, $data);

        if ($result === true) {
            return null;
        }

        return is_string($result) ? $result : $this->message;
    }
}