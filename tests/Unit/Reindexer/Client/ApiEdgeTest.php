<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use Reindexer\Client\Api;
use Reindexer\Client\BaseApi;
use Reindexer\Response;
use Tests\Unit\Reindexer\BaseTest;
use Tests\Unit\Reindexer\Support\SpyLogger;

#[CoversClass(Api::class)]
#[CoversClass(BaseApi::class)]
class ApiEdgeTest extends BaseTest
{
    private const HOST = 'http://reindexer:9088';

    private array $history = [];

    private function apiWithQueue(array $queue): Api
    {
        $this->history = [];
        $api = new Api(self::HOST);
        $stack = HandlerStack::create(new MockHandler($queue));
        $stack->push(Middleware::history($this->history));
        $api->setClient(new Client(['handler' => $stack]));

        return $api;
    }

    public function testRequestForwardsBodyAndHeaders(): void
    {
        $api = $this->apiWithQueue([new GuzzleResponse(200, [], '{}')]);

        $api->request(
            'POST',
            '/api/v1/db',
            '{"name":"db"}',
            ['Content-Type' => 'application/json;charset=utf-8', 'X-Test' => 'yes']
        );

        $sent = $this->history[0]['request'];
        $this->assertSame('POST', $sent->getMethod());
        $this->assertSame(self::HOST . '/api/v1/db', (string) $sent->getUri());
        $this->assertSame('{"name":"db"}', (string) $sent->getBody());
        $this->assertSame('yes', $sent->getHeaderLine('X-Test'));
        $this->assertSame('application/json;charset=utf-8', $sent->getHeaderLine('Content-Type'));
    }

    public function testRequestWithoutBodySendsEmptyStream(): void
    {
        $api = $this->apiWithQueue([new GuzzleResponse(200, [], '{}')]);

        $api->request('GET', '/api/v1/db');

        $this->assertSame('', (string) $this->history[0]['request']->getBody());
    }

    public function testConnectErrorProducesResponseWithErrorOnly(): void
    {
        $api = $this->apiWithQueue([
            new ConnectException(
                'cURL error 7: Failed to connect to reindexer port 9088',
                new Request('GET', self::HOST . '/api/v1/db')
            ),
        ]);

        $response = $api->request('GET', '/api/v1/db');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('cURL error 7', $response->getError());
    }

    public function testHttpErrorStatusIsCaughtAndReportedAsError(): void
    {
        // http_errors is enabled by default: 500 becomes a ServerException
        $api = $this->apiWithQueue([
            new GuzzleResponse(500, [], '{"description":"internal error"}'),
        ]);

        $response = $api->request('GET', '/api/v1/db');

        $this->assertStringContainsString('500', $response->getError());
    }

    public function testNon2xxIsReturnedAsResponseWhenHttpErrorsDisabled(): void
    {
        $api = new Api(self::HOST);
        $api->setClient(new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new GuzzleResponse(404, [], '{"success":false,"response_code":404,"description":"Namespace not found"}'),
            ])),
            'http_errors' => false,
        ]));

        $response = $api->request('GET', '/api/v1/db/missing/namespaces/nope');

        $this->assertSame('', $response->getError());
        $this->assertSame(404, $response->getCode());
        $this->assertSame(
            'Namespace not found',
            $response->getDecodedResponseBody(true)['description']
        );
    }

    public function testEmptyResponseBodyIsHandled(): void
    {
        $api = $this->apiWithQueue([new GuzzleResponse(200, [], '')]);

        $response = $api->request('GET', '/api/v1/db');

        $this->assertSame('', $response->getResponseBody());
        $this->assertSame([], $response->getDecodedResponseBody(true));
    }

    public function testLoggerReceivesTheResponse(): void
    {
        $api = $this->apiWithQueue([new GuzzleResponse(200, [], '{"ok":true}')]);

        $logger = new SpyLogger();
        $api->setLogger($logger);

        $response = $api->request('GET', '/api/v1/db');

        $this->assertCount(1, $logger->logged);
        $this->assertSame($response, $logger->logged[0]);
    }

    public function testLoggerIsNotCalledOnTransportError(): void
    {
        $api = $this->apiWithQueue([
            new ConnectException('no route to host', new Request('GET', self::HOST)),
        ]);

        $logger = new SpyLogger();
        $api->setLogger($logger);

        $api->request('GET', '/api/v1/db');

        $this->assertSame([], $logger->logged);
    }

    public function testHostConcatenationWithUri(): void
    {
        $api = $this->apiWithQueue([new GuzzleResponse(200, [], '{}')]);
        $api->setHost('http://other:9088');

        $api->request('GET', '/path');

        $this->assertSame('http://other:9088/path', (string) $this->history[0]['request']->getUri());
    }

    public function testConstructorPassesConfigToGuzzle(): void
    {
        $api = new Api(self::HOST, ['timeout' => 3.5]);

        $this->assertSame(3.5, $api->getClient()->getConfig('timeout'));
    }
}
