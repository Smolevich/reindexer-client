<?php

declare(strict_types=1);

namespace Tests\Feature\Reindexer;

/**
 * Namespace metadata (metabykey / metalist) against a real server.
 */
class MetaTest extends FeatureCase
{
    private string $ns = 'meta_ns';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createNamespace($this->ns, [$this->intPkIndex()]);
    }

    public function testPutAndGetMetaKey(): void
    {
        $response = $this->nsService->addMetaDataKey($this->ns, 'schema_version', '42');
        $this->assertSame(200, $response->getCode(), $response->getResponseBody());

        $body = $this->nsService->getMetaDataKey($this->ns, 'schema_version')->getDecodedResponseBody(true);
        $this->assertSame('schema_version', $body['key']);
        $this->assertSame('42', $body['value']);
    }

    public function testMetaValueSupportsUnicodeAndSpecialCharacters(): void
    {
        $value = '{"описание":"тест 🚀","quote":"\\"q\\""}';
        $this->nsService->addMetaDataKey($this->ns, 'meta_json', $value);

        $body = $this->nsService->getMetaDataKey($this->ns, 'meta_json')->getDecodedResponseBody(true);
        $this->assertSame($value, $body['value']);
    }

    public function testOverwritingMetaKeyReplacesValue(): void
    {
        $this->nsService->addMetaDataKey($this->ns, 'k', 'v1');
        $this->nsService->addMetaDataKey($this->ns, 'k', 'v2');

        $body = $this->nsService->getMetaDataKey($this->ns, 'k')->getDecodedResponseBody(true);
        $this->assertSame('v2', $body['value']);
    }

    public function testMetaListWithoutValues(): void
    {
        $this->nsService->addMetaDataKey($this->ns, 'alpha', '1');
        $this->nsService->addMetaDataKey($this->ns, 'beta', '2');

        $body = $this->nsService->getMetaList($this->ns)->getDecodedResponseBody(true);

        $this->assertSame(2, $body['total_items']);
        $keys = array_column($body['meta'], 'key');
        sort($keys);
        $this->assertSame(['alpha', 'beta'], $keys);
        $this->assertArrayNotHasKey('value', $body['meta'][0]);
    }

    public function testMetaListWithValuesSortedAndLimited(): void
    {
        foreach (['a' => '1', 'b' => '2', 'c' => '3'] as $key => $value) {
            $this->nsService->addMetaDataKey($this->ns, $key, $value);
        }

        $body = $this->nsService
            ->getMetaList($this->ns, 2, 0, 'desc', true)
            ->getDecodedResponseBody(true);

        $this->assertSame(3, $body['total_items']);
        $this->assertCount(2, $body['meta']);
        $this->assertSame('c', $body['meta'][0]['key']);
        $this->assertSame('3', $body['meta'][0]['value']);
        $this->assertSame('b', $body['meta'][1]['key']);
    }

    public function testMetaListOfEmptyNamespace(): void
    {
        $body = $this->nsService->getMetaList($this->ns)->getDecodedResponseBody(true);

        $this->assertSame(0, $body['total_items']);
        $this->assertSame([], $body['meta']);
    }
}
