<?php


namespace SzuniSoft\Azure\Laravel\Providers;


use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use SzuniSoft\Azure\Laravel\Storage\AzureBlobStorage;
use SzuniSoft\Azure\Laravel\Storage\AzureFileStorage;

class StorageServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->registerAzureBlobDriver();
        $this->registerAzureFileDriver();;
    }

    protected function registerAzureBlobDriver()
    {

        /**  @var FilesystemManager $manager */
        $manager = $this->app['filesystem'];

        $manager->extend('azure.blob', function ($app, $config) {

            $connectionString = isset($config['connection_string'])
                ?  $config['connection_string']
                : 'DefaultEndpointsProtocol=' . $config['protocol'] . ';AccountName=' . $config['accountname'] . ';AccountKey=' . $config['key'];

            $client = \MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobService($connectionString);
            return new Filesystem(new AzureBlobStorage($client, $config['container']), $config);
        });

    }

    protected function registerAzureFileDriver()
    {

        /**  @var FilesystemManager $manager */
        $manager = $this->app['filesystem'];

        $manager->extend('azure.file', function ($app, $config) {

            $connectionString = isset($config['connection_string'])
                ?  $config['connection_string']
                : 'DefaultEndpointsProtocol=' . $config['protocol'] . ';AccountName=' . $config['accountname'] . ';AccountKey=' . $config['key'];

            $client = \MicrosoftAzure\Storage\File\FileRestProxy::createFileService($connectionString);
            return new Filesystem(new AzureFileStorage($client, $config['share']), $config);
        });

    }

}