#!/bin/bash 
set -x
set -e
STAMP=$(date)
echo "${STAMP}: Running cron  " >> /var/log/hippo.log
SCRIPT_DIR="$(cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
php $SCRIPT_DIR/index.php cron run
