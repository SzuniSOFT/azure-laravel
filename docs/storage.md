# Storage

Available Drivers
- Azure Blob Storage
- Azure File Storage

## Azure Blob Storage

In order to make it work please add the following configuration to the ``config/filesystems.php`` config file:

```php

'disks' => [
    
    ...
    
    'azure' => [
    
        // Mandatory fields
        'driver' => 'azure.blob', // The driver
        'container' => env('AZURE_STORAGE_BLOB_CONTAINER') // Your default container,
        
        // Optional fields
        'auto_create_container' => true, // Will automatically create containers if don't exist
        
        // Override fields
        'portocol' => 'https', // Connection protocol, recommended to leave it as it is
        'account_name' => env('AZURE_ACCOUNT_NAME'), // Your Storage account name
        'key' => env('AZURE_ACCOUNT_KEY'), // Your Storage Account key
    ]
    
]

```

## Azure File Storage

To use the file storage driver please add these lines into the ``config/filesystems.php`` config file:

```php

'disks' => [
    
    ...
    
    'azure' => [
    
        // Mandatory fields
        'driver' => 'azure.file', // The driver
        'container' => env('AZURE_STORAGE_FILE_SHARE') // Your default container,
        
        // Optional fields
        'auto_create_share' => true, // Will automatically create shares if don't exist
        
        // Override fields
        'portocol' => 'https', // Connection protocol, recommended to leave it as it is
        'account_name' => env('AZURE_ACCOUNT_NAME'), // Your Storage account name
        'key' => env('AZURE_ACCOUNT_KEY'), // Your Storage Account key
    ]
    
]

```

## Known issues
_File storage driver does not support recursive item listing yet._

## Note on configuration overrides

The following driver specific configurations will be inherited by the package config file by default:
- protocol
- account_name
- key

So if you decide to use only one Storage Account you can specify these values only once and you don't have to repeat adding these credentials.

[See how to export package configuration.](../README.md#configuration)