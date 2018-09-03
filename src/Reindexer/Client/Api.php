<?php

namespace Reindexer\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Reindexer\Response;

class Api extends BaseApi {
    protected $client;
    protected $info;
    protected $error;

    public function __construct(string $host, array $config = []) {
        parent::__construct($host);
        $this->client = new Client($config);
    }

    public function getClient(): Client {
        return $this->client;
    }

    public function request(string $method, string $uri, string $body = null, array $headers = []): Response {
        $instance = $this;
        $request = new Request($method, $this->host . $uri, $headers);

        if ($body) {
            $stream = \GuzzleHttp\Psr7\stream_for($body);
            $request = $request->withBody($stream);
        }

        $response = $this->client->send(
            $request,
            [
                'on_stats' => function (TransferStats $stats) use ($instance) {
                    $instance->info = $stats->getHandlerStats();
                    if (!$stats->hasResponse()) {
                        $instance->error = $stats->getHandlerErrorData();
                    }
                },
            ]
        );
        try {
            $requestParams = $request->getBody()->__toString();
            $body = $response->getBody()->getContents();
            $apiResponse = (new Response())
                ->setRequestHeaders($request->getHeaders())
                ->setResponseHeaders($response->getHeaders())
                ->setResponseBody($body)
                ->setInfo($this->info)
                ->setRequestParams($requestParams)
                ->setError($this->error);
        } catch (GuzzleException $e) {
            $apiResponse = (new Response())->setError();
        }

        return $apiResponse;
    }
}
