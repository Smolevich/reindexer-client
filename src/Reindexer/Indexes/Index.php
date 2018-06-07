<?php

namespace Reindexer\Indexes;

use Reindexer\Entity;

class Index extends Entity {
    protected $name;
    protected $jsonPath;
    protected $fieldType;
    protected $isPk = true;
    protected $isArray = false;
    protected $isDense = false;
    protected $isAppendable = false;
    protected $collateMode = false;
    protected $sortOrderLetters;

    public function getName() {
        return $this->name;
    }

    public function setName(string $name) {
        $this->name = $name;

        return $this;
    }

    public function getJsonPath(): string {
        return $this->jsonPath ?? '';
    }

    public function setJsonPath(string $jsonPath): self {
        $this->jsonPath = $jsonPath;

        return $this;
    }

    public function getFieldType(): string {
        return $this->fieldType;
    }

    public function setFieldType($fieldType): self {
        $this->fieldType = $fieldType;
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

    public function isCollateMode(): string {
        return $this->collateMode;
    }

    public function setCollateMode(string $collateMode): self {
        $this->collateMode = $collateMode;

        return $this;
    }

    public function getSortOrderLetters(): string {
        return $this->sortOrderLetters;
    }

    public function setSortOrderLetters(string $sortOrderLetters): self {
        $this->sortOrderLetters = $sortOrderLetters;

        return $this;
    }
}