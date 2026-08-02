<?php

declare(strict_types=1);

namespace Reindexer\Enum;

enum CollateMode: string
{
    case NONE = 'none';
    case ASCII = 'ascii';
    case UTF8 = 'utf8';
    case NUMERIC = 'numeric';
}
