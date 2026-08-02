<?php

declare(strict_types=1);

namespace Tests\Feature\Reindexer;

use Reindexer\Client\Api;
use Reindexer\Services\Item;

/**
 * How real server errors surface through the client: HTTP status codes,
 * error bodies, transport-level failures.
 */
class ErrorHandlingTest extends FeatureCase
{
    public function testGetMissingNamespaceReturns404(): void
    {
        $response = $this->nsService->get('missing_ns');

        $this->assertSame(404, $response->getCode());
        $body = $response->getDecodedResponseBody(true);
        $this->assertFalse($body['success']);
        $this->assertSame(404, $body['response_code']);
        $this->assertStringContainsString('not found', $body['description']);
    }

    public function testAddItemToMissingNamespaceReturns404(): void
    {
        $items = $this->itemService('missing_ns');

        $response = $items->add(['id' => 1]);

        $this->assertSame(404, $response->getCode());
        $this->assertFalse($response->getDecodedResponseBody(true)['success']);
    }

    public function testDropMissingNamespaceReturnsError(): void
    {
        $response = $this->nsService->drop('missing_ns');

        $this->assertGreaterThanOrEqual(400, $response->getCode());
        $this->assertFalse($response->getDecodedResponseBody(true)['success']);
    }

    public function testTruncateMissingNamespaceReturnsError(): void
    {
        $response = $this->nsService->truncate('missing_ns');

        $this->assertGreaterThanOrEqual(400, $response->getCode());
    }

    public function testCreateNamespaceWithInvalidIndexDefinitionFails(): void
    {
        // composite PK index without json paths pointing anywhere sane
        $response = $this->nsService->create('broken_ns', []);
        $this->assertSame(200, $response->getCode());

        // adding an item with a PK-less namespace must fail
        $items = $this->itemService('broken_ns');
        $response = $items->add(['id' => 1, 'name' => 'x']);

        $this->assertGreaterThanOrEqual(400, $response->getCode());
        $this->assertFalse($response->getDecodedResponseBody(true)['success']);
    }

    public function testMetaKeyOfMissingNamespaceReturnsError(): void
    {
        $response = $this->nsService->getMetaDataKey('missing_ns', 'key');

        $this->assertGreaterThanOrEqual(400, $response->getCode());
    }

    public function testHttpErrorsEnabledConvertsServerErrorToTransportError(): void
    {
        // default Api (http_errors=true): 404 becomes a caught exception,
        // surfaced via getError() with an empty Response otherwise
        $api = new Api((string) getenv('REINDEXER_HOST'));
        $items = new Item($api);
        $items->setDatabase($this->database);
        $items->setNamespace('missing_ns');

        $response = $items->add(['id' => 1]);

        $this->assertStringContainsString('404', $response->getError());
    }

    public function testConnectionRefusedSurfacesAsError(): void
    {
        $api = new Api('http://127.0.0.1:59999', ['timeout' => 2]);
        $items = new Item($api);
        $items->setDatabase('any');
        $items->setNamespace('any');

        $response = $items->add(['id' => 1]);

        $this->assertNotSame('', $response->getError());
    }
}
