<?php


namespace SzuniSoft\Azure\Laravel\Test\Storage;

use InvalidArgumentException;
use League\Flysystem\Config;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\File\Internal\IFile;
use MicrosoftAzure\Storage\File\Models\CopyFileResult;
use MicrosoftAzure\Storage\File\Models\CreateFileFromContentOptions;
use MicrosoftAzure\Storage\File\Models\Directory;
use MicrosoftAzure\Storage\File\Models\File;
use MicrosoftAzure\Storage\File\Models\FileProperties;
use MicrosoftAzure\Storage\File\Models\GetFileResult;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SzuniSoft\Azure\Laravel\Storage\AzureFileStorage;

class AzureFileStorageTest extends TestCase {

    /**
     * @var Mockery\MockInterface
     */
    protected $azure;

    /**
     * @var AzureFileStorage
     */
    protected $storage;

    protected function setUp()
    {
        parent::setUp();

        $this->azure = Mockery::mock(IFile::class);
        $this->storage = new AzureFileStorage($this->azure, 'test');
    }

    /**
     * @param $lastModified
     * @return CopyFileResult
     */
    protected function getCopyFileResult($lastModified)
    {
        return CopyFileResult::create([
            Resources::LAST_MODIFIED => $lastModified,
            Resources::X_MS_COPY_STATUS => null,
            Resources::X_MS_COPY_ID => null,
            Resources::ETAG => null
        ]);
    }

    /**
     * @param $lastModified
     * @return FileProperties
     */
    protected function getFileProperties($lastModified, $content = null)
    {

        $arr = [Resources::LAST_MODIFIED => $lastModified];

        if ($content) {
            $arr += [Resources::CONTENT_LENGTH => strlen($content)];
        }

        return FileProperties::createFromHttpHeaders($arr);
    }

    /** @test */
    public function it_can_automatically_create_share()
    {

        $this->azure = Mockery::mock(IFile::class);
        $storage = new AzureFileStorage($this->azure, 'test', true);

        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(404);
        $response->shouldReceive('getReasonPhrase')->andReturn('Not found');
        $response->shouldReceive('getBody')->andReturn('Not found');

        $exception = new ServiceException($response);

        $this->azure->shouldReceive('createFileFromContent')
            ->once()
            ->andThrow($exception);

        $this->azure->shouldReceive('createShare')
            ->once();

        $this->azure->shouldReceive('createFileFromContent')
            ->once();

        $this->azure->shouldReceive('getFileProperties')
            ->once()
            ->andReturn($this->getFileProperties('Wed, 07 Jul 1993 00:00:00 +0000'));

        $storage->write('foo.txt', 'test content', new Config());
    }

    /** @test */
    public function it_can_get_azure_proxy()
    {
        $this->assertEquals($this->azure, $this->storage->getAzure());
    }

    /** @test */
    public function it_can_write_string_content()
    {
        $content = ['content' => true];
        $rawContent = json_encode($content);

        $meta = [
            'path' => 'foo/bar.json',
            'timestamp' => 742003200,
            'dirname' => 'foo',
            'type' => 'file',
            'contents' => $rawContent
        ];

        $this->azure->shouldReceive('createFileFromContent')->once();
        $this->azure->shouldReceive('getFileProperties')->once()->andReturn($this->getFileProperties('Wed, 07 Jul 1993 00:00:00 +0000'));

        $this->assertSame($meta, $this->storage->write('foo/bar.json', $rawContent, new Config()));
    }

    /** @test */
    public function it_can_update_string_content()
    {
        $content = ['content' => true];
        $rawContent = json_encode($content);

        $this->azure->shouldReceive('createFileFromContent')->once();
        $this->azure->shouldReceive('getFileProperties')->once()->andReturn($this->getFileProperties('Wed, 07 Jul 1993 00:00:00 +0000'));

        $this->assertSame([
            'path' => 'foo/bar.json',
            'timestamp' => 742003200,
            'dirname' => 'foo',
            'type' => 'file',
            'contents' => $rawContent
        ], $this->storage->update('foo/bar.json', $rawContent, new Config()));
    }

    /** @test */
    public function it_can_write_by_stream_resource()
    {

        $stream = $this->stringToStream('test content');

        $this->azure->shouldReceive('createFileFromContent')
            ->once()
            ->withArgs(function ($container, $path, $stream, $config) {
                return is_resource($stream);
            });
        $this->azure->shouldReceive('getFileProperties')->once()->andReturn($this->getFileProperties('Wed, 07 Jul 1993 00:00:00 +0000'));

        $this->assertSame([
            'path' => 'foo/bar.txt',
            'timestamp' => 742003200,
            'dirname' => 'foo',
            'type' => 'file'
        ], $this->storage->writeStream('foo/bar.txt', $stream, new Config()));
    }

    /** @test */
    public function it_can_update_by_stream_resource()
    {

        $stream = $this->stringToStream('test content to be updated');

        $this->azure->shouldReceive('createFileFromContent')
            ->once()
            ->withArgs(function ($container, $path, $stream, $config) {
                return is_resource($stream);
            });

        $this->azure->shouldReceive('getFileProperties')->once()->andReturn($this->getFileProperties('Wed, 07 Jul 1993 00:00:00 +0000'));

        $this->assertSame([
            'path' => 'foo/bar.txt',
            'timestamp' => 742003200,
            'dirname' => 'foo',
            'type' => 'file'
        ], $this->storage->updateStream('foo/bar.txt', $stream, new Config()));
    }

    /** @test */
    public function it_can_read()
    {

        $mockedFileResult = Mockery::mock(GetFileResult::class);
        $mockedFileResult->shouldReceive('getProperties')->once()->andReturn($this->getFileProperties('Fri, 01 Jan 2455 12:44:00 +0000', 'something'));
        $mockedFileResult->shouldReceive('getContentStream')->once()->andReturn($this->stringToStream('something'));

        $this->azure->shouldReceive('getFile')->once()->withArgs(['test', 'foo/bar.txt'])->andReturn($mockedFileResult);

        $this->assertSame([
            'path' => 'foo/bar.txt',
            'dirname' => 'foo',
            'type' => 'file',
            'mimetype' => null,
            'size' => 9,
            'timestamp' => 15305201040,
            'contents' => 'something'
        ], $this->storage->read('foo/bar.txt'));
    }

    /** @test */
    public function it_can_read_stream()
    {

        $mockedFileResult = Mockery::mock(GetFileResult::class);
        $mockedFileResult->shouldReceive('getProperties')->once()->andReturn($this->getFileProperties('Fri, 01 Jan 2455 12:44:00 +0000', 'something'));
        $mockedFileResult->shouldReceive('getContentStream')->once()->andReturn($this->stringToStream('something'));

        $this->azure->shouldReceive('getFile')->once()->withArgs(['test', 'foo/bar.txt'])->andReturn($mockedFileResult);

        $result = $this->storage->readStream('foo/bar.txt');

        $this->assertArrayHasKey('stream', $result);
        $this->assertTrue(is_resource($result['stream']));

        unset($result['stream']);

        $this->assertSame([
            'path' => 'foo/bar.txt',
            'dirname' => 'foo',
            'type' => 'file',
            'mimetype' => null,
            'size' => 9,
            'timestamp' => 15305201040,
        ], $result);
    }

    /** @test */
    public function it_can_get_file_metadata()
    {

        $this->azure->shouldReceive('getFileProperties')
            ->times(4)
            ->withArgs(['test', 'foo/bar.txt'])
            ->andReturn($this->getFileProperties('Fri, 01 Jan 2455 12:44:00 +0000', 'something'));

        $expected = [
            'path' => 'foo/bar.txt',
            'dirname' => 'foo',
            'type' => 'file',
            'mimetype' => null,
            'size' => 9,
            'timestamp' => 15305201040,
        ];

        $this->assertSame($expected, $this->storage->getMetadata('foo/bar.txt'));
        $this->assertSame($expected, $this->storage->getTimestamp('foo/bar.txt'));
        $this->assertSame($expected, $this->storage->getMimetype('foo/bar.txt'));
        $this->assertSame($expected, $this->storage->getSize('foo/bar.txt'));
    }

    /** @test */
    public function it_can_determine_file_exists()
    {

        $this->azure->shouldReceive('getFileMetadata')
            ->once()
            ->withArgs(['test', 'foo/bar.txt'])
            ->andReturnTrue();

        $this->assertTrue($this->storage->has('foo/bar.txt'));
    }

    /** @test */
    public function it_can_determine_file_does_not_exists()
    {
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(404);
        $response->shouldReceive('getReasonPhrase')->andReturn(null);
        $response->shouldReceive('getBody')->andReturn('Not Found');

        $exception = new ServiceException($response);

        $this->azure->shouldReceive('getFileMetadata')
            ->once()
            ->withArgs(['test', 'foo/bar.txt'])
            ->andThrow($exception);


        $this->assertFalse($this->storage->has('foo/bar.txt'));
    }

    /** @test */
    public function it_can_fail_when_error()
    {
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(500);
        $response->shouldReceive('getReasonPhrase')->andReturn(null);
        $response->shouldReceive('getBody')->andReturn('Inter Server Error');

        $exception = new ServiceException($response);

        $this->azure->shouldReceive('getFileMetadata')
            ->once()
            ->withArgs(['test', 'foo/bar.txt'])
            ->andThrow($exception);

        $this->expectException(ServiceException::class);

        $this->storage->has('foo/bar.txt');
    }

    /** @test */
    public function it_can_create_directory()
    {

        $this->azure->shouldReceive('createDirectory')
            ->once()
            ->andReturn($this->getCopyFileResult('Fri, 01 Jan 2455 12:44:00 +0000'));

        $this->assertSame([
            'path' => 'foo/bar/baz/dir/',
            'type' => 'dir'
        ], $this->storage->createDir('foo\bar\baz\dir//////', new Config()));
    }

    /** @test */
    public function it_can_copy()
    {

        $this->azure->shouldReceive('copyFile')
            ->once()
            ->withArgs(['test', 'baz/bar.txt', 'foo/bar.txt']);

        $this->assertTrue($this->storage->copy('foo/bar.txt', 'baz/bar.txt'));
    }

    /** @test */
    public function it_can_rename()
    {

        $this->azure->shouldReceive('copyFile')
            ->once()
            ->withArgs(['test', 'foo/baz.txt', 'foo/bar.txt']);

        $this->azure->shouldReceive('deleteFile')
            ->once()
            ->withArgs(['test', 'foo/bar.txt']);

        $this->assertTrue($this->storage->rename('foo/bar.txt', 'foo/baz.txt'));;
    }

    /** @test */
    public function it_can_delete()
    {

        $this->azure->shouldReceive('deleteFile')
            ->once()
            ->withArgs(['test', 'foo/bar.txt']);

        $this->assertTrue($this->storage->delete('foo/bar.txt'));
    }

    /** @test */
    public function it_can_delete_directory()
    {
        $this->azure->shouldReceive('deleteDirectory')->once();

        $this->assertTrue($this->storage->deleteDir('foo/'));
    }

    /** @test */
    public function it_can_list_contents()
    {

        /*$mockedProperties = Mockery::mock(\MicrosoftAzure\Storage\Blob\Models\BlobProperties::class);
        $mockedProperties->shouldReceive('getLastModified')->once()->andReturn(Carbon::createFromFormat(Carbon::RFC1123, 'Fri, 01 Jan 2455 12:44:00 +0000'));
        $mockedProperties->shouldReceive('getContentType')->once()->andReturn('text/plain');
        $mockedProperties->shouldReceive('getContentLength')->once()->andReturn(1024);

        $mockedBlob = Mockery::mock(\MicrosoftAzure\Storage\Blob\Models\Blob::class);
        $mockedBlob->shouldReceive('getName')->once()->andReturn('foo.txt');
        $mockedBlob->shouldReceive('getProperties')->once()->andReturn($mockedProperties);

        $mockedBlobList = Mockery::mock(\MicrosoftAzure\Storage\Blob\Models\ListBlobsResult::class);
        $mockedBlobList->shouldReceive('getBlobs')->once()->andReturn([$mockedBlob]);
        $mockedBlobList->shouldReceive('getBlobPrefixes')->once()->andReturn([]);*/

        $mockedFile = Mockery::mock(File::class);
        $mockedFile->shouldReceive('getName')->andReturn('foo.txt');
        $mockedFile->shouldReceive('getLength')->andReturn(1024);

        $mockedDirectory = Mockery::mock(Directory::class);
        $mockedDirectory->shouldReceive('getName')->andReturn('bar');

        $mockedListResult = Mockery::mock(\MicrosoftAzure\Storage\File\Models\ListDirectoriesAndFilesResult::class);
        $mockedListResult->shouldReceive('getDirectories')->once()->andReturn([$mockedDirectory]);
        $mockedListResult->shouldReceive('getFiles')->once()->andReturn([$mockedFile]);

        $this->azure->shouldReceive('listDirectoriesAndFiles')->once()->andReturn($mockedListResult);

        $this->assertSame([
            [
                'type' => 'dir',
                'path' => 'bar'
            ],
            [
                'path' => 'foo.txt',
                'dirname' => '',
                'type' => 'file',
                'size' => 1024,
            ]
        ], $this->storage->listContents());
    }

    /** @test */
    public function it_should_refuse_recursive_listing()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->storage->listContents('any', true);
    }

    /** @test */
    public function it_does_not_ignore_config()
    {

        $resultBlob = $this->getCopyFileResult('Fri, 01 Jan 2455 12:44:00 +0000');
        $settings = [
            'ContentType' => 'anyContentType',
            'CacheControl' => 'foreverCacheControl',
            'Metadata' => ['suchMetaData'],
            'ContentLanguage' => 'foreignContentLanguage',
            'ContentEncoding' => 'unknownContentEncoding'
        ];

        $this->azure->shouldReceive('createFileFromContent')->once()->withArgs([
            'test',
            'foo/bar.txt',
            'content',
            Mockery::on(function (CreateFileFromContentOptions $options) use ($settings) {
                foreach ($settings as $key => $value) {
                    if (call_user_func([$options, "get$key"]) != $value) {
                        return false;
                    }
                }
                return true;
            })
        ])->andReturn($resultBlob);

        $this->azure->shouldReceive('getFileProperties')->once()->andReturn($this->getFileProperties('Wed, 07 Jul 1993 00:00:00 +0000'));

        $this->assertSame([
            'path' => 'foo/bar.txt',
            'timestamp' => 742003200,
            'dirname' => 'foo',
            'type' => 'file',
            'contents' => 'content'
        ], $this->storage->write('foo/bar.txt', 'content', new Config($settings)));
    }

}