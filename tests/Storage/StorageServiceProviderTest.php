<?php


namespace SzuniSoft\Azure\Laravel\Test\Storage;


use League\Flysystem\Filesystem;
use Mockery;
use SzuniSoft\Azure\Laravel\Providers\StorageServiceProvider;

class StorageServiceProviderTest extends TestCase {

    /** @test */
    public function it_can_boot_and_setup_drivers()
    {

        $blobConfig = [
            'protocol' => 'https',
            'account_name' => 'test_account_name',
            'key' => base64_encode('test_key'),
            'container' => 'test_container'
        ];

        $fileConfig = [
            'protocol' => 'https',
            'account_name' => 'test_account_name',
            'key' => base64_encode('test_key'),
            'share' => 'test_share'
        ];

        $mockFileSystemManager = Mockery::mock(\Illuminate\Filesystem\FilesystemManager::class);
        $mockConfig = Mockery::mock(\Illuminate\Cache\Repository::class);

        $mockConfig->shouldReceive('offsetGet')->with('azure.storage.types.blob')->andReturn([]);
        $mockConfig->shouldReceive('offsetGet')->with('azure.storage.types.file')->andReturn([]);

        $mockFileSystemManager->shouldReceive('extend')->withArgs(function ($driver, $closure) use (&$blobConfig) {
            return $driver === 'azure.blob' && ($closure($this->app, $blobConfig) instanceof \League\Flysystem\Filesystem);
        });

        $mockFileSystemManager->shouldReceive('extend')
            ->withArgs(function ($driver, $closure) use (&$fileConfig) {
            return $driver === 'azure.file' && ($closure($this->app, $fileConfig) instanceof \League\Flysystem\Filesystem);
        });

        $this->app->shouldReceive('offsetGet')->with('filesystem')->andReturn($mockFileSystemManager);
        $this->app->shouldReceive('offsetGet')->with('config')->andReturn($mockConfig);

        $serviceProvider = new StorageServiceProvider($this->app);

        $serviceProvider->boot();

    }

}