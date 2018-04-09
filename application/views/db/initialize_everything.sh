#!/bin/bash - 
#===============================================================================
#
#          FILE: initialize_everything.sh
# 
#         USAGE: ./initialize_everything.sh 
# 
#   DESCRIPTION: 
# 
#       OPTIONS: ---
#  REQUIREMENTS: ---
#          BUGS: ---
#         NOTES: ---
#        AUTHOR: Dilawar Singh (), dilawars@ncbs.res.in
#  ORGANIZATION: NCBS Bangalore
#       CREATED: 11/07/2016 09:50:06 AM
#      REVISION:  ---
#===============================================================================

set -o nounset                              # Treat unset variables as an error
set -x

echo "Populating database"
read -p "Mysql user hippouser password " pass

mysql -h localhost -u hippouser -p$pass < ./mysql_init.sql 
mysql -h localhost -u hippouser -p$pass < ./venues.sql 

