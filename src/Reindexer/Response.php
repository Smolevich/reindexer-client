<?php

namespace Reindexer;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response {
    protected $response;
    protected $responseContents = '';
    protected $request;
    protected $info;
    protected $error;

    public function setRequest(Request $request): self {
        $this->request = $request;

        return $this;
    }

    public function getRequest(): Request {
        return $this->request;
    }

    public function setResponse(ResponseInterface $response): self {
        $this->response = $response;

        return $this;
    }

    public function getResponse() : ResponseInterface {
        return $this->response;
    }

    public function getRequestHeaders(): array {
        return $this->getRequest()->getHeaders();
    }

    public function getResponseHeaders(): array {
        return $this->getResponse()->getHeaders();
    }

    public function getInfo(): array {
        return $this->info;
    }

    public function setInfo(array $info): self {
        $this->info = $info;

        return $this;
    }

    public function getResponseBody(): string {
        if (empty($this->responseContents)) {
            $stream = $this->getResponse()->getBody();
            $this->responseContents = $this->getContentsFromStream($stream);
        }

        return $this->responseContents;
    }

    private function getContentsFromStream(StreamInterface $stream): string {
        $stream->rewind();

        return $stream->getContents();
    }

    public function getDecodedResponseBody(bool $isAssoc = false): mixed {
        return json_decode($this->getResponseBody(), $isAssoc) ?? [];
    }

    public function getError(): string {
        return $this->error ?? '';
    }

    public function setError(?string $error): self {
        $this->error = $error;

        return $this;
    }

    public function getCode(): int {
        return $this->getInfo()['http_code'] ?? $this->getResponse()->getStatusCode() ?? 0;
    }

    public function getRequestParams(): string {
        return $this->getRequest()->getBody()->__toString();
    }
}
