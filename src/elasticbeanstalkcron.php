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
     | The AWS region for the EC2 client. Resolved from AWS_REGION or
     | AWS_DEFAULT_REGION env var. Falls back to IMDS if neither is set.
     |
     */
    'region' => env('AWS_REGION', env('AWS_DEFAULT_REGION')),


];
