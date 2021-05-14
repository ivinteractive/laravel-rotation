<?php

namespace IvInteractive\Rotation\Tests\Resources;

use Illuminate\Support\ServiceProvider;

class TestingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $db = database_path('testing.sqlite');

        touch($db);

        // config([
        //     'database.default' => 'sqlite',
        //     'database.connections.sqlite.database' => $db,
        // ]);
    }
}
