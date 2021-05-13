<?php

namespace IvInteractive\LaravelRotation\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Bus\Batch;
use DateTime;

class ReencryptionComplete extends Notification implements ShouldQueue
{
    use Queueable;

    public $batch;

    public function __construct(array $batch)
    {
        $this->batch = $batch;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Reencryption complete!')
                    ->line('The reencryption job has finished processing in '.$this->duration().'.');
    }

    /**
     * Get a text representation of the duration to process the reenryption batch.
     * @return string
     */
    protected function duration() : string
    {
        $finished = new DateTime($this->batch['finishedAt']);
        $created = new DateTime($this->batch['createdAt']);

        $diff = $finished->diff($created);

        $time = [
            'days' => (int) $diff->format('%a'),
            'hours' => (int) $diff->format('%h'),
            'minutes' => (int) $diff->format('%i'),
            'seconds' => (int) $diff->format('%s'),
        ];

        return (new \Illuminate\Support\Collection($time))
                    ->filter(function ($value) { return $value > 0; })
                    ->map(function ($value, $label) {
                        return $value.' '.\Illuminate\Support\Str::plural($label, $value);
                    })
                    ->join(', ', ', and ');
    }
}