<?php

declare(strict_types=1);

namespace Reindexer\Entities;

use Reindexer\Enum\CollateMode;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;

class Index extends Entity
{
    private ?string $name = null;
    private ?array $jsonPaths = null;
    private ?FieldType $fieldType = null;
    private ?IndexType $indexType = null;
    private bool $isPk = false;
    private bool $isArray = false;
    private bool $isDense = false;
    private bool $isAppendable = false;
    private CollateMode $collateMode = CollateMode::NONE;
    private ?string $sortOrderLetters = null;

    protected array $mapJsonFields = [
        'name' => 'name',
        'jsonPaths' => 'json_paths',
        'fieldType' => 'field_type',
        'indexType' => 'index_type',
        'isPk' => 'is_pk',
        'isArray' => 'is_array',
        'isDense' => 'is_dense',
        'isAppendable' => 'is_appendable',
        'collateMode' => 'collate_mode',
        'sortOrderLetters' => 'sort_order_letters',
    ];

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getJsonPaths(): array
    {
        return $this->jsonPaths ?? [];
    }

    public function setJsonPaths(array $jsonPaths): self
    {
        $this->jsonPaths = $jsonPaths;

        return $this;
    }

    public function getFieldType(): FieldType
    {
        return $this->fieldType;
    }

    public function setFieldType(FieldType $fieldType): self
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    public function isPk(): bool
    {
        return $this->isPk;
    }

    public function setIsPk(bool $isPk): self
    {
        $this->isPk = $isPk;

        return $this;
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    public function setIsArray(bool $isArray): self
    {
        $this->isArray = $isArray;

        return $this;
    }

    public function isDense(): bool
    {
        return $this->isDense;
    }

    public function setIsDense(bool $isDense): self
    {
        $this->isDense = $isDense;

        return $this;
    }

    public function isAppendable(): bool
    {
        return $this->isAppendable;
    }

    public function setIsAppendable(bool $isAppendable): self
    {
        $this->isAppendable = $isAppendable;

        return $this;
    }

    public function getCollateMode(): CollateMode
    {
        return $this->collateMode;
    }

    public function setCollateMode(CollateMode $collateMode): self
    {
        $this->collateMode = $collateMode;

        return $this;
    }

    public function getSortOrderLetters(): string
    {
        return $this->sortOrderLetters ?? '';
    }

    public function setSortOrderLetters(string $sortOrderLetters): self
    {
        $this->sortOrderLetters = $sortOrderLetters;

        return $this;
    }

    public function getIndexType(): IndexType
    {
        return $this->indexType;
    }

    public function setIndexType(IndexType $indexType): self
    {
        $this->indexType = $indexType;

        return $this;
    }
}
