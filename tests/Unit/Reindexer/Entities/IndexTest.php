<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Indexes;

use PHPUnit\Framework\MockObject\MockObject;
use Reindexer\Client\Api;
use Reindexer\Entities\Index;
use Reindexer\Enum\CollateMode;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;
use Tests\Unit\Reindexer\BaseTest;

class IndexTest extends BaseTest
{
    protected Index $index;
    protected Api&MockObject $client;

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
        $this->index->setCollateMode(CollateMode::NONE);
        $this->assertEquals(CollateMode::NONE, $this->index->getCollateMode());
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

    public function testDefaults(): void
    {
        $index = new Index();
        $this->assertNull($index->getName());
        $this->assertSame([], $index->getJsonPaths());
        $this->assertFalse($index->isPk());
        $this->assertFalse($index->isArray());
        $this->assertFalse($index->isDense());
        $this->assertFalse($index->isAppendable());
        $this->assertSame(CollateMode::NONE, $index->getCollateMode());
        $this->assertSame('', $index->getSortOrderLetters());
    }

    public function testSettersAreFluent(): void
    {
        $index = new Index();
        $this->assertSame($index, $index->setName('id'));
        $this->assertSame($index, $index->setJsonPaths(['id']));
        $this->assertSame($index, $index->setFieldType(FieldType::INT));
        $this->assertSame($index, $index->setIndexType(IndexType::HASH));
        $this->assertSame($index, $index->setIsPk(true));
        $this->assertSame($index, $index->setIsArray(true));
        $this->assertSame($index, $index->setIsDense(true));
        $this->assertSame($index, $index->setIsAppendable(true));
        $this->assertSame($index, $index->setCollateMode(CollateMode::UTF8));
        $this->assertSame($index, $index->setSortOrderLetters('абв'));
    }

    public function testGetBodySerializesAllSetFields(): void
    {
        $index = (new Index())
            ->setName('id')
            ->setJsonPaths(['id', 'legacy_id'])
            ->setFieldType(FieldType::INT)
            ->setIndexType(IndexType::HASH)
            ->setIsPk(true)
            ->setIsArray(false)
            ->setIsDense(true)
            ->setIsAppendable(false)
            ->setCollateMode(CollateMode::NUMERIC)
            ->setSortOrderLetters('abc');

        $this->assertSame(
            [
                'name' => 'id',
                'json_paths' => ['id', 'legacy_id'],
                'field_type' => 'int',
                'index_type' => 'hash',
                'is_pk' => true,
                'is_array' => false,
                'is_dense' => true,
                'is_appendable' => false,
                'collate_mode' => 'numeric',
                'sort_order_letters' => 'abc',
            ],
            $index->getBody()
        );
    }

    public function testGetBodyOmitsUnsetNullableFields(): void
    {
        $body = (new Index())->getBody();

        $this->assertArrayNotHasKey('name', $body);
        $this->assertArrayNotHasKey('json_paths', $body);
        $this->assertArrayNotHasKey('field_type', $body);
        $this->assertArrayNotHasKey('index_type', $body);
        $this->assertArrayNotHasKey('sort_order_letters', $body);
        // booleans and collate mode always have defaults and are serialized
        $this->assertSame(
            [
                'is_pk' => false,
                'is_array' => false,
                'is_dense' => false,
                'is_appendable' => false,
                'collate_mode' => 'none',
            ],
            $body
        );
    }

    public function testUnicodeNameIsPreserved(): void
    {
        $index = (new Index())->setName('поле_名前');
        $this->assertSame('поле_名前', $index->getName());
        $this->assertSame('поле_名前', $index->getBody()['name']);
    }
}
