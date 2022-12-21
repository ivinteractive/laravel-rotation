<?php

namespace IvInteractive\Rotation\Tests;

use Illuminate\Encryption\Encrypter;
use IvInteractive\Rotation\Rotater;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $environmentKey;

    public function setUp(): void
    {
        parent::setUp();
        $this->setEnvironmentKey(env('APP_KEY'));
    }

    public function tearDown(): void
    {
        $this->artisan('config:clear');
        $this->resetCipher();
        parent::tearDown();
    }

    protected function setEnvironmentKey(string $applicationKey): void
    {
        $this->environmentKey = $applicationKey;
        file_put_contents(app()->environmentFilePath(), 'APP_KEY='.$this->environmentKey.PHP_EOL);
    }

    protected function resetCipher()
    {
        $configPath = app()->configPath('app.php');
        $contents = file_get_contents($configPath);

        file_put_contents($configPath, preg_replace(
            '/\'cipher\'(\s*)\=\>(\s*)\'(.*)\'/',
            '\'cipher\' => \'AES-256-CBC\'',
            $contents,
        ));
    }

    protected function getPackageProviders($app)
    {
        return [
            \IvInteractive\Rotation\Tests\Resources\TestingServiceProvider::class,
            \IvInteractive\Rotation\RotationServiceProvider::class,
        ];
    }

    protected function makeRotater(bool $setColumnIdentifier=true): Rotater
    {
        $oldKey = Encrypter::generateKey(config('rotation.cipher.old', config('app.cipher')));
        $newKey = Encrypter::generateKey(config('rotation.cipher.new', config('app.cipher')));

        $rotater = new Rotater('base64:'.base64_encode($oldKey), 'base64:'.base64_encode($newKey));

        if ($setColumnIdentifier) {
            $rotater->setColumnIdentifier('users.id.dob');
        }

        return $rotater;
    }

    protected function makeEncrypter($key, string $config='app.cipher')
    {
        return new Encrypter($key, config($config));
    }

    protected function laravelVersion(): int
    {
        return (int) explode('.', app()->version())[0];
    }
}
