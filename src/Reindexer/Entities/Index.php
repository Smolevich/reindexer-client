<?php

namespace Reindexer\Entities;

class Index extends Entity {
    private $name;
    private $jsonPaths;
    private $fieldType;
    private $indexType;
    private $isPk = false;
    private $isArray = false;
    private $isDense = false;
    private $isAppendable = false;
    private $collateMode = 'none';
    private $sortOrderLetters;
    private $expireAfter = 0;

    protected $mapJsonFields = [
        'name' => 'name',
        'jsonPaths'  => 'json_paths',
        'fieldType'  => 'field_type',
        'indexType' => 'index_type',
        'isPk'  => 'is_pk',
        'isArray' => 'is_array',
        'isDense' => 'is_dense',
        'isAppendable' => 'is_appendable',
        'collateMode' => 'collate_mode',
        'sortOrderLetters' => 'sort_order_letters',
        'expireAfter' => 'expire_after'
    ];

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name) {
        $this->name = $name;

        return $this;
    }

    public function getJsonPaths(): ?array {
        return $this->jsonPaths ?? [];
    }

    public function setJsonPaths(array $jsonPaths): self {
        $this->jsonPaths = $jsonPaths;

        return $this;
    }

    public function getFieldType(): ?string {
        return $this->fieldType;
    }

    public function setFieldType($fieldType): self {
        $this->fieldType = $fieldType;

        return $this;
    }

    public function isPk() {
        return $this->isPk;
    }

    public function setIsPk(bool $isPk) {
        $this->isPk = $isPk;

        return $this;
    }

    public function isArray(): bool {
        return $this->isArray;
    }

    public function setIsArray(bool $isArray): self {
        $this->isArray = $isArray;

        return $this;
    }

    public function isDense(): bool {
        return $this->isDense;
    }

    public function setIsDense(bool $isDense): self {
        $this->isDense = $isDense;

        return $this;
    }

    public function isAppendable(): bool {
        return $this->isAppendable;
    }

    public function setIsAppendable(bool $isAppendable): self {
        $this->isAppendable = $isAppendable;

        return $this;
    }

    public function getCollateMode(): string {
        return $this->collateMode;
    }

    public function setCollateMode(string $collateMode): self {
        $this->collateMode = $collateMode;

        return $this;
    }

    public function getSortOrderLetters(): ?string {
        return $this->sortOrderLetters;
    }

    public function setSortOrderLetters(string $sortOrderLetters): self {
        $this->sortOrderLetters = $sortOrderLetters;

        return $this;
    }

    public function getIndexType(): ?string {
        return $this->indexType;
    }

    public function setIndexType(string $indexType): self {
        $this->indexType = $indexType;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpireAfter(): int {
        return $this->expireAfter;
    }

    /**
     * @param int $expireAfter
     *
     * @return $this
     */
    public function setExpireAfter(int $expireAfter): self {
        $this->expireAfter = $expireAfter;

        return $this;
    }
}
