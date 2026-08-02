<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\Enum\CollateMode;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;

#[CoversClass(CollateMode::class)]
#[CoversClass(FieldType::class)]
#[CoversClass(IndexType::class)]
class EnumTest extends TestCase
{
    public function testIndexTypeValues(): void
    {
        $this->assertSame(
            ['hash' => IndexType::HASH, 'tree' => IndexType::TREE, 'text' => IndexType::TEXT, '-' => IndexType::COLUMN],
            array_column(array_map(
                static fn (IndexType $c) => ['k' => $c->value, 'v' => $c],
                IndexType::cases()
            ), 'v', 'k')
        );
    }

    public function testFieldTypeValues(): void
    {
        $this->assertSame(
            ['int', 'int64', 'double', 'string', 'bool', 'composite'],
            array_map(static fn (FieldType $c) => $c->value, FieldType::cases())
        );
    }

    public function testCollateModeValues(): void
    {
        $this->assertSame(
            ['none', 'ascii', 'utf8', 'numeric'],
            array_map(static fn (CollateMode $c) => $c->value, CollateMode::cases())
        );
    }

    public function testFromRoundTrip(): void
    {
        foreach (IndexType::cases() as $case) {
            $this->assertSame($case, IndexType::from($case->value));
        }
        foreach (FieldType::cases() as $case) {
            $this->assertSame($case, FieldType::from($case->value));
        }
        foreach (CollateMode::cases() as $case) {
            $this->assertSame($case, CollateMode::from($case->value));
        }
    }

    public function testTryFromUnknownValueReturnsNull(): void
    {
        $this->assertNull(IndexType::tryFrom('ttl'));
        $this->assertNull(IndexType::tryFrom('rtree'));
        $this->assertNull(FieldType::tryFrom('uuid'));
        $this->assertNull(CollateMode::tryFrom('custom'));
    }

    public function testFromUnknownValueThrows(): void
    {
        $this->expectException(\ValueError::class);
        IndexType::from('fulltext');
    }
}
