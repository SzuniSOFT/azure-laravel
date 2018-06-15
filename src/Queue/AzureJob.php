<?php


namespace SzuniSoft\Azure\Laravel\Queue;


use Illuminate\Container\Container;
use Illuminate\Contracts;
use Illuminate\Queue\Jobs\Job;
use MicrosoftAzure\Storage\Queue\Internal\IQueue;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureJob extends Job implements Contracts\Queue\Job {

    /**
     * @var QueueRestProxy
     */
    protected $azure;

    /**
     * @var QueueMessage
     */
    protected $job;

    /**
     * @var string
     */
    protected $queue;
    /**
     * @var bool
     */
    protected $autoBase64;

    /**
     * AzureJob constructor.
     * @param Container $container
     * @param IQueue $azure
     * @param QueueMessage $job
     * @param $connectionName
     * @param $queue
     * @param bool $autoBase64
     */
    public function __construct(Container $container, IQueue $azure, QueueMessage $job, $connectionName, $queue, $autoBase64 = false)
    {
        $this->azure = $azure;
        $this->queue = $queue;
        $this->job = $job;
        $this->container = $container;
        $this->connectionName = $connectionName;
        $this->autoBase64 = $autoBase64;
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        parent::delete();
        $this->azure->deleteMessage($this->queue, $this->job->getMessageId(), $this->job->getPopReceipt());
    }

    /**
     * @inheritdoc
     *
     * @param int $delay
     * @return mixed|void
     */
    public function release($delay = 0)
    {
        parent::release($delay);
        $this->azure->updateMessage($this->queue, $this->job->getMessageId(), $this->job->getPopReceipt(), null, $delay);
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->getMessageId();
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody()
    {

        // Automatic base64 decode
        // Azure prefers base64 encoded messages
        if ($this->autoBase64) {
            return base64_decode($this->job->getMessageText());
        }

        return $this->job->getMessageText();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->job->getDequeueCount();
    }

    /**
     * @return QueueMessage
     */
    public function getAzureJob()
    {
        return $this->job;
    }

    /**
     * @return IQueue|QueueRestProxy
     */
    public function getAzure()
    {
        return $this->azure;
    }

}