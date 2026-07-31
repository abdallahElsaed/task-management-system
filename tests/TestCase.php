<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * A guard caches the user it resolved, and the container survives between
     * requests made inside a single test. Forgetting the guards first makes each
     * request re-authenticate from scratch, the way it would in production where
     * every request gets its own container.
     *
     * Without this, a token revoked by one request still authenticates the next.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
