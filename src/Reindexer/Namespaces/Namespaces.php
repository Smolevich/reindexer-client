<?php

namespace Reindexer\Namespaces;

use Reindexer\Entity;

class Namespaces extends Entity {

    public function getList(string $database, string $sortOrder = 'asc') {
        $uri = sprintf('/api/%s/db/%s/namespaces', $this->version, $database);

        if (!empty($sortOrder)) {
            $uri .= '?sort_order='. $sortOrder;
        }

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }
}