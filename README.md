# Laravel 6 - 12.x Task Scheduler with Elastic Beanstalk

*Ensure one instance in an Elastic Beanstalk environment is running Laravel's Scheduler*

A common [problem](https://stackoverflow.com/questions/14077095/aws-elastic-beanstalk-running-a-cronjob) [many](http://culttt.com/2016/02/08/setting-up-and-using-cron-jobs-with-laravel-and-aws-elastic-beanstalk/) [people](https://medium.com/@joelennon/running-cron-jobs-on-amazon-web-services-aws-elastic-beanstalk-a41d91d1c571#.i53d41sci) have encountered with Amazon's [Elastic Beanstalk](https://aws.amazon.com/elasticbeanstalk/) is maintaining a single instance in an environment that runs Laravel's Task Scheduler. Difficulties arise because auto-scaling does not guarantee any instance is run indefinitely and there are no "master-slave" relationships within an environment to differentiate one instance from the rest.

Although Amazon has provided a [solution](http://stackoverflow.com/a/28719447/1469797) it involves setting up a worker tier and then, potentially, creating new routes/methods for implementing the tasks that need to be run. Yuck!

**This package provides a simple, zero-setup solution for maintaining one instance within an Elastic Beanstalk environment that runs the Task Scheduler.**

## Amazon Linux 1 is deprecated and Amazon Linux 2023 is recommended

Amazon Linux 1 (AL1) is already retired, even Amazon Linux 2 (AL2) will soon going to be unsupported too, so it's recommended to migrate to use Amazon Linux 2023 (AL2023)
https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/using-features.migration-al.html
Starts from this release will only support AL2023, please use previous releases for use in AL2, with this release will also drop support for Laravel 5 (PHP 7) since EB only support starting from PHP 8.1

## How Does It Work?

Glad you asked! The below process **is completely automated** and only requires that you publish the `.platform` folder to the root of your application.

### 1. Use Elastic Beanstalk's Advanced Configuration to run CRON setup commands

EB applications since AL2 can contain [platform hooks](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/platforms-linux-extend.html) that provides advanced configuration for an EB environment, called `.platform`.

This package provides a configuration file that runs two commands on deployment (every instance initialization) that setup the conditions needed to run the Task Scheduler on one instance:

### 2. Run `system:start:leaderselection`

This is the first command that is run on deployment. It configures the instance's Cron to run **Leader Selection** at a configured interval (default = 5 minutes)

### 3. Run **Leader Selection** `aws:configure:leader`

This is the **Leader Selection** command. It does the following:

* Get the Id of the Instance this deployment is running on
* Get the `EnvironmentName` of this Instance. (When running in an EB environment all EC2 instances have the same `EnvironmentName`)
* Get all running EC2 instances with that `EnvironmentName`
* Find the **earliest launched instance**

If this instance is the earliest launched then it is deemed the **Leader** and runs `system:start:cron`

### 4. Run `system:start:cron`

This command is run **only if the current instance running Leader Selection is the Leader**. It inserts another entry in the instance's Cron to run [Laravel's Scheduler](https://laravel.com/docs/12.x/scheduling).

### That's it!

Now only one instance, the earliest launched, will have the scheduler inserted into its Cron. If that instance is terminated by auto-scaling a new Leader will be chosen within 5 minutes (or the configured interval) from the remaining running instances.

## Installation

Require this package

```bash
composer require "foxxmd/laravel-elasticbeanstalk-cron"
```

Then, publish the **.platform** folder and configuration file

```bash
php artisan vendor:publish --tag=ebcron
```

Don't forget to add +x permission to the EB Platform Hooks scripts ([no longer required for Amazon Linux platform that released on or after April 29, 2022](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/platforms-linux-extend.hooks.html#platforms-linux-extend.hooks.more))

```bash
find .platform -type f -iname "*.sh" -exec chmod +x {} +
```

## Configuration

In order for Leader Selection to run a few environmental variables must be present:

* **USE_CRON** = true -- Must be set in order for Leader Selection to occur. (This can be used to prevent Selection from occurring on undesired environments IE Workers, etc.)
* **AWS_ACCESS_KEY_ID** -- Needed for read-only access to EC2 client
* **AWS_SECRET_ACCESS_KEY** -- Needed for read-only access to EC2 client
* **AWS_REGION** -- Sets which AWS region when looking using the EC2 client, defaults to `us-east-1` if not set.

These can be included in your **.env** or, for EB, in the environment's configuration section.

## Contributing

Make a PR for some extra functionality and I will happily accept it :)

## License

This package is licensed under the [MIT license](https://github.com/FoxxMD/laravel-elasticbeanstalk-cron/blob/master/LICENSE.txt).
