<?php

namespace Tests\Unit\Reindexer;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Reindexer\Client\Api;
use Reindexer\Response;

abstract class BaseTest extends TestCase {
    public function createApiMock(array $methods) {
        return $this->getMockBuilder(Api::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    public function createApiResponseMock(array $methods) {
        return $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    public function createGuzzleClient(string $baseUri, array $queue = []) {
        return new Client([
            'handler' => new MockHandler($queue),
            'base_uri' => $baseUri,
        ]);
    }

    protected function getContentsFromStream(StreamInterface $stream): string {
        $stream->rewind();

        return $stream->getContents();
    }
}
