<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Support;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Utils;
use Reindexer\Client\BaseApi;
use Reindexer\Response;

/**
 * Test double for BaseApi that records every request() call and returns a
 * canned Response. Unlike a PHPUnit mock it asserts nothing by itself, which
 * lets tests inspect exact URIs, methods, bodies and headers the services build.
 */
final class RecordingApi extends BaseApi
{
    /** @var array<int, array{method: string, uri: string, body: ?string, headers: array}> */
    public array $calls = [];

    private string $nextBody = '{}';
    private int $nextStatus = 200;

    public function __construct(string $host = 'http://reindexer:9088')
    {
        parent::__construct($host);
    }

    public function willRespond(string $body, int $status = 200): self
    {
        $this->nextBody = $body;
        $this->nextStatus = $status;

        return $this;
    }

    public function request(string $method, string $uri, ?string $body = null, array $headers = []): Response
    {
        $this->calls[] = [
            'method' => $method,
            'uri' => $uri,
            'body' => $body,
            'headers' => $headers,
        ];

        $request = new Request($method, $this->host . $uri, $headers);
        if ($body !== null) {
            $request = $request->withBody(Utils::streamFor($body));
        }

        return (new Response())
            ->setRequest($request)
            ->setResponse(new GuzzleResponse($this->nextStatus, [], $this->nextBody))
            ->setInfo(['http_code' => $this->nextStatus]);
    }

    public function lastCall(): array
    {
        if ($this->calls === []) {
            throw new \LogicException('No request() calls were recorded');
        }

        return $this->calls[array_key_last($this->calls)];
    }
}
