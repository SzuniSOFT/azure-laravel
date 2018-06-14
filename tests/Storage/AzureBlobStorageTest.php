<?php


namespace SzuniSoft\Azure\Laravel\Test\Storage;


use Carbon\Carbon;
use League\Flysystem\Config;
use MicrosoftAzure\Storage\Blob\Internal\IBlob;
use MicrosoftAzure\Storage\Blob\Models\CopyBlobResult;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\GetBlobResult;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SzuniSoft\Azure\Laravel\Storage\AzureBlobStorage;

class AzureBlobStorageTest extends TestCase {

    /**
     * @var Mockery\MockInterface
     */
    protected $azure;

    /**
     * @var AzureBlobStorage
     */
    protected $storage;

    protected function setUp()
    {
        parent::setUp();

        $this->azure = Mockery::mock(IBlob::class);
        $this->storage = new AzureBlobStorage($this->azure, 'test');
    }

    /**
     * @param $lastModified
     * @return CopyBlobResult
     */
    protected function getCopyBlobResult($lastModified)
    {
        return CopyBlobResult::create([
            Resources::LAST_MODIFIED => $lastModified
        ]);
    }

    /**
     * @param $lastModified
     * @param $content
     * @return GetBlobResult
     */
    protected function getCopyGetBlobResult($lastModified, $content)
    {

        $httpStreamInterface = Mockery::mock(StreamInterface::class);
        $httpStreamInterface->shouldReceive('detach')->once()->andReturn($this->stringToStream($content));

        return GetBlobResult::create([
            Resources::LAST_MODIFIED => $lastModified,
            Resources::CONTENT_LENGTH => strlen($content)
        ], $httpStreamInterface, []);
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

        $this->azure->shouldReceive('createBlockBlob')
            ->once()
            ->withArgs(function ($container, $path, $content, $config) use (&$rawContent) {
                return $container == 'test' && $path == 'foo/bar.json' && $content == $rawContent;
            })
            ->andReturn($this->getCopyBlobResult('Wed, 07 Jul 1993 00:00:00 +0000'));

        $this->assertSame([
            'path' => 'foo/bar.json',
            'timestamp' => 742003200,
            'dirname' => 'foo',
            'type' => 'file',
            'contents' => $rawContent
        ], $this->storage->write('foo/bar.json', $rawContent, new Config()));
    }

    /** @test */
    public function it_can_update_string_content()
    {
        $content = ['content' => true];
        $rawContent = json_encode($content);

        $this->azure->shouldReceive('createBlockBlob')
            ->once()
            ->withArgs(function ($container, $path, $content, $config) use (&$rawContent) {
                return $container == 'test' && $path == 'foo/bar.json' && $content == $rawContent;
            })
            ->andReturn($this->getCopyBlobResult('Wed, 07 Jul 1993 00:00:00 +0000'));

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

        $this->azure->shouldReceive('createBlockBlob')
            ->once()
            ->withArgs(function ($container, $path, $stream, $config) {
                return $container == 'test' && $path == 'foo/bar.txt' && is_resource($stream);
            })
            ->andReturn($this->getCopyBlobResult('Wed, 07 Jul 1993 00:00:00 +0000'));

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

        $this->azure->shouldReceive('createBlockBlob')
            ->once()
            ->withArgs(function ($container, $path, $stream, $config) {
                return $container == 'test' && $path == 'foo/bar.txt' && is_resource($stream);
            })
            ->andReturn($this->getCopyBlobResult('Fri, 01 Jan 2455 12:44:00 +0000'));

        $this->assertSame([
            'path' => 'foo/bar.txt',
            'timestamp' => 15305201040,
            'dirname' => 'foo',
            'type' => 'file'
        ], $this->storage->updateStream('foo/bar.txt', $stream, new Config()));
    }

    /** @test */
    public function it_can_read()
    {

        $this->azure->shouldReceive('getBlob')
            ->once()
            ->withArgs(['test', 'foo/bar.txt'])
            ->andReturn($this->getCopyGetBlobResult('Fri, 01 Jan 2455 12:44:00 +0000', 'something'));

        $this->assertSame([
            'path' => 'foo/bar.txt',
            'timestamp' => 15305201040,
            'dirname' => 'foo',
            'mimetype' => null,
            'size' => 9,
            'type' => 'file',
            'contents' => 'something'
        ], $this->storage->read('foo/bar.txt'));
    }

    /** @test */
    public function it_can_read_stream()
    {

        $this->azure->shouldReceive('getBlob')
            ->once()
            ->withArgs(['test', 'foo/bar.txt'])
            ->andReturn($this->getCopyGetBlobResult('Fri, 01 Jan 2455 12:44:00 +0000', 'something'));

        $result = $this->storage->readStream('foo/bar.txt');

        $this->assertArrayHasKey('stream', $result);
        $this->assertTrue(is_resource($result['stream']));

        unset($result['stream']);

        $this->assertSame([
            'path' => 'foo/bar.txt',
            'timestamp' => 15305201040,
            'dirname' => 'foo',
            'mimetype' => null,
            'size' => 9,
            'type' => 'file'
        ], $result);
    }

    /** @test */
    public function it_can_get_file_metadata()
    {

        $this->azure->shouldReceive('getBlobProperties')
            ->times(4)
            ->withArgs(['test', 'foo/bar.txt'])
            ->andReturn($this->getCopyGetBlobResult('Fri, 01 Jan 2455 12:44:00 +0000', 'something'));

        $expected = [
            'path' => 'foo/bar.txt',
            'timestamp' => 15305201040,
            'dirname' => 'foo',
            'mimetype' => null,
            'size' => 9,
            'type' => 'file'
        ];

        $this->assertSame($expected, $this->storage->getMetadata('foo/bar.txt'));
        $this->assertSame($expected, $this->storage->getTimestamp('foo/bar.txt'));
        $this->assertSame($expected, $this->storage->getMimetype('foo/bar.txt'));
        $this->assertSame($expected, $this->storage->getSize('foo/bar.txt'));
    }

    /** @test */
    public function it_can_determine_file_exists()
    {

        $this->azure->shouldReceive('getBlobMetadata')
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

        $this->azure->shouldReceive('getBlobMetadata')
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

        $this->azure->shouldReceive('getBlobMetadata')
            ->once()
            ->withArgs(['test', 'foo/bar.txt'])
            ->andThrow($exception);

        $this->expectException(ServiceException::class);

        $this->storage->has('foo/bar.txt');
    }

    /** @test */
    public function it_can_create_directory()
    {

        $this->azure->shouldReceive('createBlockBlob')
            ->once()
            ->andReturn($this->getCopyBlobResult('Fri, 01 Jan 2455 12:44:00 +0000'));

        $this->assertSame([
            'path' => 'foo/bar/baz/dir/',
            'type' => 'dir'
        ], $this->storage->createDir('foo\bar\baz\dir//////', new Config()));
    }

    /** @test */
    public function it_can_copy()
    {

        $this->azure->shouldReceive('copyBlob')
            ->once()
            ->withArgs(['test', 'baz/bar.txt', 'test', 'foo/bar.txt']);

        $this->assertTrue($this->storage->copy('foo/bar.txt', 'baz/bar.txt'));
    }

    /** @test */
    public function it_can_rename()
    {

        $this->azure->shouldReceive('copyBlob')
            ->once()
            ->withArgs(['test', 'foo/baz.txt', 'test', 'foo/bar.txt']);

        $this->azure->shouldReceive('deleteBlob')
            ->once()
            ->withArgs(['test', 'foo/bar.txt']);

        $this->assertTrue($this->storage->rename('foo/bar.txt', 'foo/baz.txt'));;
    }

    /** @test */
    public function it_can_delete()
    {

        $this->azure->shouldReceive('deleteBlob')
            ->once()
            ->withArgs(['test', 'foo/bar.txt']);

        $this->assertTrue($this->storage->delete('foo/bar.txt'));
    }

    /** @test */
    public function it_can_delete_directory()
    {

        $mockedBlob = Mockery::mock(\MicrosoftAzure\Storage\Blob\Models\Blob::class);
        $mockedBlob->shouldReceive('getName')->once();

        $mockedBlobList = Mockery::mock(\MicrosoftAzure\Storage\Blob\Models\ListBlobsResult::class);
        $mockedBlobList->shouldReceive('getBlobs')->once()->andReturn([$mockedBlob]);

        $this->azure->shouldReceive('listBlobs')->once()->andReturn($mockedBlobList);
        $this->azure->shouldReceive('deleteBlob')->once();

        $this->assertTrue($this->storage->deleteDir('foo/'));
    }

    /** @test */
    public function it_can_list_contents()
    {

        $mockedProperties = Mockery::mock(\MicrosoftAzure\Storage\Blob\Models\BlobProperties::class);
        $mockedProperties->shouldReceive('getLastModified')->once()->andReturn(Carbon::createFromFormat(Carbon::RFC1123, 'Fri, 01 Jan 2455 12:44:00 +0000'));
        $mockedProperties->shouldReceive('getContentType')->once()->andReturn('text/plain');
        $mockedProperties->shouldReceive('getContentLength')->once()->andReturn(1024);

        $mockedBlob = Mockery::mock(\MicrosoftAzure\Storage\Blob\Models\Blob::class);
        $mockedBlob->shouldReceive('getName')->once()->andReturn('foo.txt');
        $mockedBlob->shouldReceive('getProperties')->once()->andReturn($mockedProperties);

        $mockedBlobList = Mockery::mock(\MicrosoftAzure\Storage\Blob\Models\ListBlobsResult::class);
        $mockedBlobList->shouldReceive('getBlobs')->once()->andReturn([$mockedBlob]);
        $mockedBlobList->shouldReceive('getBlobPrefixes')->once()->andReturn([]);

        $this->azure->shouldReceive('listBlobs')->once()->andReturn($mockedBlobList);

        $this->assertSame([
            [
                'path' => 'foo.txt',
                'timestamp' => 15305201040,
                'dirname' => '',
                'mimetype' => 'text/plain',
                'size' => 1024,
                'type' => 'file'
            ]
        ], $this->storage->listContents());
    }

    /** @test */
    public function it_does_not_ignore_config()
    {

        $resultBlob = $this->getCopyBlobResult('Fri, 01 Jan 2455 12:44:00 +0000');
        $settings = [
            'ContentType' => 'anyContentType',
            'CacheControl' => 'foreverCacheControl',
            'Metadata' => ['suchMetaData'],
            'ContentLanguage' => 'foreignContentLanguage',
            'ContentEncoding' => 'unknownContentEncoding'
        ];

        $this->azure->shouldReceive('createBlockBlob')->once()->withArgs([
            'test',
            'foo/bar.txt',
            'content',
            Mockery::on(function (CreateBlobOptions $options) use ($settings) {
                foreach ($settings as $key => $value) {
                    if (call_user_func([$options, "get$key"]) != $value) {
                        return false;
                    }
                }
                return true;
            })
        ])->andReturn($resultBlob);

        $this->assertSame([
            'path' => 'foo/bar.txt',
            'timestamp' => 15305201040,
            'dirname' => 'foo',
            'type' => 'file',
            'contents' => 'content'
        ], $this->storage->write('foo/bar.txt', 'content', new Config($settings)));
    }

}