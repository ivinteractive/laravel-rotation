<?php

namespace IvInteractive\Rotation\Tests\Resources;

use Illuminate\Support\ServiceProvider;

class TestingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $db = database_path('testing.sqlite');

        touch($db);

        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');
    }
}
