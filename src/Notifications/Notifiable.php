<?php

namespace IvInteractive\Rotation\Notifications;

use Illuminate\Notifications\Notifiable as NotifiableTrait;
use IvInteractive\Rotation\Exceptions\ConfigurationException;

class Notifiable
{
    use NotifiableTrait;

    /**
     * @return string|null
     */
    public function routeNotificationForMail()
    {
        $mail = config('rotation.notification.recipient.mail');

        if (!is_null($mail) && !is_string($mail)) {
            throw new ConfigurationException('The mail notification recipient must be a string or left empty. (config path: rotation.notification.recipient.mail)');
        }

        return $mail;
    }

    /**
     * @return string|null
     */
    public function routeNotificationForSlack()
    {
        $slack = config('rotation.notification.recipient.slack');

        if (!is_null($slack) && !is_string($slack)) {
            throw new ConfigurationException('The Slack notification webhook must be a string or left empty. (config path: rotation.notification.recipient.slack)');
        }

        return $slack;
    }

    public function getKey(): string
    {
        return static::class;
    }
}
