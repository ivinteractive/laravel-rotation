<?php

namespace IvInteractive\Rotation\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use IvInteractive\Rotation\Notifications\ReencryptionFinishedNotification;
use IvInteractive\Rotation\Tests\Resources\User;

class NotificationTest extends \IvInteractive\Rotation\Tests\TestCase
{
    use DatabaseMigrations;

    private CarbonImmutable $createdAt;
    private CarbonImmutable $finishedAt;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $this->createdAt = new CarbonImmutable('2020-01-01 00:00:00');
        $this->finishedAt = new CarbonImmutable('2020-01-01 01:23:45');
    }

    public function testNotificationMailSubject()
    {
        $notification = new ReencryptionFinishedNotification(['createdAt'=>$this->createdAt, 'finishedAt'=>$this->finishedAt]);
        $mail = $notification->toMail($this->user);

        $this->assertSame(trans('rotation::notification.subject'), $mail->subject);
    }

    public function testNotificationMailDuration()
    {
        $notification = new ReencryptionFinishedNotification(['createdAt'=>$this->createdAt, 'finishedAt'=>$this->finishedAt]);
        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('1 hour, 23 minutes and 45 seconds', $mail->introLines[0]);
    }

    public function testNotificationMailNoDuration()
    {
        $notification = new ReencryptionFinishedNotification(['createdAt'=>$this->createdAt, 'finishedAt'=>null]);
        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString(trans('rotation::notification.body-no-duration'), $mail->introLines[0]);
    }
}
