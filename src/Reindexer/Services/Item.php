<?php

namespace Reindexer\Services;

use Reindexer\BaseService;
use Reindexer\Response;

class Item extends BaseService {
    protected $database;
    protected $namespace;

    public function getDatabase(): string {
        return $this->database ?? '';
    }

    public function setDatabase(string $database): void {
        $this->database = $database;
    }

    public function getNamespace(): string {
        return $this->namespace ?? '';
    }

    public function setNamespace(string $namespace): void {
        $this->namespace = $namespace;
    }

    /**
     * @param array $precepts
     *
     * @return string
     */
    protected function preparePercepts(array $precepts): string {
        $result = [];
        foreach ($precepts as $k => $v) {
            $result[] = 'precepts=' . $this->urlencode("{$k}={$v}");
        }

        return implode('&', $result);
    }

    /**
     * @param string $str
     *
     * @return string
     * @todo Move to separate helper class
     */
    public function urlencode(string $str): string {
        return str_ireplace(
            ['%3B', '%3A', '%40', '%24', '%21', '%2A', '%28', '%29', '%2C', '%2F'],
            [';', ':', '@', '$', '!', '*', '(', ')', ',', '/'],
            \urlencode($str)
        );
    }

    /**
     * This method will INSERT documents to namespace, by their primary keys.
     * @param array $data Single document or Array of documents
     * @param array $precepts Example: ['id' => 'serial()']
     *
     * @return Response
     */
    public function add(array $data = [], array $precepts = []): Response {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->getDatabase(),
            $this->getNamespace()
        );

        if ($precepts) {
            $uri .= '?' . $this->preparePercepts($precepts);
        }

        return $this->client->request(
            'POST',
            $uri,
            json_encode($data),
            $this->defaultHeaders
        );
    }

    public function update(array $data = []): Response {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->getDatabase(),
            $this->getNamespace()
        );

        return $this->client->request(
            'PUT',
            $uri,
            json_encode($data),
            $this->defaultHeaders
        );
    }

    public function delete(array $data = []): Response {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->getDatabase(),
            $this->getNamespace()
        );

        return $this->client->request(
            'DELETE',
            $uri,
            json_encode($data),
            $this->defaultHeaders
        );
    }

    /**
     * @param int $limit Maximum count of returned items
     * @param int $offset Offset of first returned item
     * @param string $sortField Sort Field
     * @param string $sortOrder Sort Order
     * @param string $filter Filter with SQL syntax, e.g: field1 = 'v1' AND field2 > 'v2'
     * @param array $fields List of returned fields
     *
     * @return Response
     */
    public function get(int $limit = 0, int $offset = 0, string $sortField = '', string $sortOrder = '', string $filter = '', array $fields = []): Response {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->getDatabase(),
            $this->getNamespace()
        );
        $params = [];

        if ($limit) {
            $params['limit'] = $limit;
        }

        if ($offset) {
            $params['offset'] = $offset;
        }

        if ($sortField) {
            $params['sort_field'] = $sortField;
        }

        if ($sortOrder) {
            $params['sort_order'] = $sortOrder;
        }

        if (!empty($fields)) {
            $params['fields'] = implode(',', $fields);
        }

        if ($params) {
            $uri .= '?' . http_build_query($params);
        }

        if (!empty($filter)) {
            $uri .= ($params ? '&' : '?') . 'filter=' . $this->urlencode($filter);
        }

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }
}
