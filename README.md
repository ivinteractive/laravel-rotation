# Laravel Rotater

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ivinteractive/laravel-rotation.svg)](https://packagist.org/packages/ivinteractive/laravel-rotation)
[![Total Downloads](https://img.shields.io/packagist/dt/ivinteractive/laravel-rotation.svg)](https://packagist.org/packages/ivinteractive/laravel-rotation)
![GitHub Actions](https://github.com/ivinteractive/laravel-rotation/actions/workflows/tests.yml/badge.svg)
![License](https://img.shields.io/packagist/l/ivinteractive/laravel-rotation)

Laravel Rotater is a package for reencrypting your data in case your application's encryption key becomes compromised. By running `php artisan rotation:run`, the package will generate a new application key and reencrypt all configured database columns using the the new key.

## Installation

You can install the package via composer:

```bash
composer require ivinteractive/laravel-rotation
```

Publish the configuration file:

```php
php artisan vendor:publish --tag=rotation.config
```

The configuration file will contain the following:

```php
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
     * The key rotation command creates a job batch for each column to be reencrypted
     * and chunks the records into separate jobs. This value controls the number of
     * records processed in each job.
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
```

## Usage

The key rotation command will generate a new application key, set the existing application key as the old key in the configuration, and push the batched reencryption jobs to the queue:
```
php artisan rotation:run {--horizon} {--force}
```
The `--horizon` option will make the call to `horizon:terminate` instead of `queue:restart` to make sure that the queued jobs use the recached config.

The `--force` option will skip a confirmation step that comes before making any changes to the config or pushing any jobs to the queue.


### Testing

```
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email support@ivinteractive.com instead of using the issue tracker.

## Credits

-   [Craig Spivack](https://github.com/ivinteractive)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.