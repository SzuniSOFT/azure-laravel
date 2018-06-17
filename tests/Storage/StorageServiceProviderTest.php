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

        $mockLaravel = Mockery::mock(\Illuminate\Foundation\Application::class);
        $mockFileSystemManager = Mockery::mock(\Illuminate\Filesystem\FilesystemManager::class);
        $mockConfig = Mockery::mock(\Illuminate\Cache\Repository::class);

        $mockConfig->shouldReceive('offsetGet')->with('azure.storage.types.blob')->andReturn([]);
        $mockConfig->shouldReceive('offsetGet')->with('azure.storage.types.file')->andReturn([]);

        $mockFileSystemManager->shouldReceive('extend')->withArgs(function ($driver, $closure) use (&$mockLaravel, &$blobConfig) {
            return $driver === 'azure.blob' && ($closure($mockLaravel, $blobConfig) instanceof \League\Flysystem\Filesystem);
        });

        $mockFileSystemManager->shouldReceive('extend')
            ->withArgs(function ($driver, $closure) use (&$mockLaravel, &$fileConfig) {
            return $driver === 'azure.file' && ($closure($mockLaravel, $fileConfig) instanceof \League\Flysystem\Filesystem);
        });

        $mockLaravel->shouldReceive('offsetGet')->with('filesystem')->andReturn($mockFileSystemManager);
        $mockLaravel->shouldReceive('offsetGet')->with('config')->andReturn($mockConfig);

        $serviceProvider = new StorageServiceProvider($mockLaravel);

        $serviceProvider->boot();

    }

}