<?php

declare(strict_types=1);

namespace Reindexer\Exceptions;

use RuntimeException;

class GrpcException extends RuntimeException
{
    public static function fromErrorResponse(int $code, string $what): self
    {
        return new self(sprintf('Reindexer gRPC error %d: %s', $code, $what), $code);
    }

    public static function fromStatus(int $statusCode, string $details): self
    {
        return new self(sprintf('gRPC call failed with status %d: %s', $statusCode, $details), $statusCode);
    }
}
