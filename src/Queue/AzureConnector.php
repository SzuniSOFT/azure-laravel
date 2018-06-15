<?php


namespace SzuniSoft\Azure\Laravel\Queue;


use Illuminate\Queue\Connectors\ConnectorInterface;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureConnector implements ConnectorInterface {

    /**
     * @var array
     */
    protected $defaultConfig;

    /**
     * AzureConnector constructor.
     * @param array $defaultConfig
     */
    public function __construct(array $defaultConfig = null)
    {

        if (isset($defaultConfig['default'])) {
            $defaultConfig['queue'] = $defaultConfig['default'];
            unset($defaultConfig['default']);
        }

        $this->defaultConfig = $defaultConfig ?: [];
    }

    /**
     * @return array
     */
    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {

        $config = array_merge([
            'auto_base64' => false,
            'auto_create_queue' => false
        ],
            $this->defaultConfig,
            $config
        );

        $connectionString = isset($config['connection_string'])
            ? $config['connection_string']
            : 'DefaultEndpointsProtocol=' . $config['protocol'] . ';AccountName=' . $config['account_name'] . ';AccountKey=' . $config['key'];

        $queueRestProxy = QueueRestProxy::createQueueService($connectionString);
        return new AzureQueue($queueRestProxy, $config['queue'], $config['timeout'], $config['auto_base64'], $config['auto_create_queue']);
    }
}