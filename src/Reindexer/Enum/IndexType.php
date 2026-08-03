<?php

declare(strict_types=1);

namespace Reindexer\Enum;

enum IndexType: string
{
    case HASH = 'hash';
    case TREE = 'tree';
    case TEXT = 'text';
    case RTREE = 'rtree';
    case TTL = 'ttl';
    case COLUMN = '-';
}
