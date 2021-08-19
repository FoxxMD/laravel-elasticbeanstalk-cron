<?php

namespace FoxxMD\LaravelElasticBeanstalkCron\Console\AWS;

use Aws\Ec2\Ec2Client;
use Functional as F;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

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
     * @var ConfigRepository
     */
    protected $config;

    public function __construct(ConfigRepository $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    public function handle()
    {

        $client = new Ec2Client([
            'credentials' => [
                'key' => $this->config->get('elasticbeanstalkcron.key', ''),
                'secret' => $this->config->get('elasticbeanstalkcron.secret', ''),
            ],
            'region'  => $this->config->get('elasticbeanstalkcron.region', 'us-east-1'),
            'version' => 'latest',
        ]);

        $this->info('Initializing Leader Selection...');

        // Only do cron setup if environment is configured to use it (This way we don't accidentally run on workers)
        if ((bool) $this->config->get('elasticbeanstalkcron.enable', false)) {
            // AL2 is using IMDSv2 which use session token
            // https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/configuring-instance-metadata-service.html

            // get token first, check to see if we are in an instance
            $ch = curl_init('http://169.254.169.254/latest/api/token'); //magic ip from AWS
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-aws-ec2-metadata-token-ttl-seconds: 21600']);

            if ($token = curl_exec($ch)) {
                $ch = curl_init('http://169.254.169.254/latest/meta-data/instance-id');
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-aws-ec2-metadata-token: ' . $token]);
                $instanceId = curl_exec($ch);
                $this->info('Instance ID: ' . $instanceId);

                // Get this instance metadata so we can find the environment it's running in
                $tags = $info = $client->describeInstances([
                    'Filters' => [
                        [
                            'Name'   => 'instance-id',
                            'Values' => [$instanceId],
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
                $info = $client->describeInstances([
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
                    if ($oldestInstance['InstanceId'] == $instanceId) {
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
