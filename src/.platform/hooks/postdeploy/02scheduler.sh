#!/usr/bin/env bash
# Crontab will place cron tasks as root if the user doesn't have a home directory.
mkdir -p /home/webapp ; chown -R webapp:webapp /home/webapp

# Adds a cron entry that checks for leader selection every 5 minutes
sudo -u webapp bash -c ". /opt/elasticbeanstalk/deployment/envvars ; /usr/bin/php artisan system:start:leaderselection"

# Does an initial leader selection check
sudo -u webapp bash -c ". /opt/elasticbeanstalk/deployment/envvars ; /usr/bin/php artisan aws:configure:leader"
