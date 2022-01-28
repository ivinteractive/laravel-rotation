<?php

namespace IvInteractive\Rotation\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use IvInteractive\Rotation\Tests\Resources\User;

class RotaterTest extends \IvInteractive\Rotation\Tests\TestCase
{
    use DatabaseMigrations;

    private $rotater;

    public function setUp(): void
    {
        parent::setUp();
        $this->rotater = $this->makeRotater();
    }

    public function testRotaterTable()
    {
        $this->assertSame('users', $this->rotater->getTable());
    }

    public function testRotaterPrimaryKey()
    {
        $this->assertSame('id', $this->rotater->getPrimaryKey());
    }

    public function testRotaterColumn()
    {
        $this->assertSame('dob', $this->rotater->getColumn());
    }

    public function testDoesNotReencryptTwice()
    {
        $oldEncrypter = $this->rotater->getOldEncrypter();

        $user = User::factory()->create([
            'dob' => $oldEncrypter->encrypt('2000-01-01'),
        ]);

        $reencrypted = $this->rotater->reencrypt($user->dob);
        $rereencrypted = $this->rotater->reencrypt($reencrypted);

        $this->assertSame($reencrypted, $rereencrypted);
    }

    public function testThrowsDecryptException()
    {
        $this->expectException(\Illuminate\Contracts\Encryption\DecryptException::class);

        $user = User::factory()->create([
            'dob' => '2000-01-01',
        ]);

        $this->rotater->reencrypt($user->dob);
    }

    public function testBatchConnectionConfiguration()
    {
        $connection = 'other-connection';

        config(['rotation.connection' => $connection]);

        $batch = $this->rotater->makeBatch();

        $this->assertSame($connection, $batch->options['connection']);
    }

    public function testBatchQueueConfiguration()
    {
        $queue = 'other-queue';

        config(['rotation.queue' => $queue]);

        $batch = $this->rotater->makeBatch();

        $this->assertSame($queue, $batch->options['queue']);
    }
}
