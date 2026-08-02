<?php

declare(strict_types=1);

namespace Reindexer;

interface LoggerInterface
{
    public function logResponse(Response $response): void;
}
