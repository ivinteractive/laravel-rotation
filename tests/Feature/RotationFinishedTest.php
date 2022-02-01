<?php

namespace IvInteractive\Rotation\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use IvInteractive\Rotation\Events\ReencryptionFinished;
use IvInteractive\Rotation\Notifications\Notifiable;
use IvInteractive\Rotation\Notifications\ReencryptionCompleteNotification;
use IvInteractive\Rotation\Tests\Resources\User;

class RotationFinishedTest extends \IvInteractive\Rotation\Tests\TestCase
{
    use DatabaseMigrations;

    public const EMAIL_ADDRESS = 'jane.doe@example.org';
    public const SLACK_WEBHOOK_URL = 'https://hooks.slack.com/services/01234/4567/890';

    protected $rotater;
    protected $batch;

    public function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'database']);
        config(['rotation.columns' =>  ['users.id.dob']]);
        config(['rotation.notification.recipient.mail' => static::EMAIL_ADDRESS]);
        config(['rotation.notification.recipient.slack' => static::SLACK_WEBHOOK_URL]);
        touch(base_path('.env'));

        User::factory()->count(5)->create();

        $this->rotater = $this->makeRotater();
        $this->rotater->setColumnIdentifier('users.id.dob');
        $this->batch = $this->rotater->makeBatch();
        $this->rotater->rotate($this->batch);
    }

    public function testNotificationSentViaMail()
    {
        Notification::fake();

        config(['rotation.notification.channels' => ['mail']]);

        $batch = $this->batch->dispatch();
        $this->rotater::finish($batch);

        Notification::assertSentTo(
            new Notifiable(),
            ReencryptionFinishedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routeNotificationForMail() === static::EMAIL_ADDRESS;
            }
        );
    }

    public function testNotificationSentViaSlack()
    {
        Notification::fake();

        config(['rotation.notification.channels' => ['slack']]);

        $batch = $this->batch->dispatch();
        $this->rotater::finish($batch);

        Notification::assertSentTo(
            new Notifiable(),
            ReencryptionFinishedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routeNotificationForSlack() === static::SLACK_WEBHOOK_URL;
            }
        );
    }

    public function testEventDispatched()
    {
        Event::fake();

        config(['rotation.notification.channels' => ['slack']]);

        $batch = $this->batch->dispatch();
        $this->rotater::finish($batch);

        Event::assertDispatched(ReencryptionFinished::class);
    }
}
