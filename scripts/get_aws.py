#!/usr/bin/env python3

"""get_aws.py:

"""
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import os
import sys
import html2other
from db_connect import db_

aws_ = [ ]

def main( ):
    global db_
    global titles_, abstract_
    cur = db_.cursor( dictionary = True )
    cur.execute( 'SELECT title, abstract FROM annual_work_seminars' )
    for a in cur.fetchall( ):
        aws_.append( '<br> %s </br>' % a[ 'title' ] + '<br>' + a['abstract'] )

    aws = html2other.tomd( ' '.join( aws_ ) )
    with open( '/tmp/aws.txt', 'w' ) as f:
        f.write( aws )

if __name__ == '__main__':
    main()
