<?php

declare(strict_types=1);

namespace Reindexer\Enum;

/**
 * Algorithm used to construct an RTree index (index_type "rtree").
 */
enum RtreeType: string
{
    case LINEAR = 'linear';
    case QUADRATIC = 'quadratic';
    case GREENE = 'greene';
    case RSTAR = 'rstar';
}
