#!/usr/bin/env bash
# Prepare bash importable env file
sed -E -n 's/[^#]+/export &/ p' /opt/elasticbeanstalk/deployment/env > /opt/elasticbeanstalk/deployment/envvars
