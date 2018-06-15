<?php


namespace SzuniSoft\Azure\Laravel\Providers;



use Illuminate\Support\ServiceProvider;

class LaravelServiceProvider extends ServiceProvider {

    public function boot() {

        /* Register Azure Queue Service Provider */
        $this->app->register(QueueServiceProvider::class);

        /* Register Azure Storage Service Provider  */
        $this->app->register(StorageServiceProvider::class);

    }

    public function register() {

        $this->publishes([
            __DIR__ . "/../config/config.php" => config_path('azure')
        ], 'config');

    }

}