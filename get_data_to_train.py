#!/usr/bin/env python2.7

"""get_aws.py:

"""
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
sys.path.append( '..' )

import os
import html2other
import re
from db_connect import db_

data_ = [ ]

def strip_image( txt ):
    pat = re.compile( r'(\<img.*?src=\".+?\".*?\s*\/\>)', re.DOTALL )
    return pat.sub( '', txt )

def main( ):
    global db_
    global titles_, abstract_
    global data_

    cur = db_.cursor( dictionary = True )

    cur.execute( 'SELECT title, description FROM talks' )
    for a in cur.fetchall( ):
        data_.append( '<br> %s </br>' % a[ 'title' ] + '<br>' 
                + strip_image( a['description'] ) )

    cur.execute( 'SELECT title, abstract FROM annual_work_seminars' )
    for a in cur.fetchall( ):
        data_.append( '<br> %s </br>' % a[ 'title' ] + '<br>' + strip_image(
            a['abstract'] ) 
            )

    aws = html2other.tomd( ' '.join( data_ ) )
    with open( '/tmp/data.txt', 'w' ) as f:
        f.write( aws )

if __name__ == '__main__':
    main()
