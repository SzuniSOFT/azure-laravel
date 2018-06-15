<?php


namespace SzuniSoft\Azure\Laravel\Storage;


use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use MicrosoftAzure\Storage\Blob\Internal\IBlob;
use MicrosoftAzure\Storage\Blob\Models\BlobPrefix;
use MicrosoftAzure\Storage\Blob\Models\BlobProperties;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsResult;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class AzureBlobStorage extends AbstractAzureStorage {

    /**
     * @var IBlob
     */
    protected $azure;

    /**
     * @var string
     */
    protected $container;

    /**
     * @var bool
     */
    protected $autoCreateContainer;

    /**
     * AzureStorage constructor.
     * @param IBlob $azureClient
     * @param string $container
     * @param bool $autoCreateContainer
     */
    public function __construct(IBlob $azureClient, $container, $autoCreateContainer = false)
    {
        $this->azure = $azureClient;
        $this->container = $container;
        $this->autoCreateContainer = $autoCreateContainer;
    }

    /**
     * @return IBlob
     */
    public function getAzure()
    {
        return $this->azure;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        $this->copy($path, $newpath);
        return $this->delete($path);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $this->azure->copyBlob($this->container, $newpath, $this->container, $path);
        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        $this->azure->deleteBlob($this->container, $path);
        return true;
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {

        $options = new ListBlobsOptions();
        $options->setPrefix($dirname . '/');

        /**  @var ListBlobsResult $listResults */
        $listResults = $this->azure->listBlobs($this->container, $options);

        foreach ($listResults->getBlobs() as $blob) {

            /**  @var \MicrosoftAzure\Storage\Blob\Models\Blob $blob */
            $this->azure->deleteBlob($this->container, $blob->getName());
        }

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {

        $dirname = $this->normalizePath($dirname, true);

        $this->write($dirname, ' ', $config);
        return ['path' => $dirname, 'type' => 'dir'];
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {

        try {
            $this->azure->getBlobMetadata($this->container, $path);
        } catch (ServiceException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }
            return false;
        }

        return true;
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {

        /**  @var \MicrosoftAzure\Storage\Blob\Models\GetBlobResult $blobResult */
        $blobResult = $this->azure->getBlob($this->container, $path);
        $properties = $blobResult->getProperties();
        $content = $this->streamContentsToString($blobResult->getContentStream());

        return $this->transformBlobRemoteProperties($path, $properties) + ['contents' => $content];
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {

        /**  @var \MicrosoftAzure\Storage\Blob\Models\GetBlobResult $blobResult */
        $blobResult = $this->azure->getBlob($this->container, $path);
        $properties = $blobResult->getProperties();

        return $this->transformBlobRemoteProperties($path, $properties) + ['stream' => $blobResult->getContentStream()];
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {

        if (strlen($directory)) {
            $directory = rtrim($directory, '/') . '/';
        }

        $options = new ListBlobsOptions();
        $options->setPrefix($directory);

        if (!$recursive) {
            $options->setDelimiter('/');
        }

        /**  @var ListBlobsResult $listResults */
        $listResults = $this->azure->listBlobs($this->container, $options);

        $contents = [];

        foreach ($listResults->getBlobs() as $blob) {
            $contents[] = $this->transformBlobRemoteProperties($blob->getName(), $blob->getProperties());
        }

        if (!$recursive) {
            $contents = array_merge(
                $contents,
                array_map([$this, 'transformBlobRemotePrefix'], $listResults->getBlobPrefixes())
            );
        }

        return Util::emulateDirectories($contents);
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {

        /**  @var \MicrosoftAzure\Storage\Blob\Models\GetBlobPropertiesResult $result */
        $result = $this->azure->getBlobProperties($this->container, $path);
        return $this->transformBlobRemoteProperties($path, $result->getProperties());
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the last modified time of a file as a timestamp.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @param $path
     * @param BlobProperties $properties
     * @return array
     */
    protected function transformBlobRemoteProperties($path, BlobProperties $properties)
    {

        if (substr($path, -1) === '/') {
            return ['type' => 'dir', 'path' => $path];
        }

        return [
            'path' => $path,
            'timestamp' => (int)$properties->getLastModified()->format('U'),
            'dirname' => Util::dirname($path),
            'mimetype' => $properties->getContentType(),
            'size' => $properties->getContentLength(),
            'type' => 'file'
        ];

    }

    /**
     * @param BlobPrefix $blobPrefix
     * @return array
     */
    protected function transformBlobRemotePrefix(BlobPrefix $blobPrefix)
    {
        return ['type' => 'dir', 'path' => rtrim($blobPrefix->getName(), '/')];
    }

    /**
     * @param $resource
     * @return bool|string
     */
    protected function streamContentsToString($resource)
    {
        return stream_get_contents($resource);
    }

    /**
     * @param $path
     * @param $timestamp
     * @param null $content
     * @return array
     */
    protected function normalize($path, $timestamp, $content = null)
    {

        $data = [
            'path' => $path,
            'timestamp' => (int)$timestamp,
            'dirname' => Util::dirname($path),
            'type' => 'file'
        ];

        if (is_string($content)) {
            $data['contents'] = $content;
        }

        return $data;
    }

    /**
     * @param Config $config
     * @return CreateBlobOptions
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = new CreateBlockBlobOptions();
        foreach (static::$metaOptions as $option) {
            if (!$config->has($option)) {
                continue;
            }
            call_user_func([$options, "set$option"], $config->get($option));
        }
        if ($mimetype = $config->get('mimetype')) {
            $options->setContentType($mimetype);
        }
        return $options;
    }

    /**
     * @param $path
     * @param $contents
     * @param Config $config
     * @return array
     */
    protected function upload($path, $contents, Config $config)
    {

        $path = $this->normalizePath($path);

        /**  @var \MicrosoftAzure\Storage\Blob\Models\CopyBlobResult $result */
        try {
            $result = $this->azure->createBlockBlob(
                $this->container,
                $path,
                $contents,
                $this->getOptionsFromConfig($config)
            );
        } catch (ServiceException $exception) {

            if ($exception->getCode() == 404 and $this->autoCreateContainer) {

                $this->azure->createContainer($this->container);

                $result = $this->azure->createBlockBlob(
                    $this->container,
                    $path,
                    $contents,
                    $this->getOptionsFromConfig($config)
                );
            }
            else {
                throw $exception;
            }
        }

        return $this->normalize($path, $result->getLastModified()->format('U'), $contents);
    }

}