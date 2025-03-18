<?php

namespace IvInteractive\Rotation\Notifications;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use IvInteractive\Rotation\Exceptions\ConfigurationException;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReencryptionFinishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param array<string, mixed> $batchData */
    public function __construct(public array $batchData){}

    /**
     * Get the notification's delivery channels.
     * @param  object  $notifiable
     * @return array<mixed>
     */
    public function via(object $notifiable): array
    {
        $channels = config('rotation.notification.channels', []);

        if (!is_array($channels)) {
            throw new ConfigurationException('The notification channels for the ReencryptionFinishedNotification must be an array. (config path: rotation.notification.channels)');
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     * @param  object  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        $duration = $this->duration();

        if (strlen($duration)) {
            $body = trans('rotation::notification.body', ['duration' => $duration]);
        } else {
            $body = trans('rotation::notification.body-no-duration');
        }

        return (new MailMessage())
                    ->subject(trans('rotation::notification.subject'))
                    ->line($body);
    }

    /**
     * Get a text representation of the duration to process the re-enryption batch data.
     * @return string
     */
    protected function duration(): string
    {
        $finishedAtString = $this->batchData['finishedAt'];
        $createdAtString = $this->batchData['createdAt'];

        if (!is_string($finishedAtString) || !is_string($createdAtString)) {
            return '';
        }

        $finished = new DateTime($finishedAtString);
        $created = new DateTime($createdAtString);

        $diff = $finished->diff($created);

        $time = [
            'day' => (int) $diff->format('%a'),
            'hour' => (int) $diff->format('%h'),
            'minute' => (int) $diff->format('%i'),
            'second' => (int) $diff->format('%s'),
        ];

        return (new \Illuminate\Support\Collection($time))
                    ->filter(function ($value) {
                        return $value > 0;
                    })
                    ->map(function ($value, $label) {
                        return $value.' '.\Illuminate\Support\Str::plural($label, $value);
                    })
                    ->join(', ', ' and ');
    }
}
