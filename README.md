# Laravel Azure
This package is a continuously developed package which provides a full Azure integration for Laravel offering the following drivers / adapters.

- [Storage](./docs/storage.md#storage)
- [Queue](./docs/queue.md#queue)

# Configuration

# Testing
The project uses phpunit and mockery. See **composer.json** for further details.

```
vendor/bin/phpunit
```

# Changelog
Export the package configuration:
```
php artisan vendor:publish --tag=config --provider="SzuniSoft\Azure\Laravel\Providers\LaravelServiceProvider"
```

## 1.0.0 beta (2018-06-15)
### added
- Blob storage driver support
- File storage driver support
- Storage account Queue driver