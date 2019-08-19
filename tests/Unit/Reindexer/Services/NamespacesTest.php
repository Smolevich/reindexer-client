<?php
namespace Tests\Reindexer\Services;

use Reindexer\Services\Namespaces;
use Tests\Reindexer\BaseTest;

class NamespacesTest extends BaseTest {
    public function setUp() {
        $this->api = $this->createApiMock(['request']);
        $this->service = new Namespaces($this->api);
    }

    public function testGetDatabase() {
        $expected = 'database';
        $this->service->setDatabase($expected);
        $this->assertEquals($expected, $this->service->getDatabase());
    }

    public function testGetList() {
        $responseData = [
            'items' => [
                [
                    'name' => 'namespace_one'
                ],
                [
                    'name' => 'namespace_two'
                ]
            ]
        ];
        $response = $this->createApiResponseMock(['getResponseBody']);
        $response->method('getResponseBody')->willReturn(json_encode($responseData));
        $this->api->method('request')->willReturn($response);
        $actual = $this->service->getList();
        $this->assertEquals(json_encode($responseData), $actual->getResponseBody());
    }
}
