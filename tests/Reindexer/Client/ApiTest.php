<?php

namespace Tests\Reindexer\Client;

use GuzzleHttp\Client;
use Reindexer\Client\Api;
use \PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{

    public function setUp() {
        $this->config = [
            'host' => 'reindexer:9800'
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
}
