<?php


namespace SzuniSoft\Azure\Laravel\Test\Queue\Fixtures;


use MicrosoftAzure\Storage\Queue\Models\QueueMessage;

class ListMessagesResult {

    /**
     * @var int
     */
    protected $count;
    /**
     * ListMessagesResult constructor.
     * @param int $count
     */
    public function __construct($count = 1)
    {
        $this->count = $count;
    }
    public function getQueueMessages()
    {
        if ($this->count == 0) return [];
        return array_fill(0, $this->count, new QueueMessage());
    }

}