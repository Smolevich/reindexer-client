<?php

namespace Reindexer\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\TransferStats;
use Reindexer\LoggerInterface;
use Reindexer\Response;

class Api extends BaseApi
{
    protected $client;
    protected $info;
    protected $logger;
    protected $error;

    public function __construct(string $host, array $config = [])
    {
        parent::__construct($host);
        $this->client = new Client($config);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function request(string $method, string $uri, string $body = null, array $headers = []): Response
    {
        $instance = $this;
        $request = new Request($method, $this->host . $uri, $headers);

        if ($body) {
            $stream = Utils::streamFor($body);
            $request = $request->withBody($stream);
        }

        $apiResponse = new Response();

        try {
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

            $apiResponse->setRequest($request)
                ->setResponse($response)
                ->setInfo($this->info)
                ->setError($this->error);

            if ($this->logger) {
                $this->logger->logResponse($apiResponse);
            }
        } catch (GuzzleException $e) {
            $apiResponse->setError($e->getMessage());
        }

        return $apiResponse;
    }
}
