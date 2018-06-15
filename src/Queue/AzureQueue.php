<?php


namespace SzuniSoft\Azure\Laravel\Queue;

use Illuminate\Contracts;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Queue\Internal\IQueue;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageOptions;
use MicrosoftAzure\Storage\Queue\Models\GetQueueMetadataResult;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;

/**
 * Class AzureQueue
 * @package SzuniSoft\Azure\Laravel\Queue
 */
class AzureQueue extends \Illuminate\Queue\Queue implements Contracts\Queue\Queue {

    /**
     * @var IQueue
     */
    protected $azure;

    /**
     * @var int
     */
    protected $visibilityTimeout;

    /**
     * @var string
     */
    protected $default;

    /**
     * @var bool
     */
    protected $autoBase64;

    /**
     * @var bool
     */
    protected $autoCreateQueues;

    /**
     * AzureQueue constructor.
     * @param IQueue $azure
     * @param $default
     * @param $visibilityTimeout
     * @param bool $autoBase64
     * @param bool $autoCreateQueues
     */
    public function __construct(IQueue $azure, $default, $visibilityTimeout, $autoBase64 = false, $autoCreateQueues = false)
    {
        $this->azure = $azure;
        $this->default = $default;
        $this->visibilityTimeout = $visibilityTimeout ?: 5;
        $this->autoBase64 = $autoBase64;
        $this->autoCreateQueues = $autoCreateQueues;
    }

    /**
     * Gets queue or return the default one.
     *
     * @param string $queue
     * @return mixed
     */
    public function getQueue($queue = '')
    {
        return $queue ?: $this->default;
    }

    /**
     * Returns with the azure client
     *
     * @return IQueue
     */
    public function getAzure()
    {
        return $this->azure;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string $queue
     * @return int
     */
    public function size($queue = null)
    {

        $queue = $this->getQueue($queue);

        /** @var GetQueueMetadataResult $meta */
        $meta = $this->azure->getQueueMetadata($queue);

        return $meta->getApproximateMessageCount();
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {

        $queue = $this->getQueue($queue);

        try {

            $this->azure->createMessage($queue, $payload);

        } catch (ServiceException $exception) {

            // Automatically create queues in Azure Storage
            if ($this->autoCreateQueues and $exception->getCode() == 404) {

                $this->azure->createQueue($queue);
                $this->azure->createMessage($queue, $payload);

            } else {
                throw $exception;
            }
        }
    }

    /**
     * @param string $job
     * @param string $data
     * @return string
     */
    protected function createPayload($job, $data = '')
    {
        $payload = parent::createPayload($job, $data);

        // Automatic base64 encode
        // Azure prefers base64 encoded messages
        if ($this->autoBase64) {
            $payload = base64_encode($payload);
        }

        return $payload;
    }


    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int $delay
     * @param  string|object $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {

        $payload = $this->createPayload($job, $data);

        $options = new CreateMessageOptions();
        $options->setVisibilityTimeoutInSeconds($this->secondsUntil($delay));

        $this->azure->createMessage($this->getQueue($queue), $payload, $options);
    }


    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {

        $queue = $this->getQueue($queue);

        $listMessagesOptions = new ListMessagesOptions();
        $listMessagesOptions->setVisibilityTimeoutInSeconds($this->visibilityTimeout);
        $listMessagesOptions->setNumberOfMessages(1);

        $listMessages = $this->azure->listMessages($queue, $listMessagesOptions);
        $messages = $listMessages->getQueueMessages();

        if (!empty($messages)) {
            return new AzureJob(
                $this->container,
                $this->azure,
                $messages[0],
                $this->connectionName,
                $queue,
                $this->autoBase64
            );
        }

        return null;
    }

    /**
     * @return int
     */
    public function getVisibilityTimeout()
    {
        return $this->visibilityTimeout;
    }

}