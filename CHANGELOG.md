# Changelog

All notable changes to `laravel-rotation` will be documented in this file

## 0.6.0 - 2023-03-17

Code was made stricter to conform to PHPStan level 8:

- For the `RotatesApplicationKey` contract and `Rotater` class, `getTable()`, `getPrimaryKey()`, and `getColumn()` must return a string instead of ?string. A new `CouldNotParseIdentifierException` class is provided for handling identifiers that cannot be parsed. `getCount()` must return an int instead of ?int.
- Switch from using PHPStan directly to Larastan
- Update tests to handle `CouldNotParseIdentifierException`

## 0.5.0 - 2023-02-23

### What's Changed

- Added Laravel 10 support
- Dropped PHP 7.4 support
- Dropped Laravel 8 support
- Improved typing according to PHPStan recommendations

**Full Changelog**: https://github.com/ivinteractive/laravel-rotation/compare/v0.4.1...v0.5.0