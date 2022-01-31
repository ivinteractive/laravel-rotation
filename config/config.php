<?php

use IvInteractive\Rotation;

return [
    /**
     * The columns to be decrypted and reencrypted. The columns must be
     * an array of string with the format '{TABLENAME}.{PRIMARY_KEY}.{COLUMN_NAME}'.
     */
    'columns' => [
        // 'tablename.primary_key.column',
    ],

    /**
     * The key rotation command creates a job batch and chunks the records into separate
     * jobs. This value controls the number of records processed in each job.
     */
    'chunk_size' => 1000,

    /**
     * The old application key for the key rotater to use in decryption of old encrypted values.
     * This value is set automatically. If your config is cached, the key rotation command will
     * recache your config after appending the OLD_KEY value to your .env file.
     */
    'old_key' => env('OLD_KEY', null),

    /**
     * This class performs decryption and reencryption while processing records. A valid
     * rotation class must implement `IvInteractive\Rotation\RotaterInterface`.
     */
    'rotater_class' => Rotation\Rotater::class,

    /**
     * The class for receiving notifications after the key rotation is completed. The base
     * class includes notification routing for mail and Slack.
     */
    'notifiable' => Rotation\Notifications\Notifiable::class,

    /**
     * The queue connection that will be used for the reencryption jobs.
     */
    'connection' => 'default',

    /**
     * The queue that reencryption jobs will be pushed to.
     */
    'queue' => 'default',

    /**
     * The recipient for the notification sent by the default IvInteractive\Rotation|Rotater class.
     */
    'notification' => [
        'recipient' => [
            'mail' => 'jane.doe@example.org',
            // 'slack' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        ],
        'channels' => ['mail'],
    ],

    /**
     * Whether the application should be put in maintenance mode while the reencryption
     * is running. This is highly recommended, as users may experience errors before
     * all data has been reencrypted.
     */
    'maintenance' => env('ROTATION_MAINTENANCE', true),
];
