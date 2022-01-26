<?php

namespace IvInteractive\Rotation\Notifications;

use Illuminate\Notifications\Notifiable as NotifiableTrait;

class Notifiable
{
    use NotifiableTrait;

    /**
     * @return string|null
     */
    public function routeNotificationForMail()
    {
        return config('rotation.notification.recipient.mail');
    }

    /**
     * @return string|null
     */
    public function routeNotificationForSlack()
    {
        return config('rotation.notification.recipient.slack');
    }

    public function getKey(): string
    {
        return static::class;
    }
}
