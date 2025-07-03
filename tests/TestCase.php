<?php

namespace Coderstm\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithLaravelMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    protected function defineEnvironment($app)
    {
        // Use array cache driver for tests instead of database
        $app['config']->set('cache.default', 'array');

        // Setup testing database
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
