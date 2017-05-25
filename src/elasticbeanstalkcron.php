<?php

return [


    /*
     |--------------------------------------------------------------------------
     | Enable
     |--------------------------------------------------------------------------
     |
     | Set the USE_CRON env variable to enable the cron. Defaults to false.
     |
     */
    'enable' => env('USE_CRON', false),


    /*
     |--------------------------------------------------------------------------
     | INTERVAL
     |--------------------------------------------------------------------------
     |
     | The interval, in minutes, that a Leader Selection check should be
     | run by the CRON
     |
     */
    'interval' => 5,


    /*
     |--------------------------------------------------------------------------
     | PATH
     |--------------------------------------------------------------------------
     |
     | Path to your artisan file. Defaults to /var/app/current/artisan
     | By default the root of your app is located at /var/app/current
     |
     */
    'path' => '/var/app/current/artisan',


    /*
     |--------------------------------------------------------------------------
     | AWS KEY
     |--------------------------------------------------------------------------
     |
     | AWS Key Needed for read-only access to ec2 client
     |
     */
    'key' => env('AWS_ACCESS_KEY_ID', null),


    /*
     |--------------------------------------------------------------------------
     | AWS SECERT
     |--------------------------------------------------------------------------
     |
     | Needed for read-only access to ec2 client
     |
     */
    'secret' => env('AWS_SECRET_ACCESS_KEY', null),


    /*
     |--------------------------------------------------------------------------
     | AWS REGION
     |--------------------------------------------------------------------------
     |
     | Sets which AWS region when looking using the ec2 client,
     | defaults to us-east-1 if not set.
     |
     */
    'region' => env('AWS_REGION', 'us-east-1'),


];
