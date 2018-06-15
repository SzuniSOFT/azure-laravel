<?php


namespace SzuniSoft\Azure\Laravel\Test\Queue;


use Mockery;
use SzuniSoft\Azure\Laravel\Providers\QueueServiceProvider;
use SzuniSoft\Azure\Laravel\Queue\AzureConnector;

class QueueServiceProviderTest extends \Orchestra\Testbench\TestCase {

    /** @test */
    public function it_can_boot_and_setup_driver()
    {

        $mockLaravel = Mockery::mock(\Illuminate\Foundation\Application::class);
        $mockQueueManager = Mockery::mock(\Illuminate\Queue\QueueManager::class);
        $mockConfig = Mockery::mock(\Illuminate\Cache\Repository::class);

        $mockConfig->shouldReceive('offsetGet')->with('azure.queue')->andReturn([]);

        $mockQueueManager->shouldReceive('addConnector')->withArgs(function ($driver, $closure) {
            return $driver === 'azure' && ($closure() instanceof AzureConnector);
        });

        $mockLaravel->shouldReceive('offsetGet')->with('queue')->andReturn($mockQueueManager);
        $mockLaravel->shouldReceive('offsetGet')->with('config')->andReturn($mockConfig);

        $serviceProvider = new QueueServiceProvider($mockLaravel);

        $serviceProvider->boot();
    }


}