<?php

namespace Reindexer\Entities;

class Query {
    protected $namespace;
    protected $limit;
    protected $offset;
    protected $distinct;
    protected $refTotal;
    protected $filters;
    protected $sort;
    protected $joined;
    protected $merged;
    protected $selectFilter;
    protected $selectFunctions;
    protected $aggregations;
}