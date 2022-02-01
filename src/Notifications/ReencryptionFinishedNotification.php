<?php

namespace IvInteractive\Rotation\Notifications;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReencryptionFinishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $batchData;

    public function __construct(array $batchData)
    {
        $this->batchData = $batchData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return config('rotation.notification.channels');
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
                    ->subject('Reencryption complete!')
                    ->line('The reencryption job has finished processing in '.$this->duration().'.');
    }

    /**
     * Get a text representation of the duration to process the reenryption batch data.
     * @return string
     */
    protected function duration(): string
    {
        $finished = new DateTime($this->batchData['finishedAt']);
        $created = new DateTime($this->batchData['createdAt']);

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
