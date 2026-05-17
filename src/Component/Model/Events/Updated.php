<?php

declare(strict_types=1);

namespace Strux\Component\Model\Events;

use Strux\Component\Model\Model;

class Updated
{
    public function __construct(
        public readonly Model $model
    ) {
    }
}