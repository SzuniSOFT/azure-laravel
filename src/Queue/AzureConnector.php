<?php


namespace SzuniSoft\Azure\Laravel\Queue;


use Illuminate\Queue\Connectors\ConnectorInterface;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureConnector implements ConnectorInterface {

    /**
     * Establish a queue connection.
     *
     * @param  array $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config) {

        $connectionString = isset($config['connection_string'])
            ? $config['connection_string']
            : 'DefaultEndpointsProtocol=' . $config['protocol'] . ';AccountName=' . $config['accountname'] . ';AccountKey=' . $config['key'];

        $queueRestProxy = QueueRestProxy::createQueueService($connectionString);
        return new AzureQueue($queueRestProxy, $config['queue'], $config['timeout']);
    }
}