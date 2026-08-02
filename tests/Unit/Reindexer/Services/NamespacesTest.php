<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Reindexer\Services\Namespaces;
use Tests\Unit\Reindexer\BaseTest;

#[CoversClass(Namespaces::class)]
class NamespacesTest extends BaseTest
{
    private MockObject $api;
    private Namespaces $service;

    public function setUp(): void
    {
        $this->api = $this->createApiMock(['request']);
        $this->service = new Namespaces($this->api);
    }

    public function testGetDatabase()
    {
        $expected = 'database';
        $this->service->setDatabase($expected);
        $this->assertEquals($expected, $this->service->getDatabase());
    }

    public function testGetList()
    {
        $responseData = [
            'items' => [
                [
                    'name' => 'namespace_one',
                ],
                [
                    'name' => 'namespace_two',
                ],
            ],
        ];
        $response = $this->createApiResponseMock(['getResponseBody']);
        $response->method('getResponseBody')->willReturn(json_encode($responseData));
        $this->api->method('request')->willReturn($response);
        $actual = $this->service->getList();
        $this->assertEquals(json_encode($responseData), $actual->getResponseBody());
    }

    public function testGet()
    {
        $responseData = [
            'name' => 'namespace_one',
            'storage' => [
                'enabled' => true,
            ],
            'indexes' => [
                [
                    'name' => 'id',
                    'field_type' => 'int',
                    'index_type' => 'hash',
                    'is_pk' => true,
                    'is_array' => false,
                    'is_dense' => true,
                    'is_sparse' => false,
                    'collate_mode' => 'none',
                    'sort_order_letters' => '',
                    'expire_after' => 0,
                    'config' => [],
                    'json_paths' => [
                        'id',
                    ],
                ],
            ],
        ];
        $response = $this->createApiResponseMock(['getResponseBody']);
        $response->method('getResponseBody')->willReturn(json_encode($responseData));
        $this->api->method('request')->willReturn($response);
        $actual = $this->service->get('namespace_one');
        $this->assertEquals(json_encode($responseData), $actual->getResponseBody());
    }

    public function testTruncate()
    {
        $responseData = [
            'success' => true,
            'response_code' => 0,
            'description' => 'string',
        ];
        $response = $this->createApiResponseMock(['getResponseBody']);
        $response->method('getResponseBody')->willReturn(json_encode($responseData));
        $this->api->method('request')->willReturn($response);
        $actual = $this->service->truncate('namespace_one');
        $this->assertEquals(json_encode($responseData), $actual->getResponseBody());
    }

    public function testRename()
    {
        $responseData = [
            'success' => true,
            'response_code' => 0,
            'description' => 'string',
        ];
        $response = $this->createApiResponseMock(['getResponseBody']);
        $response->method('getResponseBody')->willReturn(json_encode($responseData));
        $this->api->method('request')->willReturn($response);
        $actual = $this->service->rename('namespace_one', 'namespace_two');
        $this->assertEquals(json_encode($responseData), $actual->getResponseBody());
    }

    public function testGetMetaDataKey()
    {
        $responseData = [
            'success' => true,
            'response_code' => 0,
            'description' => 'string',
        ];
        $response = $this->createApiResponseMock(['getResponseBody']);
        $response->method('getResponseBody')->willReturn(json_encode($responseData));
        $this->api->method('request')->willReturn($response);
        $actual = $this->service->getMetaDataKey('namespace_one', 'test_key');
        $this->assertEquals(json_encode($responseData), $actual->getResponseBody());
    }

    public function testGetMetaList()
    {
        $responseData = [
            'total_items' => 0,
            'meta' => [],
        ];
        $response = $this->createApiResponseMock(['getResponseBody']);
        $response->method('getResponseBody')->willReturn(json_encode($responseData));
        $this->api->method('request')->willReturn($response);
        $actual = $this->service->getMetaList('namespace_one');
        $this->assertEquals(json_encode($responseData), $actual->getResponseBody());
    }
}
