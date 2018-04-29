#/usr/bin/env bash

#===============================================================================
#
#          FILE: tex2pdf.sh
#
#         USAGE: ./tex2pdf.sh
#
#   DESCRIPTION: Genrate a pdf file in /tmp directory.
#
#       OPTIONS: ---
#  REQUIREMENTS: ---
#          BUGS: ---
#         NOTES: ---
#        AUTHOR: Dilawar Singh (), dilawars@ncbs.res.in
#  ORGANIZATION: NCBS Bangalore
#       CREATED: Saturday 17 March 2018 11:54:26  IST
#      REVISION:  ---
#===============================================================================

set -e
# set -x
INFILE=$(readlink -f $1)
# SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
TEXFILENAME=$(basename $INFILE)
OUTFILENAME=${TEXFILENAME%.tex}.pdf
( 
    cd /tmp
    # Run the command two times. Sometimes it does not add images.
    kpsewhich --var-value=TEXMFVAR
    if [ -d /var/www/.texlive2016/texmf-var ];then 
        export TEXMFVAR=/var/www/.texlive2016/texmf-var/
    fi

    # LuaLaTex suffers from 'writable cache path' problem. See here
    # https://github.com/sharelatex/sharelatex/issues/450
    #lualatex --interaction nonstopmode --output-directory=/tmp "$INFILE"
    #lualatex --interaction nonstopmode --output-directory=/tmp "$INFILE"

    xelatex --interaction nonstopmode --output-directory=/tmp "$INFILE"
    xelatex --interaction nonstopmode --output-directory=/tmp "$INFILE"
)
