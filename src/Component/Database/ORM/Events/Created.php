<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Events;

use Strux\Component\Database\ORM\Model;

class Created
{
    public function __construct(
        public readonly Model $model
    ) {
    }
}
