<?php

namespace Tests;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

    }

    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->instance('request', Request::create('http://localhost/'));
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
