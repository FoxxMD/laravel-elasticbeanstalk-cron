#!/usr/bin/env bash
# Prepare bash importable env file
sed -r -n 's/.+=/export &"/p' /opt/elasticbeanstalk/deployment/env | sed -r -n 's/.+/&"/p' > /opt/elasticbeanstalk/deployment/envvars
