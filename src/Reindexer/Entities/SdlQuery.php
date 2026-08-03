<?php

declare(strict_types=1);

namespace Reindexer\Entities;

/**
 * Typed builder for the Query-DSL body accepted by POST/PUT/DELETE /query
 * (see the "Query" schema in the server OpenAPI spec).
 */
class SdlQuery extends Entity
{
    protected ?string $namespace = null;
    protected ?string $type = null;
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected ?string $distinct = null;
    protected bool|string|null $reqTotal = null;
    protected ?array $filters = null;
    protected ?array $sort = null;
    /** @deprecated the server DSL expects joins inside filters[].join_query */
    protected ?array $joined = null;
    /** @deprecated superseded by $mergeQueries (merge_queries) */
    protected ?array $merged = null;
    protected ?array $mergeQueries = null;
    protected ?array $selectFilter = null;
    protected ?array $selectFunctions = null;
    protected ?array $dropFields = null;
    protected ?array $updateFields = null;
    protected ?array $aggregations = null;
    protected ?bool $explain = null;

    protected array $mapJsonFields = [
        'namespace' => 'namespace',
        'type' => 'type',
        'limit' => 'limit',
        'offset' => 'offset',
        'distinct' => 'distinct',
        'reqTotal' => 'req_total',
        'filters' => 'filters',
        'sort' => 'sort',
        'joined' => 'joined',
        'merged' => 'merged',
        'mergeQueries' => 'merge_queries',
        'selectFilter' => 'select_filter',
        'selectFunctions' => 'select_functions',
        'dropFields' => 'drop_fields',
        'updateFields' => 'update_fields',
        'aggregations' => 'aggregations',
        'explain' => 'explain',
    ];

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param string $type one of: select, update, delete, truncate
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function setDistinct(string $distinct): self
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * The server expects the enum disabled/enabled/cached; booleans are
     * normalized (true => enabled, false => disabled) for convenience.
     */
    public function setReqTotal(bool|string $reqTotal): self
    {
        if (is_bool($reqTotal)) {
            $reqTotal = $reqTotal ? 'enabled' : 'disabled';
        }

        $this->reqTotal = $reqTotal;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $filters FilterDef list; joins go
     *                                                  into filters[].join_query
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @param array<string, mixed>|array<int, array<string, mixed>> $sort SortDef or list of SortDef
     */
    public function setSort(array $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $mergeQueries Query list merged with the main query
     */
    public function setMergeQueries(array $mergeQueries): self
    {
        $this->mergeQueries = $mergeQueries;

        return $this;
    }

    /**
     * @param string[] $selectFilter
     */
    public function setSelectFilter(array $selectFilter): self
    {
        $this->selectFilter = $selectFilter;

        return $this;
    }

    /**
     * @param string[] $selectFunctions
     */
    public function setSelectFunctions(array $selectFunctions): self
    {
        $this->selectFunctions = $selectFunctions;

        return $this;
    }

    /**
     * @param string[] $dropFields fields removed by an update query
     */
    public function setDropFields(array $dropFields): self
    {
        $this->dropFields = $dropFields;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $updateFields UpdateField list ({name, type, values})
     */
    public function setUpdateFields(array $updateFields): self
    {
        $this->updateFields = $updateFields;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $aggregations AggregationsDef list
     */
    public function setAggregations(array $aggregations): self
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    public function setExplain(bool $explain): self
    {
        $this->explain = $explain;

        return $this;
    }
}
