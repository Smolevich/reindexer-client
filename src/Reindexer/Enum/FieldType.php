<?php

declare(strict_types=1);

namespace Reindexer\Enum;

enum FieldType: string
{
    case INT = 'int';
    case INT64 = 'int64';
    case DOUBLE = 'double';
    case STRING = 'string';
    case BOOL = 'bool';
    case COMPOSITE = 'composite';
    case POINT = 'point';
}
