<?php

namespace IvInteractive\Rotation;

use Illuminate\Support\ServiceProvider;

class RotationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('rotation.php'),
            ], 'config');

            // Registering package commands.
            $this->commands([
                \IvInteractive\Rotation\Console\Commands\RotateKey::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'rotation');

        $this->app->bind(Rotater::class, config('rotation.rotater_class'));
    }
}