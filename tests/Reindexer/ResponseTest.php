<?php

namespace Reindexer;

use Tests\Reindexer\BaseTest;

class ResponseTest extends BaseTest {
    public function setUp() {
        $this->response = new Response();
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
}
