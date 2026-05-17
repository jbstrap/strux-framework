<?php

declare(strict_types=1);

namespace Strux\Component\Model\Events;

use Strux\Component\Model\Model;

class Created
{
    public function __construct(
        public readonly Model $model
    ) {
    }
}