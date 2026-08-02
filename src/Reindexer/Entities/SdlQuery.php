<?php

declare(strict_types=1);

namespace Reindexer\Entities;

class SdlQuery extends Entity
{
    protected ?string $namespace = null;
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected ?string $distinct = null;
    protected ?bool $reqTotal = null;
    protected ?array $filters = null;
    protected ?array $sort = null;
    protected ?array $joined = null;
    protected ?array $merged = null;
    protected ?array $selectFilter = null;
    protected ?array $selectFunctions = null;
    protected ?array $aggregations = null;

    protected array $mapJsonFields = [
        'namespace' => 'namespace',
        'limit' => 'limit',
        'offset' => 'offset',
        'distinct' => 'distinct',
        'reqTotal' => 'req_total',
        'filters' => 'filters',
        'sort' => 'sort',
        'joined' => 'joined',
        'merged' => 'merged',
        'selectFilter' => 'select_filter',
        'selectFunctions' => 'select_functions',
        'aggregations' => 'aggregations',
    ];
}
