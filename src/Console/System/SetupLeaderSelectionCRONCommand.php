<?php

namespace FoxxMD\LaravelElasticBeanstalkCron\Console\System;

use Illuminate\Console\Command;

class SetupLeaderSelectionCRONCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'system:start:leaderselection
                            {--overwrite : If set the CRON tab for this system will be overwritten}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure this system\'s CRON to periodically (every 5 minutes) run leader selection.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Initializing CRON Leader Setup...');

        $overwrite = $this->option('overwrite');

        if (!$overwrite) {
            $output = shell_exec('crontab -l');
        } else {
            $this->info('Overwriting previous CRON contents...');
            $output = null;
        }

        if (!is_null($output) && strpos($output, 'aws:configure:leader') !== false) {
            $this->info('Already found Leader Selection entry! Not adding.');
        } else {
            // using opt..envvars makes sure that environmental variables are loaded before we run artisan
            // http://georgebohnisch.com/laravel-task-scheduling-working-aws-elastic-beanstalk-cron/
            $interval = config('elasticbeanstalkcron.interval', 5);
            file_put_contents('/tmp/crontab.txt', $output . "*/$interval * * * * . /opt/elasticbeanstalk/support/envvars && /usr/bin/php /var/app/current/artisan aws:configure:leader >> /dev/null 2>&1" . PHP_EOL);
            echo exec('crontab /tmp/crontab.txt');
        }

        $this->info('Leader Selection CRON Done!');
    }
}