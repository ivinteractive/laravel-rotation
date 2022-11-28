<?php

namespace IvInteractive\Rotation\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use IvInteractive\Rotation\Tests\Resources\User;

class RotationHorizonCommandTest extends \IvInteractive\Rotation\Tests\TestCase
{
    use DatabaseMigrations;

    public const COUNT = 5;

    public function setUp(): void
    {
        parent::setUp();

        User::factory()->count(static::COUNT)->create();

        config(['rotation.columns' => ['users.id.dob']]);
        touch(app()->environmentFilePath());
    }

    public function testRestartHorizon()
    {
        Bus::fake();

        $this->artisan('rotation:run', ['--force'=>true, '--horizon'=>true])
             ->assertExitCode(0);
    }

    protected function getPackageProviders($app)
    {
        return [
            \Laravel\Horizon\HorizonServiceProvider::class,
            \Laravel\Horizon\HorizonApplicationServiceProvider::class,
            \IvInteractive\Rotation\Tests\Resources\TestingServiceProvider::class,
            \IvInteractive\Rotation\RotationServiceProvider::class,
        ];
    }
}
