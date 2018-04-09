#/bin/bash -
#===============================================================================
#
#          FILE: tex2pdf.sh
#
#         USAGE: ./tex2pdf.sh
#
#   DESCRIPTION: 
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
set -x
set -o nounset                                  # Treat unset variables as an error
INFILE=$(readlink -f $1)
EXTRA=""
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
( 
    cd /tmp
    # Run the command two times. Sometimes it does not add images.
    kpsewhich --var-value=TEXMFVAR
    export TEXMFVAR=/var/www/.texlive2016/texmf-var/
    pdflatex $EXTRA --output-directory=$SCRIPT_DIR/data/ "$INFILE"
    pdflatex $EXTRA --output-directory=$SCRIPT_DIR/data/ "$INFILE"
)
