<?php


namespace SzuniSoft\Azure\Laravel\Test\Queue;

use MicrosoftAzure\Storage\Queue\Internal\IQueue;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use Mockery;
use SzuniSoft\Azure\Laravel\Queue\AzureJob;
use SzuniSoft\Azure\Laravel\Queue\AzureQueue;

class AzureJobTest extends TestCase {

    /**
     * @var Mockery\Mock
     */
    protected $azure;

    /**
     * @var AzureQueue
     */
    protected $queue;

    /**
     * @var QueueMessage
     */
    protected $message;

    /**
     * @var AzureJob
     */
    protected $job;

    protected function setUp()
    {
        parent::setUp();

        $this->azure = Mockery::mock(\MicrosoftAzure\Storage\Queue\Internal\IQueue::class);
        $this->queue = new AzureQueue($this->azure, 'testqueue', 30);
        $this->queue->setContainer($this->app);

        $this->message = new QueueMessage();
        $this->message->setMessageId(123);
        $this->message->setPopReceipt(234);
        $this->message->setMessageText(json_encode(['something' => 'important']));
        $this->message->setDequeueCount(5);

        $this->job = new AzureJob($this->app, $this->azure, $this->message, 'testconnection', 'testqueue');
    }

    /** @test */
    public function it_can_get_job_id()
    {
        $this->assertEquals(123, $this->job->getJobId());
    }

    /** @test */
    public function it_can_delete_job_from_queue()
    {
        $this->azure->shouldReceive('deleteMessage')->once()->withArgs(['testqueue', 123, 234]);
        $this->job->delete();
    }

    /** @test */
    public function it_can_release_job_back_to_queue()
    {
        $this->azure->shouldReceive('updateMessage')->once()->withArgs(['testqueue', 123, 234, null, 10]);
        $this->job->release(10);
    }

    /** @test */
    public function it_can_get_azure_job()
    {
        $this->assertEquals($this->message, $this->job->getAzureJob());
    }

    /** @test */
    public function it_can_get_raw_body()
    {
        $this->assertEquals(json_encode(['something' => 'important']), $this->job->getRawBody());
    }

    /** @test */
    public function it_can_get_azure_proxy()
    {
        $this->assertInstanceOf(IQueue::class, $this->job->getAzure());
    }

    /** @test */
    public function it_can_get_number_of_attempts()
    {
        $this->assertEquals(5, $this->job->attempts());
    }

    /** @test */
    public function it_can_get_app_container()
    {
        $this->assertEquals($this->app, $this->job->getContainer());
    }

}