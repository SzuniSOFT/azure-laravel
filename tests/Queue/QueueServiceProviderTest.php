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

        $mockQueueManager->shouldReceive('addConnector')->withArgs(function ($driver, $closure) {
            return $driver === 'azure' && ($closure() instanceof AzureConnector);
        });

        $mockLaravel->shouldReceive('offsetGet')->with('queue')->andReturn($mockQueueManager);

        $serviceProvider = new QueueServiceProvider($mockLaravel);

        $serviceProvider->boot();
    }


}