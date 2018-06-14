<?php


namespace SzuniSoft\Azure\Laravel\Test\Queue;


use MicrosoftAzure\Storage\Queue\Internal\IQueue;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use Mockery;
use SzuniSoft\Azure\Laravel\Queue\AzureConnector;
use SzuniSoft\Azure\Laravel\Queue\AzureQueue;

class AzureConnectorTest extends TestCase {

    /**
     * @var AzureConnector
     */
    protected $connector;

    /**
     * @var Mockery\MockInterface
     */
    protected $queueRestProxy;

    protected function setUp() {
        parent::setUp();

        $this->connector = new AzureConnector();
        $this->queueRestProxy = Mockery::mock('alias:' . QueueRestProxy::class);
    }


    /**
     * @test
     */
    public function it_can_create_azure_queue() {

        $config = [
            'protocol' => 'https',
            'accountname' => 'foo',
            'key' => 'bar',
            'queue' => 'baz',
            'timeout' => 25
        ];

        $connectionString = 'DefaultEndpointsProtocol=' . $config['protocol'] . ';AccountName=' . $config['accountname'] . ';AccountKey=' . $config['key'];
        $queueProxy = Mockery::mock(IQueue::class);

        $this->queueRestProxy->shouldReceive('createQueueService')
            ->with($connectionString)
            ->andReturn($queueProxy)
            ->once();

        /** @var AzureQueue $azureQueue */
        $azureQueue = $this->connector->connect($config);
        $this->assertEquals('baz', $azureQueue->getQueue(null));
        $this->assertEquals(25, $azureQueue->getVisibilityTimeout());
    }

}