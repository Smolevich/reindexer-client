<?php

namespace Reindexer;

class Response {
    protected $requestHeaders;
    protected $responseHeaders;
    protected $requestParams;
    protected $info;
    protected $responseBody;
    protected $error;

    public function getRequestHeaders() {
        return $this->requestHeaders;
    }

    public function setRequestHeaders(array $requestHeaders): self {
        $this->requestHeaders = $requestHeaders;

        return $this;
    }

    public function getResponseHeaders(): array {
        return $this->responseHeaders;
    }

    public function setResponseHeaders(array $responseHeaders): self {
        $this->responseHeaders = $responseHeaders;

        return $this;
    }

    public function getInfo(): array {
        return $this->info;
    }

    public function setInfo(array $info): self {
        $this->info = $info;

        return $this;
    }

    public function getResponseBody(): string {
        return $this->responseBody;
    }

    public function setResponseBody(string $responseBody): self {
        $this->responseBody = $responseBody;

        return $this;
    }

    public function getError(): string {
        return $this->error ?? '';
    }

    public function setError(?string $error): self {
        $this->error = $error;

        return $this;
    }

    public function getRequestParams(): string {
        return $this->requestParams;
    }

    public function setRequestParams(string $requestParams): self {
        $this->requestParams = $requestParams;

        return $this;
    }
}
