<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Indexes;

use PHPUnit\Framework\MockObject\MockObject;
use Reindexer\Client\Api;
use Reindexer\Entities\Index;
use Reindexer\Enum\CollateMode;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;
use Reindexer\Enum\RtreeType;
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

    public function testGetAndSetIsSparse()
    {
        $this->index->setIsSparse(true);
        $this->assertTrue($this->index->isSparse());
        $this->index->setIsSparse(false);
        $this->assertFalse($this->index->isSparse());
    }

    public function testGetAndSetExpireAfter()
    {
        $this->index->setExpireAfter(3600);
        $this->assertSame(3600, $this->index->getExpireAfter());
    }

    public function testGetAndSetRtreeType()
    {
        $this->index->setRtreeType(RtreeType::QUADRATIC);
        $this->assertSame(RtreeType::QUADRATIC, $this->index->getRtreeType());
    }

    public function testGetAndSetConfig()
    {
        $config = ['enable_translit' => true, 'max_typos' => 2];
        $this->index->setConfig($config);
        $this->assertSame($config, $this->index->getConfig());
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
        $this->assertFalse($index->isSparse());
        $this->assertNull($index->getExpireAfter());
        $this->assertNull($index->getRtreeType());
        $this->assertSame([], $index->getConfig());
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
        $this->assertSame($index, $index->setIsSparse(true));
        $this->assertSame($index, $index->setExpireAfter(60));
        $this->assertSame($index, $index->setRtreeType(RtreeType::RSTAR));
        $this->assertSame($index, $index->setConfig(['bm25_boost' => 1.5]));
    }

    public function testGetBodySerializesTtlIndexFields(): void
    {
        $index = (new Index())
            ->setName('expires')
            ->setJsonPaths(['expires'])
            ->setFieldType(FieldType::INT64)
            ->setIndexType(IndexType::TTL)
            ->setExpireAfter(86400)
            ->setIsSparse(true);

        $body = $index->getBody();
        $this->assertSame('ttl', $body['index_type']);
        $this->assertSame('int64', $body['field_type']);
        $this->assertSame(86400, $body['expire_after']);
        $this->assertTrue($body['is_sparse']);
    }

    public function testGetBodySerializesRtreeIndexFields(): void
    {
        $index = (new Index())
            ->setName('location')
            ->setJsonPaths(['location'])
            ->setFieldType(FieldType::POINT)
            ->setIndexType(IndexType::RTREE)
            ->setRtreeType(RtreeType::GREENE);

        $body = $index->getBody();
        $this->assertSame('rtree', $body['index_type']);
        $this->assertSame('point', $body['field_type']);
        $this->assertSame('greene', $body['rtree_type']);
    }

    public function testGetBodySerializesConfigAsIs(): void
    {
        $config = ['enable_translit' => false, 'stemmers' => ['en', 'ru']];
        $body = (new Index())->setName('body')->setConfig($config)->getBody();

        $this->assertSame($config, $body['config']);
    }

    public function testGetBodyOmitsNewNullableFieldsWhenUnset(): void
    {
        $body = (new Index())->setName('plain')->getBody();

        $this->assertArrayNotHasKey('is_sparse', $body);
        $this->assertArrayNotHasKey('expire_after', $body);
        $this->assertArrayNotHasKey('rtree_type', $body);
        $this->assertArrayNotHasKey('config', $body);
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
