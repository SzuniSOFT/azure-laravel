<?php


namespace SzuniSoft\Azure\Laravel\Test\Queue;


use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageOptions;
use MicrosoftAzure\Storage\Queue\Models\GetQueueMetadataResult;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use SzuniSoft\Azure\Laravel\Queue\AzureJob;
use SzuniSoft\Azure\Laravel\Queue\AzureQueue;
use SzuniSoft\Azure\Laravel\Test\Queue\Fixtures;

class AzureQueueTest extends TestCase {

    /**
     * @var \Mockery\Mock
     */
    protected $azure;

    /**
     * @var AzureQueue
     */
    protected $queue;

    /**
     * @var \Illuminate\Container\Container
     */
    protected $app;

    protected function setUp()
    {

        parent::setUp();

        $this->azure = Mockery::mock(\MicrosoftAzure\Storage\Queue\Internal\IQueue::class);
        $this->queue = new AzureQueue($this->azure, 'testqueue', 30);
        $this->queue->setContainer($this->app);
    }

    /**
     * @param Mockery\Expectation $mock
     * @param int $count
     * @return Mockery\Expectation
     */
    protected function setUpListMessagesReturnExpectation($mock, $count = 1)
    {
        return $mock->andReturn(new Fixtures\ListMessagesResult($count));
    }

    /**
     * @return Mockery\Expectation|Mockery\ExpectationInterface|Mockery\HigherOrderMessage
     */
    protected function azureListMessageReceive()
    {
        return $this->azure->shouldReceive('listMessages');
    }

    protected function job()
    {
        return [
            "displayName" => "testJob",
            "job" => "testJob",
            "maxTries" => null,
            "timeout" => null,
            "data" => "testData"
        ];
    }

    /** @test */
    public function it_can_automatically_create_azure_queue()
    {

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(404);
        $response->shouldReceive('getReasonPhrase')->andReturn('Not found');
        $response->shouldReceive('getBody')->andReturn('Not found');

        $exception = new ServiceException($response);

        $this->azure->shouldReceive('createMessage')
            ->twice()
            ->andThrow($exception);

        $this->expectException(ServiceException::class);

        $this->azure->shouldReceive('createQueue')->once()->withArgs(['testqueue']);

        $queue = new AzureQueue($this->azure, 'testqueue', 30, false, true);
        $queue->push('testJob', 'testData');
    }

    /** @test */
    public function it_can_base_64_encode_jobs()
    {

        $job = $this->job();

        $queue = new AzureQueue($this->azure, 'testqueue', 30, true);
        $queue->setContainer($this->app);

        $this->azure->shouldReceive('createMessage')->once()
            ->withArgs([
                'testqueue',
                base64_encode(json_encode($job))
            ]);

        $queue->push('testJob', 'testData');
    }

    /** @test */
    public function it_can_push_message_to_queue()
    {

        $job = $this->job();

        $this->azure->shouldReceive('createMessage')->once()
            ->withArgs([
                'testqueue',
                json_encode($job)
            ]);

        $this->queue->push('testJob', 'testData');
    }

    /** @test */
    public function it_can_push_to_specific_queue()
    {

        $job = $this->job();

        $this->azure->shouldReceive('createMessage')->once()
            ->withArgs([
                'otherqueue',
                json_encode($job)
            ]);

        $this->queue->push('testJob', 'testData', 'otherqueue');
    }

    /** @test */
    public function it_can_pop_message_from_queue()
    {

        $this->setUpListMessagesReturnExpectation($this->azureListMessageReceive()->once());

        $message = $this->queue->pop('testqueue');
        $this->assertInstanceOf(AzureJob::class, $message);
    }

    /** @test */
    public function it_can_pop_message_from_default_queue()
    {
        $this->setUpListMessagesReturnExpectation($this->azureListMessageReceive()->once());

        $message = $this->queue->pop();
        $this->assertInstanceOf(AzureJob::class, $message);
        $this->assertEquals($this->queue->getQueue(), 'testqueue');
    }

    /** @test */
    public function it_returns_null_message_if_queue_empty()
    {
        $this->setUpListMessagesReturnExpectation($this->azureListMessageReceive()->once(), 0);

        $message = $this->queue->pop();
        $this->assertNull($message);
    }

    /** @test */
    public function it_passes_visibility_timeout_from_config()
    {
        $this->setUpListMessagesReturnExpectation(
            $this->azureListMessageReceive()->once()->withArgs(function ($queue, ListMessagesOptions $options) {
                return $queue == 'testqueue' && $options->getVisibilityTimeoutInSeconds() == 30;
            })
        );

        $this->queue->pop('testqueue');
    }


    /** @test */
    public function it_only_offers_first_message()
    {
        $this->setUpListMessagesReturnExpectation(
            $this->azureListMessageReceive()->once()->withArgs(function ($queue, ListMessagesOptions $options) {
                return $queue == 'testqueue' && $options->getNumberOfMessages() == 1;
            })
        );

        $this->queue->pop('testqueue');
    }

    /** @test */
    public function it_can_get_visibility_timeout()
    {
        $this->assertEquals(30, $this->queue->getVisibilityTimeout());
    }

    /** @test */
    public function it_can_get_queue_size()
    {
        $this->azure->shouldReceive('getQueueMetadata')->with('testqueue')->andReturn(new GetQueueMetadataResult(5, []));
        $this->assertEquals(5, $this->queue->size('testqueue'));
    }

    /** @test */
    public function it_can_delay_jobs_on_default_queue()
    {

        $job = $this->job();
        $this->azure->shouldReceive('createMessage')->withArgs(function ($queue, $payload, CreateMessageOptions $options) use (&$job) {
            return $queue == 'testqueue' && $options->getVisibilityTimeoutInSeconds() == 60 && $payload == json_encode($job);
        });

        $this->queue->later(60, 'testJob', 'testData');
    }

    /** @test */
    public function it_can_delay_jobs_on_other_queue()
    {

        $job = $this->job();
        $this->azure->shouldReceive('createMessage')->withArgs(function ($queue, $payload, CreateMessageOptions $options) use (&$job) {
            return $queue == 'otherqueue' && $options->getVisibilityTimeoutInSeconds() == 60 && $payload == json_encode($job);
        });

        $this->queue->later(60, 'testJob', 'testData', 'otherqueue');
    }

}