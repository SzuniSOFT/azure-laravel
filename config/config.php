<?php

return [

    /*
     * These credentials will be used by default.
     * You can override it in any other storage / queue configuration.
     * */
    'credentials' => [

        'protocol' => 'https',
        'accountname' => env('AZURE_ACCOUNT_NAME'),
        'key' => env('AZURE_ACCOUNT_KEY')

    ],

    'storage' => [

        /*
        * The Azure storage driver type
         * To make your best decision you may visit this guy here:
         * https://docs.microsoft.com/en-us/azure/storage/common/storage-decide-blobs-files-disks
         *
         * Possible values are:
         * - blob
         * - file
         *
         * default: azure.blob
        */
        'default' => 'blob',

        'types' => [

            'blob' => [

                /*
                 * The default container name in the Azure Storage for Blob FS.
                 * */
                'container' => env('AZURE_STORAGE_BLOB_CONTAINER')
            ],

            'file' => [

                /*
                 * The default share name in thee Azure Storage for Common FS.
                 * */
                'share' => env('AZURE_STORAGE_FILE_SHARE')
            ]

        ],

    ]

];