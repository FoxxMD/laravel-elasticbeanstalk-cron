<?php

namespace FoxxMD\LaravelElasticBeanstalkCron\Console\AWS;

use Aws\Ec2\Ec2Client;
use Functional as F;
use Illuminate\Console\Command;

class ConfigureLeaderCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'aws:configure:leader';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure leader ec2 instance';

    /**
     * @var Ec2Client
     */
    protected $ecClient;

    public function __construct()
    {
        parent::__construct();

        $client = new Ec2Client([
            'region'  => getenv('AWS_REGION') ?: 'us-east-1',
            'version' => 'latest',
        ]);

        $this->ecClient = $client;
    }

    public function handle()
    {
        $this->info('Initializing Leader Selection...');

        // Only do cron setup if environment is configured to use it (This way we don't accidentally run on workers)
        if (getenv('USE_CRON') == 'true') {
            //check to see if we are in an instance
            $ch = curl_init('http://169.254.169.254/latest/meta-data/instance-id'); //magic ip from AWS
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            if ($result = curl_exec($ch)) {
                $this->info('Instance ID: ' . $result);

                // Get this instance metadata so we can find the environment it's running in
                $tags = $info = $this->ecClient->describeInstances([
                    'Filters' => [
                        [
                            'Name'   => 'instance-id',
                            'Values' => [$result],
                        ],
                    ],
                ])->get('Reservations')[0]['Instances'][0]['Tags'];

                // Get environment name
                $environmentName = F\first($tags, function ($tagArray) {
                    return $tagArray['Key'] == 'elasticbeanstalk:environment-name';
                })['Value'];
                $this->info('Environment: ' . $environmentName);

                $this->info('Getting Instances with Environment: ' . $environmentName);

                // Get instances that have this environment tagged
                $info = $this->ecClient->describeInstances([
                    'Filters' => [
                        [
                            'Name'   => 'tag-value',
                            'Values' => [$environmentName],
                        ],
                    ],
                ]);
                $instances = F\map($info->get('Reservations'), function ($i) {
                    return current($i['Instances']);
                });
                $this->info('Getting potential instances...');

                // Only want instances that are running
                $candidateInstances = F\select($instances, function ($instanceMeta) {
                    return $instanceMeta['State']['Code'] == 16;
                });

                $leader = false;

                if (!empty($candidateInstances)) { //there are instances running
                    if (count($candidateInstances) > 1) {
                        // if there is more than one we sort by launch time and get the oldest
                        $this->info('More than one instance running, finding the oldest...');
                        $oldestInstance = F\sort($candidateInstances, function ($left, $right) {
                            return $left['LaunchTime'] > $right['LaunchTime'];
                        })[0];
                    } else {
                        $this->info('Only one instance running...');
                        $oldestInstance = reset($candidateInstances);
                    }
                    if ($oldestInstance['InstanceId'] == $result) {
                        // if this instance is the oldest instance it's the leader
                        $leader = true;
                    }
                } else {
                    $this->info('No candidate instances found. \'O Brave New World!');
                    $leader = true;
                }


                // No leader is running so we'll setup this one as the leader
                // and create a cron entry to run the scheduler
                if ($leader) {
                    $this->info('We are the Leader! Initiating Cron Setup');
                    $this->call('system:start:cron');
                } else {
                    // Instance was found, don't do any cron stuff
                    $this->info('We are not a leader instance :( Maybe next time...');
                    $this->info('Leader should be running on Instance ' . $leader['InstanceId']);
                }


                $this->info('Leader Selection Done!');
            } else {
                // Probably be run from your local machine
                $this->error('Did not detect an ec2 environment. Exiting.');
            }
        } else {
            $this->info('USE_CRON env var not set. Exiting.');
        }
    }
}
