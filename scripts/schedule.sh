#!/bin/bash -
#===============================================================================
#
#          FILE: schedule.sh
#
#         USAGE: ./schedule.sh
#
#   DESCRIPTION:
#
#       OPTIONS: ---
#  REQUIREMENTS: ---
#          BUGS: ---
#         NOTES: ---
#        AUTHOR: Dilawar Singh (), dilawars@ncbs.res.in
#  ORGANIZATION: NCBS Bangalore
#       CREATED: 02/05/2017 05:27:07 PM
#      REVISION:  ---
#===============================================================================

set -e
set -o nounset                              # Treat unset variables as an error

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
GRAPHMLFILE="/tmp/__aws_data.graphml"
$DIR/fetch_aws_data.py "$GRAPHMLFILE"
$DIR/schedule_aws_clean.py --gml "$GRAPHMLFILE"
