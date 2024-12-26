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

        $this->seed();
    }
}
