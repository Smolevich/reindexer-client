<?php

namespace Tests\Reindexer\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Reindexer\Client\Api;
use Tests\Reindexer\BaseTest;

class ApiTest extends BaseTest {
    public function setUp() {
        $this->config = [
            'host' => 'http://reindexer:9800'
        ];
        $this->api = new Api($this->config['host']);
    }

    public function testGetHost() {
        $this->api->setHost('reindexer2:9800');
        $this->assertEquals('reindexer2:9800', $this->api->getHost());
    }

    /**
     * @expectedException \TypeError
     */
    public function testCreateApiInstanceThrowExceptionIfHostEmpty() {
        $api = new Api(null);
    }

    public function testGetClient() {
        $this->assertInstanceOf(Client::class, $this->api->getClient());
    }

    public function testRequest() {
        $expectedResponse = new Response(
            200,
            [
                'Content-Type' => ' application/json; charset=utf-8',
                'Date' => date('D, d M Y H:i:s T')
            ],
            '{total_items: 2, items: ["first_item"]}'
        );
        $queue = [$expectedResponse];
        $this->api->setClient($this->createGuzzleClient($this->config['host'], $queue));
        $apiResponse = $this->api->request('GET', '/api/db');
        $this->assertSame($expectedResponse, $apiResponse->getResponse());
        $this->assertEquals($this->config['host'].'/api/db', $apiResponse->getRequest()->getUri()->__toString());
    }
}
