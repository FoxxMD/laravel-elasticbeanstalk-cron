<?php

namespace FoxxMD\LaravelElasticBeanstalkCron;

use FoxxMD\LaravelElasticBeanstalkCron\Console\AWS\ConfigureLeaderCommand;
use FoxxMD\LaravelElasticBeanstalkCron\Console\System\SetupLeaderSelectionCRONCommand;
use FoxxMD\LaravelElasticBeanstalkCron\Console\System\SetupSchedulerCommand;
use Illuminate\Support\ServiceProvider;

class ElasticBeanstalkCronProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommands();
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/.platform' => base_path('/.platform'),
            __DIR__ . '/elasticbeanstalkcron.php' => config_path('elasticbeanstalkcron.php')
        ], 'ebcron');
    }

    protected function registerConsoleCommands()
    {
        $this->commands([
            ConfigureLeaderCommand::class,
            SetupLeaderSelectionCRONCommand::class,
            SetupSchedulerCommand::class
        ]);
    }
}
