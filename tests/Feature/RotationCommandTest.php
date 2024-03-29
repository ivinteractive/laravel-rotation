<?php

namespace IvInteractive\Rotation\Tests\Feature;

use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use IvInteractive\Rotation\Tests\Resources\User;

class RotationCommandTest extends \IvInteractive\Rotation\Tests\TestCase
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

    public function testBatchDispatched()
    {
        Bus::fake();

        $this->artisan('rotation:run', ['--force'=>true]);

        Bus::assertBatched(function (PendingBatch $batch) {
            return Str::startsWith($batch->name, 'reencryption_') &&
                   $batch->jobs->count() === ((int) ceil(static::COUNT / config('rotation.chunk_size')));
        });
    }

    public function testBatchDispatchedSeparateJobs()
    {
        config(['rotation.chunk_size' => 1]);

        Bus::fake();

        $this->artisan('rotation:run', ['--force'=>true]);

        Bus::assertBatched(function (PendingBatch $batch) {
            return Str::startsWith($batch->name, 'reencryption_') &&
                   $batch->jobs->count() === static::COUNT;
        });
    }

    public function testCancelCommand()
    {
        $this->artisan('rotation:run')
             ->expectsConfirmation('Do you wish to continue?', 'no')
             ->assertExitCode(1);
    }

    public function testFailToSetKey()
    {
        unlink(app()->environmentFilePath());

        $this->artisan('rotation:run', ['--force'=>true])
             ->assertExitCode(1);
    }

    public function testSetsOldKey()
    {
        $this->artisan('rotation:run', ['--force'=>true]);
        $this->assertStringContainsString('OLD_KEY='.$this->environmentKey, file_get_contents(app()->environmentFilePath()));
    }

    public function testSetsMaintenanceModeSecret()
    {
        if ($this->laravelVersion() < 9) {
            $this->markTestSkipped(
                'The `expectsOutputToContain` method is not available.'
            );
        }
        config(['rotation.maintenance-secret'=>true]);
        $this->artisan('rotation:run', ['--force'=>true])
             ->expectsOutputToContain('to view the site while it is in maintenance mode.');
    }
}
