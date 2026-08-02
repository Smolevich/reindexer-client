<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Reindexer\Response;

/**
 * Edge cases: broken server payloads, empty bodies, error propagation,
 * status code resolution.
 */
#[CoversClass(Response::class)]
class ResponseEdgeTest extends BaseTest
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();
    }

    #[DataProvider('brokenJsonProvider')]
    public function testBrokenJsonDecodesToEmptyArray(string $payload): void
    {
        $this->response->setResponse(new GuzzleResponse(200, [], $payload));

        $this->assertSame([], $this->response->getDecodedResponseBody(true));
    }

    public static function brokenJsonProvider(): array
    {
        return [
            'truncated object' => ['{"items": [1,2'],
            'plain html' => ['<html><body>502 Bad Gateway</body></html>'],
            'empty string' => [''],
            'lone null' => ['null'],
            'invalid utf8 sequence' => ["\xB1\x31"],
        ];
    }

    public function testValidJsonScalarIsReturnedAsIs(): void
    {
        $this->response->setResponse(new GuzzleResponse(200, [], '42'));

        $this->assertSame(42, $this->response->getDecodedResponseBody(true));
    }

    public function testDecodedBodyAsObjectByDefault(): void
    {
        $this->response->setResponse(new GuzzleResponse(200, [], '{"a":{"b":1}}'));

        $decoded = $this->response->getDecodedResponseBody();
        $this->assertIsObject($decoded);
        $this->assertSame(1, $decoded->a->b);
    }

    public function testUnicodeBodyRoundTrip(): void
    {
        $payload = json_encode(['name' => 'пространство_🚀'], JSON_UNESCAPED_UNICODE);
        $this->response->setResponse(new GuzzleResponse(200, [], $payload));

        $this->assertSame(
            ['name' => 'пространство_🚀'],
            $this->response->getDecodedResponseBody(true)
        );
    }

    public function testResponseBodyIsCachedAfterFirstRead(): void
    {
        $stream = Utils::streamFor('{"cached":true}');
        $this->response->setResponse(new GuzzleResponse(200, [], $stream));

        $first = $this->response->getResponseBody();
        // exhaust the underlying stream: cached value must still be returned
        $stream->close();
        $second = $this->response->getResponseBody();

        $this->assertSame('{"cached":true}', $first);
        $this->assertSame($first, $second);
    }

    public function testGetErrorDefaultsToEmptyString(): void
    {
        $this->assertSame('', $this->response->getError());
    }

    public function testSetErrorNullKeepsEmptyString(): void
    {
        $this->response->setError(null);
        $this->assertSame('', $this->response->getError());
    }

    public function testSetErrorIsReturned(): void
    {
        $this->response->setError('connection refused');
        $this->assertSame('connection refused', $this->response->getError());
    }

    public function testGetCodePrefersInfoHttpCode(): void
    {
        $this->response->setResponse(new GuzzleResponse(200));
        $this->response->setInfo(['http_code' => 502]);

        $this->assertSame(502, $this->response->getCode());
    }

    public function testGetCodeFallsBackToResponseStatus(): void
    {
        $this->response->setResponse(new GuzzleResponse(404));
        $this->response->setInfo([]);

        $this->assertSame(404, $this->response->getCode());
    }

    public function testGetCodeWithoutInfoUsesResponseStatus(): void
    {
        $this->response->setResponse(new GuzzleResponse(201));

        $this->assertSame(201, $this->response->getCode());
    }

    public function testGetInfoDefaultsToEmptyArray(): void
    {
        $this->assertSame([], $this->response->getInfo());
    }

    public function testGetRequestParamsReturnsRawBody(): void
    {
        $request = (new Request('POST', '/api/v1/db'))
            ->withBody(Utils::streamFor('{"name":"db"}'));
        $this->response->setRequest($request);

        $this->assertSame('{"name":"db"}', $this->response->getRequestParams());
    }

    public function testGetRequestParamsEmptyBody(): void
    {
        $this->response->setRequest(new Request('GET', '/api/v1/db'));

        $this->assertSame('', $this->response->getRequestParams());
    }

    public function testGetResponseHeaders(): void
    {
        $this->response->setResponse(
            new GuzzleResponse(200, ['X-Server' => 'reindexer'], '{}')
        );

        $this->assertSame(['reindexer'], $this->response->getResponseHeaders()['X-Server']);
    }

    public function testNon2xxBodyIsStillReadable(): void
    {
        $this->response->setResponse(
            new GuzzleResponse(404, [], '{"success":false,"response_code":404,"description":"Namespace not found"}')
        );

        $this->assertSame(404, $this->response->getCode());
        $decoded = $this->response->getDecodedResponseBody(true);
        $this->assertFalse($decoded['success']);
        $this->assertSame('Namespace not found', $decoded['description']);
    }

    public function testSettersAreFluent(): void
    {
        $request = new Request('GET', '/');
        $guzzleResponse = new GuzzleResponse(200);

        $this->assertSame($this->response, $this->response->setRequest($request));
        $this->assertSame($this->response, $this->response->setResponse($guzzleResponse));
        $this->assertSame($this->response, $this->response->setInfo(['http_code' => 200]));
        $this->assertSame($this->response, $this->response->setError('x'));
    }
}
