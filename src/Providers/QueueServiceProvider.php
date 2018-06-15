<?php


namespace SzuniSoft\Azure\Laravel\Providers;


use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use SzuniSoft\Azure\Laravel\Queue\AzureConnector;

class QueueServiceProvider extends ServiceProvider {

    public function boot() {

        /**
         * @var QueueManager $manager
         */
        $manager = $this->app['queue'];

        $manager->addConnector('azure', function () {
            return new AzureConnector($this->app['config']['azure.queue']);
        });
    }

}