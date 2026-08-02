<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Reindexer\Client\Api;
use Reindexer\Response;

abstract class BaseTest extends TestCase
{
    public function createApiMock(array $methods): MockObject
    {
        return $this->getMockBuilder(Api::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }

    public function createApiResponseMock(array $methods): MockObject
    {
        return $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }

    public function createGuzzleClient(string $baseUri, array $queue = []): Client
    {
        return new Client([
            'handler' => new MockHandler($queue),
            'base_uri' => $baseUri,
        ]);
    }

    protected function getContentsFromStream(StreamInterface $stream): string
    {
        $stream->rewind();

        return $stream->getContents();
    }
}
