# Queue

This driver uses the Azure Storage Account queue to sending jobs (as messages).

## Configuration

You must specify the queue connection in the ``config/queue.php`` file like:

```php

'connections' => [
    
    ...
    
    'azure' => [
    
        // Mandatory fields
        'driver' => 'azure', // The driver
        'default' => 'default' // Your default queue in the storage,
        
        // Optional fields
        'auto_create_queue' => true, // Will automatically create queues if don't exist
        'auto_base64' => true, // Will automatically base64 jobs. It's recommended by Azure
        'timeout' => 60, // Azure visibility timeout
        
        // Override fields
        'portocol' => 'https', // Connection protocol, recommended to leave it as it is
        'account_name' => env('AZURE_ACCOUNT_NAME'), // Your Storage account name
        'key' => env('AZURE_ACCOUNT_KEY'), // Your Storage Account key
    ]
    
]

```

## Usage

Just use it as you would normally

```php

dispatch(new MyJob())
    ->onQueue('my-desired-queue') // Note this is Azure storage queue
    ->onConnection('azure') // This is the connection you defined in config/queue.php

```

[See how to export package configuration.](../README.md#configuration)