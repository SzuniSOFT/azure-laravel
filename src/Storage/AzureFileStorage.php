<?php


namespace SzuniSoft\Azure\Laravel\Storage;


use InvalidArgumentException;
use League\Flysystem\Config;
use League\Flysystem\Util;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\File\Internal\IFile;
use MicrosoftAzure\Storage\File\Models\CreateFileFromContentOptions;
use MicrosoftAzure\Storage\File\Models\Directory;
use MicrosoftAzure\Storage\File\Models\File;
use MicrosoftAzure\Storage\File\Models\FileProperties;
use MicrosoftAzure\Storage\File\Models\ListDirectoriesAndFilesOptions;
use MicrosoftAzure\Storage\File\Models\ListDirectoriesAndFilesResult;

class AzureFileStorage extends AbstractAzureStorage {

    /**
     * @var IFile
     */
    protected $azure;

    /**
     * @var string
     */
    protected $share;
    /**
     * @var bool
     */
    protected $autoCreateShare;

    /**
     * AzureFileStorage constructor.
     * @param IFile $azureClient
     * @param $share
     * @param bool $autoCreateShare
     */
    public function __construct(IFile $azureClient, $share, $autoCreateShare = false)
    {
        $this->azure = $azureClient;
        $this->share = $share;
        $this->autoCreateShare = $autoCreateShare;
    }

    /**
     * @return IFile
     */
    public function getAzure()
    {
        return $this->azure;
    }

    /**
     * @param Config $config
     * @return CreateFileFromContentOptions|string
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = new CreateFileFromContentOptions();
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

        try {

            $this->azure->createFileFromContent(
                $this->share,
                $path,
                $contents,
                $this->getOptionsFromConfig($config)
            );
        } catch (ServiceException $exception) {

            if ($exception->getCode() == 404 and $this->autoCreateShare) {

                $this->azure->createShare($this->share);

                $this->azure->createFileFromContent(
                    $this->share,
                    $path,
                    $contents,
                    $this->getOptionsFromConfig($config)
                );
            }
            else {
                throw $exception;
            }

        }

        /** @var \MicrosoftAzure\Storage\File\Models\FileProperties $result */
        $result = $this->azure->getFileProperties($this->share, $path);

        return $this->normalize($path, $result->getLastModified()->format('U'), $contents);
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
     * @param $path
     * @param FileProperties|File|Directory $properties
     * @param null $contents
     * @return array
     */
    protected function transformFileRemoteProperties($path, $properties = null, $contents = null)
    {

        if (substr($path, -1) === '/' || ($properties && $properties instanceof Directory)) {
            return ['type' => 'dir', 'path' => $path];
        }

        $meta = [
            'path' => $path,
            'dirname' => Util::dirname($path),
            'type' => 'file'
        ];

        if ($contents) {
            $meta['contents'] = $contents;
        }

        if ($properties) {

            if ($properties instanceof FileProperties) {
                $meta += [
                    'mimetype' => $properties->getContentType(),
                    'size' => $properties->getContentLength(),
                    'timestamp' => (int)$properties->getLastModified()->format('U'),
                ];
            } else if ($properties instanceof File) {
                $meta += [
                    'size' => $properties->getLength()
                ];
            }
        }

        return $meta;

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
        $this->azure->copyFile($this->share, $newpath, $path);
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
        $this->azure->deleteFile($this->share, $path);
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
        $this->azure->deleteDirectory($this->share, Util::dirname($dirname));
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

        $this->azure->createDirectory($this->share, $dirname);
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
            $this->azure->getFileMetadata($this->share, $path);
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

        /**  @var \MicrosoftAzure\Storage\File\Models\GetFileResult */
        $fileResult = $this->azure->getFile($this->share, $path);
        $properties = $fileResult->getProperties();
        $content = stream_get_contents($fileResult->getContentStream());

        return $this->transformFileRemoteProperties($path, $properties) + ['contents' => $content];
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

        /**  @var \MicrosoftAzure\Storage\File\Models\GetFileResult $fileResult */
        $fileResult = $this->azure->getFile($this->share, $path);
        $properties = $fileResult->getProperties();

        return $this->transformFileRemoteProperties($path, $properties) + ['stream' => $fileResult->getContentStream()];
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

        if ($recursive) {
            throw new InvalidArgumentException('Recursive listing is not available!');
        }

        if (strlen($directory)) {
            $directory = rtrim($directory, '/') . '/';
        }

        $options = new ListDirectoriesAndFilesOptions();
        $options->setPrefix($directory);

        /** @var ListDirectoriesAndFilesResult $listResults */
        $listResults = $this->azure->listDirectoriesAndFiles($this->share, $options);

        $contents = [];

        foreach ($listResults->getDirectories() as $directory) {
            $contents[] = $this->transformFileRemoteProperties($directory->getName(), $directory);
        }

        foreach ($listResults->getFiles() as $file) {
            $contents[] = $this->transformFileRemoteProperties($file->getName(), $file);
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

        /** @var \MicrosoftAzure\Storage\File\Models\FileProperties $result */
        $result = $this->azure->getFileProperties($this->share, $path);
        return $this->transformFileRemoteProperties($path, $result);
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
}