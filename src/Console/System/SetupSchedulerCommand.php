<?php

namespace FoxxMD\LaravelElasticBeanstalkCron\Console\System;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SetupSchedulerCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'system:start:cron
                        {--overwrite : If set the CRON tab for this system will be overwritten}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure this system\'s CRON to use Laravel\'s scheduler.';

    public function __construct(ConfigRepository $config)
    {
        parent::__construct();

        $this->config = $config;

    }

    public function handle()
    {
        $this->info('Initializing CRON Setup...');

        $overwrite = $this->option('overwrite');

        if (!$overwrite) {
            $output = shell_exec('crontab -l');
        } else {
            $this->info('Overwriting previous CRON contents...');
            $output = null;
        }

        if (!is_null($output) && strpos($output, 'schedule:run') !== false) {
            $this->info('Already found Scheduler entry! Not adding.');
        } else {
            $path = $this->config->get('elasticbeanstalkcron.path', '/var/app/current/artisan');
            // using opt..envvars makes sure that environmental variables are loaded before we run artisan
            // http://georgebohnisch.com/laravel-task-scheduling-working-aws-elastic-beanstalk-cron/
            file_put_contents('/tmp/crontab.txt', $output . '* * * * * . /opt/elasticbeanstalk/support/envvars && /usr/bin/php ' . $path . ' schedule:run >> /dev/null 2>&1' . PHP_EOL);
            echo exec('crontab /tmp/crontab.txt');
        }

        $this->info('Schedule Cron Done!');
    }
}
