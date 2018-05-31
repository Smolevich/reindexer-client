<?php

namespace Reindexer;

use Response;

abstract class BaseClient {
    abstract function request(): Response;
}
