<?php

namespace IvInteractive\Rotation\Tests;

use Illuminate\Encryption\Encrypter;
use IvInteractive\Rotation\Rotater;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $testKey = Encrypter::generateKey(config('app.cipher'));

        file_put_contents(base_path('.env'), 'APP_KEY='.env('APP_KEY').PHP_EOL);
    }

    public function tearDown(): void
    {
        $this->artisan('config:clear');
        parent::tearDown();
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
        $oldKey = Encrypter::generateKey(config('app.cipher'));
        $newKey = Encrypter::generateKey(config('app.cipher'));

        $rotater = new Rotater('base64:'.base64_encode($oldKey), 'base64:'.base64_encode($newKey));

        if ($setColumnIdentifier) {
            $rotater->setColumnIdentifier('users.id.dob');
        }

        return $rotater;
    }

    protected function makeEncrypter($key)
    {
        return new Encrypter($key, config('app.cipher'));
    }
}
