"""find_faculty.py: 

    Search intranet for faculy.

"""
    
__author__           = "Me"
__copyright__        = "Copyright 2016, Me"
__credits__          = ["NCBS Bangalore"]
__license__          = "GNU GPL"
__version__          = "1.0.0"
__maintainer__       = "Me"
__email__            = ""
__status__           = "Development"

import sys
import os
import matplotlib.pyplot as plt
import numpy as np
import urllib2 
from bs4 import BeautifulSoup
import html2text
import re


def queryIntranet( query, page = 0 ):
    url =  "https://intranet.ncbs.res.in/people-search?%s&page=%s" %( query , page )
    req = urllib2.Request( url )
    response = urllib2.urlopen( req )
    text =  response.read( ) 
    return BeautifulSoup( text, 'html.parser' )

def data( text ):
    m = re.search( '\[(.+)\]', text )
    if m:
        return m.group(1)
    else:
        return ''

def main( ):
    with open( "faculty.txt", "w" ) as f:
        f.write( "fname,mname,lname,login,email\n")
    for page in [ 0, 1, 2 ]:
        soup = queryIntranet( "field_personal_group_tid=111", page )
        for t in soup.find_all( 'tr' ):
            t = str(t)
            if "Faculty" in t:
                fline = html2text.html2text( t )
                faculty = filter(None, fline.split( '\n' ) )
                name, email = filter(None, map( data, faculty ))[0:2]
                name = name.split()
                fname, mname, lname = name[0], " ".join(name[1:-2]), name[-1]
                print email
                with open( "faculty.txt", "a" ) as f:
                    f.write( "%s,%s,%s,%s,%s\n" % (fname, mname, lname
                        , email.split('@')[0], email ) )
    
    print( "Wrote faculty names to faculty.txt" )



if __name__ == '__main__':
    main()
