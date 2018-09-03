<?php

namespace Reindexer\Entities;

class SdlQuery extends Entity {
    protected $namespace;
    protected $limit;
    protected $offset;
    protected $distinct;
    protected $reqTotal;
    protected $filters;
    protected $sort;
    protected $joined;
    protected $merged;
    protected $selectFilter;
    protected $selectFunctions;
    protected $aggregations;

    protected $mapJsonFields = [
        'namespace' = 'namespace',
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
        'aggregations' => 'aggregations'
    ];
}
