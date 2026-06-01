<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Enums;

enum DataType: string
{
    case JSON = 'json';
    case ARRAY = 'array';
    case INT = 'int';
    case FLOAT = 'float';
    case BOOL = 'bool';
    case STRING = 'string';
    case DATETIME = 'datetime';
    case ENCRYPTED = 'encrypted';
}
