<?php

namespace IvInteractive\Rotation\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use IvInteractive\Rotation\Notifications\ReencryptionFinishedNotification;
use IvInteractive\Rotation\Tests\Resources\User;

class NotificationTest extends \IvInteractive\Rotation\Tests\TestCase
{
    use DatabaseMigrations;

    public const CREATED_AT = '2020-01-01 00:00:00';
    public const FINISHED_AT = '2020-01-01 01:23:45';

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function testNotificationMailSubject()
    {
        $notification = new ReencryptionFinishedNotification(['createdAt'=>static::CREATED_AT, 'finishedAt'=>static::FINISHED_AT]);
        $mail = $notification->toMail($this->user);

        $this->assertSame('Re-encryption complete!', $mail->subject);
    }

    public function testNotificationMailDuration()
    {
        $notification = new ReencryptionFinishedNotification(['createdAt'=>static::CREATED_AT, 'finishedAt'=>static::FINISHED_AT]);
        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('1 hour, 23 minutes and 45 seconds', $mail->introLines[0]);
    }
}
