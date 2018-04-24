#!/usr/bin/env python3

import networkx as nx
import itertools
from db_connect import db_

g_ = nx.DiGraph( )
venues_ = set( )
slots_ = set( )
courses_ = set( )
entries_ = [ ]
max_weight_ = 0

def init( ):
    global g_
    global entries_
    global max_weight_
    cur = db_.cursor( dictionary = True )

    g_.add_node( 'SOURCE' )
    g_.add_node( 'SINK' )

    cur.execute( "SELECT * FROM upcoming_course_schedule WHERE status='VALID'" )
    for row in cur:
        entries_.append( row )
        slots_.add( row[ 'slot' ] )
        venues_.add( row[ 'venue' ] )
        courses_.add( row[ 'course_id' ] )
        if int( row[ 'weight'] ) > max_weight_:
            max_weight_ = int( row[ 'weight' ]) + 1

    # SOURCE to slot.
    for slot, venue in itertools.product( slots_, venues_ ):
        print( 'Slot %s and venue %s' % (slot, venue ) )
        key = slot + ':' + venue
        g_.add_node( key )
        g_.add_edge( 'SOURCE', key, capacity = 1 )

    #  To sink
    for c in courses_:
        print( '[INFO] Course is %s' % c )
        g_.add_node( c )
        g_.add_edge( c, 'SINK', capacity = 1 )

    # Now SLOT to COURSE
    for e in entries_:
        venue, slot = e['venue'], e['slot']
        key = slot + ':' + venue
        g_.add_edge( key, e[ 'course_id' ], capacity = 100
                , weight = max_weight_ - int( e['weight'] )
                )

    print( '[INFO] Max Weight is %d' % max_weight_ )
    assignments = { }
    solution = nx.min_cost_flow( g_, 'SOURCE', 'SINK' )
    for k in solution:
        if k in [ 'SOURCE', 'SINK' ]:
            continue
        vs = solution[ k ]
        for v in vs:
            if v == 'SINK':
                continue
            assignments[ v ] = k.split( ':' )

    # Remove all previous assignments.
    cur.execute( """
        UPDATE upcoming_course_schedule
        SET alloted_slot=NULL AND alloted_venue=NULL
        """ )
    db_.commit( )

    for k in assignments:
        slot, venue =  assignments[k]
        query = """
                UPDATE upcoming_course_schedule
            SET alloted_slot='{s}',alloted_venue='{v}'
                WHERE
            course_id='{c}' AND slot='{s}'
                AND venue='{v}'""".format(s=slot, v=venue, c=k)
        cur.execute( query )
    db_.commit( )

def main( ):
    init( )

if __name__ == '__main__':
    main()
