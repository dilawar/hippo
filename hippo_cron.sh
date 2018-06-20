#!/usr/bin/env bash

SCRIPT_DIR="$(cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
export HTTP_PROXY=http://proxy.ncbs.res.in:3128/
php $SCRIPT_DIR/index.php cron run
