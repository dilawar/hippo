"""concurrent_usage.py:

"""

__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
import os
from db_connect import db_
import datetime

def dbTime( d ):
    return d.strftime( '%H:%M' )

def overlapping( venueA, venueB ):
    overlapping = 0
    for e1 in venueA:
        e1st = e1['start_time']
        e1et = e1['end_time']
        for e2 in filter( lambda x: x['date'] == e1['date'], venueB):
            e2st = e2['start_time']
            e2et = e2['end_time']
            if e1et < e2st or e1st > e2et:
                pass
            else:
                overlapping += 1
    return overlapping

cur = db_.cursor( dictionary = True )
cur.execute( 'SELECT * FROM events WHERE status="VALID" AND class!="CLASS"' )
entries = [ ]
venues_ = set( )
for row in cur.fetchall( ):
    entries.append( row )
    venues_.add( row[ 'venue' ] )

print( 'Total %d entries' % len( entries ) )

malgova = filter( lambda x:'gova' in x['venue'], entries )
lh2 = filter( lambda x:'LH1' in x['venue'], entries )
print( 'Total overlapping %d' % overlapping(malgova, lh2) )
