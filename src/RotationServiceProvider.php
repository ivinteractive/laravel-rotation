<?php

namespace IvInteractive\Rotation;

use Illuminate\Support\ServiceProvider;
use IvInteractive\Rotation\Contracts\RotatesApplicationKey;
use IvInteractive\Rotation\Exceptions\ConfigurationException;

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

        $rotaterClass = config('rotation.rotater_class');

        $configExceptionMessage = 'The configured rotater class must be a class-string that implements IvInteractive\Rotation\Contracts\RotatesApplicationKey. (config path: rotation.rotater_class)';

        if (!is_string($rotaterClass)) {
            throw new ConfigurationException($configExceptionMessage);
        }

        $implementedClasses = class_implements($rotaterClass);

        if (!is_array($implementedClasses) || !in_array(RotatesApplicationKey::class, $implementedClasses)) {
            throw new ConfigurationException($configExceptionMessage);
        }

        $this->app->bind(RotatesApplicationKey::class, $rotaterClass);
    }
}
