<?php

namespace IvInteractive\Rotation\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use IvInteractive\Rotation\Events\ReencryptionFinished;
use IvInteractive\Rotation\Exceptions\ConfigurationException;

class SendFinishedNotification implements ShouldQueue
{
    public function handle(ReencryptionFinished $event): void
    {
        app('log')->info('Data re-encryption has been completed.');

        if (config('rotation.notification')) {
            $notifiableClass = config('rotation.notifiable');

            if (!is_string($notifiableClass)) {
                throw new ConfigurationException('The notifiable class must be a class string. (config path: rotation.notifiable)');
            }

            $notifiable = app($notifiableClass);
            $notifiable->notify(new \IvInteractive\Rotation\Notifications\ReencryptionFinishedNotification($event->batchData));
        }
    }
}
