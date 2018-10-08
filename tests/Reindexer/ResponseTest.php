<?php

namespace Reindexer;

use Tests\Reindexer\BaseTest;

class ResponseTest extends BaseTest {
    public function setUp() {
        $this->response = new Response();
        $this->info = [
            'http_code' => 200,
            'content_type' => 'application/json; charset=utf-8'
        ];
    }

    public function testGetResponseBody() {
        $responseData = '
            {
                "items": []
            }
        ';
        $this->response->setResponseBody($responseData);
        $this->assertEquals($responseData, $this->response->getResponseBody());
    }

    public function testGetDecodedResponseBody() {
        $responseData = '
            {
                "items": []
            }
        ';
        $responseDataWithInvalidJson = '
            {
                "items": [`]
            }
        ';
        $this->response->setResponseBody($responseData);
        $expectedObject = json_decode($responseData, false);
        $this->assertAttributeEquals($expectedObject->items, 'items', $this->response->getDecodedResponseBody());
        $this->assertEquals(json_decode($responseData, true), $this->response->getDecodedResponseBody(true));
        $this->response->setResponseBody($responseDataWithInvalidJson);
        $this->assertEquals([], $this->response->getDecodedResponseBody());
    }

    public function testGetCode() {
        $this->response->setInfo($this->info);
        $this->assertEquals($this->info['http_code'], $this->response->getCode());
    }
}
