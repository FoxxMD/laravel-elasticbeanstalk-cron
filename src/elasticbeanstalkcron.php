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
     | AWS REGION
     |--------------------------------------------------------------------------
     |
     | Sets which AWS region when looking using the ec2 client,
     | defaults to us-east-1 if not set.
     |
     */
    'region' => env('AWS_REGION', 'us-east-1'),


];
