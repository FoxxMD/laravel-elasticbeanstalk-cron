<?php

namespace FoxxMD\LaravelElasticBeanstalkCron\Console\System;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

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
    protected $description = "Configure this system's CRON to periodically (every 5 minutes) run leader selection.";

    /**
     * @var ConfigRepository
     */
    private $config;

    /**
     * SetupLeaderSelectionCRONCommand constructor.
     *
     * @param ConfigRepository $config
     */
    public function __construct(ConfigRepository $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    public function handle()
    {
        $this->info('Initializing CRON Leader Setup...');

        $overwrite = $this->option('overwrite');

        if (!$overwrite) {
            $output = shell_exec('crontab -u webapp -l');
        } else {
            $this->info('Overwriting previous CRON contents...');
            $output = null;
        }

        if (!is_null($output) && strpos($output, 'aws:configure:leader') !== false) {
            $this->info('Already found Leader Selection entry! Not adding.');
        } else {
            $interval = $this->config->get('elasticbeanstalkcron.interval', 5);

            // using opt..envvars makes sure that environmental variables are loaded before we run artisan
            // http://georgebohnisch.com/laravel-task-scheduling-working-aws-elastic-beanstalk-cron/
            file_put_contents(
                '/tmp/crontab.txt',
                $output . "*/$interval * * * * . /opt/elasticbeanstalk/support/envvars &&" .
                " /usr/bin/php /var/app/current/artisan aws:configure:leader >> /dev/null 2>&1" . PHP_EOL
            );

            echo exec('crontab -u webapp /tmp/crontab.txt');
        }

        $this->info('Leader Selection CRON Done!');
    }
}
