<?php

return [
    'columns' => [
        // tablename.primary_key.column,
    ],
    'chunk_size' => 1000,
    'old_key' => env('OLD_KEY', null),
    'rotater_class' => \IvInteractive\Rotation\Rotater::class,
    'notifiable' => \IvInteractive\Rotation\Notifications\Notifiable::class,
    'connection' => 'default',
    'queue' => 'default',
    'notification' => [
        'recipient' => [
            'mail' => 'jane.doe@example.org',
        ],
        'channels' => ['mail'],
    ],
];
