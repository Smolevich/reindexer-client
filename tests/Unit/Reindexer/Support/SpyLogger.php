<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Support;

use Reindexer\LoggerInterface;
use Reindexer\Response;

final class SpyLogger implements LoggerInterface
{
    /** @var Response[] */
    public array $logged = [];

    public function logResponse(Response $response): void
    {
        $this->logged[] = $response;
    }
}
