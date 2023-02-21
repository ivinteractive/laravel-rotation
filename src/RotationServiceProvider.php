<?php

namespace IvInteractive\Rotation;

use Illuminate\Support\ServiceProvider;
use IvInteractive\Rotation\Contracts\RotatesApplicationKey;

class RotationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'rotation');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('rotation.php'),
            ], 'rotation.config');

            $this->publishes([
                __DIR__.'/../lang' => lang_path('vendor/rotation'),
            ], 'rotation.lang');

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

        $this->app->bind(RotatesApplicationKey::class, config('rotation.rotater_class'));
    }
}
