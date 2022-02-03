<?php

namespace IvInteractive\Rotation\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use IvInteractive\Rotation\Events\ReencryptionFinished;

class SendFinishedNotification implements ShouldQueue
{
    public function handle(ReencryptionFinished $event)
    {
        app('log')->info('Data reencryption has been completed.');

        if (config('rotation.notification')) {
            $notifiable = app(config('rotation.notifiable'));
            $notifiable->notify(new \IvInteractive\Rotation\Notifications\ReencryptionFinishedNotification($event->batchData));
        }
    }
}
