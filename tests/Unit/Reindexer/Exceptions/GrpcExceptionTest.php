<?php

declare(strict_types=1);

namespace Tests\Unit\Reindexer\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Reindexer\Exceptions\GrpcException;

#[CoversClass(GrpcException::class)]
class GrpcExceptionTest extends TestCase
{
    public function testIsARuntimeException(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new GrpcException('boom'));
    }

    public function testFromErrorResponseFormatsMessageAndKeepsCode(): void
    {
        $exception = GrpcException::fromErrorResponse(13, 'Namespace not found');

        $this->assertSame('Reindexer gRPC error 13: Namespace not found', $exception->getMessage());
        $this->assertSame(13, $exception->getCode());
    }

    public function testFromStatusFormatsMessageAndKeepsCode(): void
    {
        $exception = GrpcException::fromStatus(14, 'failed to connect to all addresses');

        $this->assertSame(
            'gRPC call failed with status 14: failed to connect to all addresses',
            $exception->getMessage()
        );
        $this->assertSame(14, $exception->getCode());
    }

    public function testEmptyDetailsAreAllowed(): void
    {
        $exception = GrpcException::fromStatus(4, '');

        $this->assertSame('gRPC call failed with status 4: ', $exception->getMessage());
        $this->assertSame(4, $exception->getCode());
    }
}
