<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must not depend on the client codes configured in the local .env.
        config(['moncolis.client_app_codes' => []]);
        // No external routing service during tests (straight line × factor).
        config(['moncolis.delivery.routing' => 'straight']);
    }
}
