<?php

namespace Strux\Component\Database\Seeder;

use PDO;

interface SeederInterface
{
    public function run(?PDO $db): void;
}