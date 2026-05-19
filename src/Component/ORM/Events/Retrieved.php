<?php

declare(strict_types=1);

namespace Strux\Component\Model\Events;

use Strux\Component\Model\Model;

class Retrieved
{
    public function __construct(
        public readonly Model $model
    ) {
    }
}