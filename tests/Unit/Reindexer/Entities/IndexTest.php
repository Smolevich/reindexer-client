<?php

namespace Tests\Unit\Reindexer\Indexes;

use Reindexer\Client\Api;
use Reindexer\Entities\Index;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;
use Tests\Unit\Reindexer\BaseTest;

class IndexTest extends BaseTest
{
    protected $index;
    protected $client;

    public function setUp(): void
    {
        $this->client = $this->createMock(Api::class);
        $this->index = new Index($this->client);
    }

    public function testGetAndSetIsDense()
    {
        $this->index->setIsDense(true);
        $this->assertTrue($this->index->isDense());
        $this->index->setIsDense(false);
        $this->assertFalse($this->index->isDense());
    }

    public function testGetAndSetName()
    {
        $this->index->setName('index_name');
        $this->assertEquals('index_name', $this->index->getName());
    }

    public function testGetAndSetIsPk()
    {
        $this->index->setIsPk(true);
        $this->assertTrue($this->index->isPk());
    }

    public function testGetAndSetFieldType()
    {
        $this->index->setFieldType(FieldType::INT);
        $this->assertEquals(FieldType::INT, $this->index->getFieldType());
    }

    public function testGetAndSetSortOrderLetters()
    {
        $this->index->setSortOrderLetters('asc');
        $this->assertEquals('asc', $this->index->getSortOrderLetters());
    }

    public function testGetAndSetIsArray()
    {
        $this->index->setIsArray(false);
        $this->assertFalse($this->index->isArray());
    }

    public function testGetAndSetCollateMode()
    {
        $this->index->setCollateMode('none');
        $this->assertEquals('none', $this->index->getCollateMode());
    }

    public function testGetAndSetJsonPath()
    {
        $this->index->setJsonPaths(['json_path']);
        $this->assertEquals(['json_path'], $this->index->getJsonPaths());
    }
    public function testGetSetFieldType()
    {
        $this->index->setFieldType(FieldType::DOUBLE);
        $this->assertEquals(FieldType::DOUBLE, $this->index->getFieldType());
    }

    public function testGetAndSetIsAppendable()
    {
        $this->index->setIsAppendable(false);
        $this->assertFalse($this->index->isAppendable());
    }

    public function testGetAndIndexType()
    {
        $this->index->setIndexType(IndexType::HASH);
        $this->assertEquals(IndexType::HASH, $this->index->getIndexType());
    }
}
