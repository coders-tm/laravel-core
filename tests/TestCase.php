<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithLaravelMigrations;
}
