#!/usr/bin/env bash

PHP=/usr/bin/php
if [ -d /opt/rh/rh-php56 ]; then
    source /opt/rh/rh-php56/enable
    PHP=/opt/rh/rh-php56/root/usr/bin/php
fi

function log_msg
{
    echo $1
    NOW=$(date +"%Y_%m_%d__%H_%M_%S")
    if [[ -f /var/log/hippo.log ]]; then
        echo "$NOW : $1" >> ${LOG_FILE}
    fi
}

SCRIPT_DIR="$(cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
export http_proxy=http://proxy.ncbs.res.in:3128
export https_proxy=http://proxy.ncbs.res.in:3128
LOG_FILE=/var/log/hippo.log

# We must CD to script dir else include paths will not work.
( 
    cd $SCRIPT_DIR
    log_msg "$USER running all cron jobs"
    FILES=`find ${SCRIPT_DIR}/cron_jobs -name "*.php"`
    for _file in $FILES; do
        log_msg "Executing $_file"
        $PHP -f $_file
        log_msg "Status of previous command $?"
    done
)

# cleaup data folder.
(
    cd $SCRIPT_DIR/data && git clean -fxd -e "_mails*" .
)
