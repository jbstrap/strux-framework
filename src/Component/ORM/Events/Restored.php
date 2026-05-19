<?php

declare(strict_types=1);

namespace Strux\Component\ORM\Events;

use Strux\Component\ORM\Model;

class Restored
{
    public function __construct(
        public readonly Model $model
    ) {
    }
}
