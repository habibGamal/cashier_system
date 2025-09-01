<?php

namespace Tests\Unit;

use Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Providers\OrderServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register the OrderServiceProvider for unit tests
        $this->app->register(OrderServiceProvider::class);

        // Run migrations
        $this->artisan('migrate');
    }
}
