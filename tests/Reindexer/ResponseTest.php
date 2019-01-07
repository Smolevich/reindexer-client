<?php

namespace Reindexer;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use function GuzzleHttp\Psr7\stream_for;
use Reindexer\Response;
use Tests\Reindexer\BaseTest;

class ResponseTest extends BaseTest {
    public function setUp() {
        $this->response = new Response();
        $this->info = [
            'http_code' => 200,
            'content_type' => 'application/json; charset=utf-8'
        ];
    }

    /**
     * @dataProvider responseProvider
     */
    public function testGetResponseBody($request, $response, $decodedData) {
        $this->response->setRequest($request)
            ->setResponse($response);
        $this->assertEquals(
            $this->getContentsFromStream($response->getBody()),
            $this->response->getResponseBody()
        );
        $this->assertEquals(
            $request->getMethod(),
            $this->response->getRequest()->getMethod()
        );
        $this->assertEquals(
            $decodedData,
            $this->response->getDecodedResponseBody(true)
        );
    }

    public function testGetCode() {
        $this->response->setInfo($this->info);
        $this->assertEquals($this->info['http_code'], $this->response->getCode());
    }

    public function responseProvider() {
        return [
            [
                new Request('GET', 'api/v1/db'),
                new GuzzleResponse(200, [], '{"items": []}'),
                [
                    "items" => []
                ]
            ],
            [
                new Request(
                    'POST',
                    'api/v1/indexes',
                    ['Content-Type' => 'application/json; charset=utf-8'],
                    '{"indexes": []}'
                ),
                new GuzzleResponse(200, [], '{"success":true,"response_code":200,"description":""}'),
                [
                    'success' => true,
                    'response_code' => 200,
                    'description' => ''
                ]
            ]
        ];
    }
}
