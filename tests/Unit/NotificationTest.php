<?php

namespace IvInteractive\Rotation\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use IvInteractive\Rotation\Tests\Resources\User;
use IvInteractive\Rotation\Notifications\ReencryptionComplete;

class NotificationTest extends \IvInteractive\Rotation\Tests\TestCase
{
    use DatabaseMigrations;

    const CREATED_AT = '2020-01-01 00:00:00';
    const FINISHED_AT = '2020-01-01 01:23:45';

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function testNotificationMailSubject()
    {
        $notification = new ReencryptionComplete(['createdAt'=>static::CREATED_AT, 'finishedAt'=>static::FINISHED_AT]);
        $mail = $notification->toMail($this->user);

        $this->assertSame('Reencryption complete!', $mail->subject);
    }

    public function testNotificationMailDuration()
    {
        $notification = new ReencryptionComplete(['createdAt'=>static::CREATED_AT, 'finishedAt'=>static::FINISHED_AT]);
        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('1 hour, 23 minutes and 45 seconds', $mail->introLines[0]);
    }
}
