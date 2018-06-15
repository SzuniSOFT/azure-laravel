<?php


namespace SzuniSoft\Azure\Laravel\Providers;


use Illuminate\Cache\Repository;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use SzuniSoft\Azure\Laravel\Storage\AzureBlobStorage;
use SzuniSoft\Azure\Laravel\Storage\AzureFileStorage;

class StorageServiceProvider extends ServiceProvider {

    public function boot()
    {

        $this->registerAzureDriver(
            'azure.blob',
            AzureBlobStorage::class,
            \MicrosoftAzure\Storage\Blob\BlobRestProxy::class,
            'createBlobService',
            'container'
        );

        $this->registerAzureDriver(
            'azure.file',
            AzureFileStorage::class,
            \MicrosoftAzure\Storage\File\FileRestProxy::class,
            'createFileService',
            'share'
        );

    }

    /**
     * @param string $driverName
     * @param string $driverClass
     * @param string $azureClientClass
     * @param string $azureClientClassMethod
     * @param string $subjectTypeName
     */
    protected function registerAzureDriver(
        $driverName,
        $driverClass,
        $azureClientClass,
        $azureClientClassMethod,
        $subjectTypeName
    )
    {

        /**  @var FilesystemManager $manager */
        $manager = $this->app['filesystem'];

        $manager->extend($driverName, function ($app, $config) use (&$driverName, &$driverClass, &$azureClientClass, &$azureClientClassMethod, &$subjectTypeName) {

            /**  @var Repository $configRepository */
            $configRepository = $app['config'];

            $type = explode('.', $driverName)[1];

            $config = array_merge(
                [
                    'auto_create_' . $subjectTypeName => false
                ],
                $configRepository['azure.storage.types.' . $type],
                $config);

            $connectionString = isset($config['connection_string'])
                ? $config['connection_string']
                : 'DefaultEndpointsProtocol=' . $config['protocol'] . ';AccountName=' . $config['account_name'] . ';AccountKey=' . $config['key'];

            $client = call_user_func($azureClientClass . '::' . $azureClientClassMethod, $connectionString);
            return new Filesystem(new $driverClass($client, $config[$subjectTypeName], $config['auto_create_' . $subjectTypeName]), $config);
        });

        
    }


}