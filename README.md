[![Travis](https://img.shields.io/travis/SzuniSOFT/azure-laravel.svg?style=for-the-badge)](https://travis-ci.com/SzuniSOFT/azure-laravel)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/szunisoft/azure-laravel.svg?style=for-the-badge)](http://php.net/releases/7_1_0.php)

[![Packagist](https://img.shields.io/packagist/dt/szunisoft/azure-laravel.svg?style=for-the-badge)](https://packagist.org/packages/szunisoft/azure-laravel)
[![license](https://img.shields.io/github/license/szunisoft/azure-laravel.svg?style=for-the-badge)](https://github.com/SzuniSOFT/azure-laravel)





# Laravel Azure
This package is a continuously developed package which provides a full Azure integration for Laravel offering the following drivers / adapters.

- [Storage](./docs/storage.md#storage)
- [Queue](./docs/queue.md#queue)

# Download
```
composer require szunisoft/azure-laravel
```

# Configuration
Export the package configuration:
```
php artisan vendor:publish --tag=config --provider="SzuniSoft\Azure\Laravel\Providers\LaravelServiceProvider"
```

# Testing
The project uses phpunit and mockery. See **composer.json** for further details.

```
vendor/bin/phpunit
```

# Changelog

## 1.0.0 (2018-06-17)
### added
- Blob storage driver support
- File storage driver support
- Storage account Queue driver