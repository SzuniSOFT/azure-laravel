<?php


namespace SzuniSoft\Azure\Laravel\Storage;


use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;

abstract class AbstractAzureStorage implements AdapterInterface {
    use NotSupportingVisibilityTrait;

    /**
     * @var string[]
     */
    protected static $metaOptions = [
        'CacheControl',
        'ContentType',
        'Metadata',
        'ContentLanguage',
        'ContentEncoding',
    ];

    /**
     * @return mixed
     */
    abstract function getAzure();

    /**
     * @param Config $config
     * @return string
     * @return mixed
     */
    abstract protected function getOptionsFromConfig(Config $config);

    /**
     * @param $path
     * @param $contents
     * @param Config $config
     * @return array|false
     */
    abstract protected function upload($path, $contents, Config $config);

    /**
     * @param $path
     * @param bool $isDir
     * @return string
     */
    protected function normalizePath($path, $isDir = false)
    {

        $path = Util::normalizePath($path);

        if ($isDir) {
            $path = rtrim($path, '/') . '/';
        }
        return $path;
    }

}