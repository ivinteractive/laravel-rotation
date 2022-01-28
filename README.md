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

## Usage

```php
// Usage description here
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email cs@ivinteractive.com instead of using the issue tracker.

## Credits

-   [Craig Spivack](https://github.com/ivinteractive)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
