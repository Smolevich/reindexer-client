<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Entities;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\Entities\Entity;
use Reindexer\Enum\IndexType;

#[CoversClass(Entity::class)]
class EntityTest extends TestCase
{
    public function testNullPropertiesAreSkipped(): void
    {
        $entity = new class () extends Entity {
            protected ?string $filled = 'value';
            protected ?string $empty = null;
            protected array $mapJsonFields = [
                'filled' => 'filled',
                'empty' => 'empty',
            ];
        };

        $this->assertSame(['filled' => 'value'], $entity->getBody());
    }

    public function testFalseAndZeroValuesAreKept(): void
    {
        $entity = new class () extends Entity {
            protected bool $flag = false;
            protected int $limit = 0;
            protected string $text = '';
            protected array $mapJsonFields = [
                'flag' => 'flag',
                'limit' => 'limit',
                'text' => 'text',
            ];
        };

        $this->assertSame(
            ['flag' => false, 'limit' => 0, 'text' => ''],
            $entity->getBody()
        );
    }

    public function testUnmappedPropertiesAreSkipped(): void
    {
        $entity = new class () extends Entity {
            protected string $mapped = 'yes';
            protected string $unmapped = 'no';
            protected array $mapJsonFields = [
                'mapped' => 'mapped',
            ];
        };

        $this->assertSame(['mapped' => 'yes'], $entity->getBody());
    }

    public function testPropertyNamesAreRenamedByMap(): void
    {
        $entity = new class () extends Entity {
            protected bool $reqTotal = true;
            protected array $mapJsonFields = [
                'reqTotal' => 'req_total',
            ];
        };

        $this->assertSame(['req_total' => true], $entity->getBody());
    }

    public function testBackedEnumIsSerializedToItsValue(): void
    {
        $entity = new class () extends Entity {
            protected ?IndexType $indexType = IndexType::TREE;
            protected array $mapJsonFields = [
                'indexType' => 'index_type',
            ];
        };

        $this->assertSame(['index_type' => 'tree'], $entity->getBody());
    }

    public function testPrivatePropertiesAreSerializedToo(): void
    {
        $entity = new class () extends Entity {
            private string $secret = 'private-value';
            protected array $mapJsonFields = [
                'secret' => 'secret',
            ];

            public function __construct()
            {
                // private property on anonymous class, read via reflection
            }
        };

        $this->assertSame(['secret' => 'private-value'], $entity->getBody());
    }

    public function testNestedObjectIsParsedRecursively(): void
    {
        $nested = new class () {
            private string $inner = 'deep';
        };

        $entity = new class ($nested) extends Entity {
            protected array $mapJsonFields = [
                'nested' => 'nested',
                'inner' => 'inner',
            ];

            public function __construct(protected object $nested)
            {
            }
        };

        $this->assertSame(['nested' => ['inner' => 'deep']], $entity->getBody());
    }

    public function testUnicodeValuesSurviveSerialization(): void
    {
        $entity = new class () extends Entity {
            protected string $title = 'Привет, мир! 🚀 汉字';
            protected array $mapJsonFields = [
                'title' => 'title',
            ];
        };

        $this->assertSame(['title' => 'Привет, мир! 🚀 汉字'], $entity->getBody());
    }

    public function testEmptyMapProducesEmptyBody(): void
    {
        $entity = new class () extends Entity {
            protected string $anything = 'value';
        };

        $this->assertSame([], $entity->getBody());
    }
}
