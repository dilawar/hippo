#!/usr/bin/env python3
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2016, Dilawar singh <dilawars@ncbs.res.in>"
__credits__          = ["NCBS Bangalore"]
__license__          = "GNU GPL"
__version__          = "1.0.0"
__maintainer__       = "Dilawra Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development/Production"

import sys
import os
import datetime 
from db_connect import db_
from global_data import *
import itertools
import networkx as nx

def spec_short( spec ):
    return  ''.join( [ x.strip()[0] for x in spec.split( ) ] )

def getSpecialization( cur, piOrHost ):
    cur.execute( "SELECT specialization FROM faculty WHERE email='%s'" % piOrHost )
    a = cur.fetchone( )
    return a['specialization']

def init( cur ):
    """
    Create a temporaty table for scheduling AWS
    """

    global db_

    cur.execute( 'DROP TABLE IF EXISTS aws_temp_schedule' )
    cur.execute( 
            '''
            CREATE TABLE IF NOT EXISTS aws_temp_schedule 
            ( speaker VARCHAR(40) PRIMARY KEY, date DATE NOT NULL ) 
            ''' 
        )
    db_.commit( )
    cur.execute( 
        """
        SELECT * FROM logins WHERE eligible_for_aws='YES' AND status='ACTIVE'
        ORDER BY login 
        """
        )
    for a in cur.fetchall( ):
        speakers_[ a['login'].lower() ] = a
        spec = a['specialization']
        if spec is None:
            pi = a['pi_or_host']
            if pi is None:
                continue
            spec = getSpecialization( cur, pi )

        spec = spec or 'UNSPECIFIED'
        specialization_[ a['login'] ] = spec
    
    cur.execute( """SELECT * FROM holidays ORDER BY date""")
    for a in cur.fetchall( ):
        if a[ 'schedule_talk_or_aws' ] == 'NO':
            holidays_[ a['date'] ] = a


def get_data( ):
    global db_
    try:
        cur = db_.cursor( dictionary = True )
    except Exception as e:
        print( 
        '''If complain is about dictionary keyword. Install 
        https://pypi.python.org/pypi/mysql-connector-python-rf/2.2.2
        using easy_install'''
        )
        quit( )
    init( cur )

    # Entries in this table are usually in future.
    cur.execute( 'SELECT * FROM upcoming_aws' )
    for a in cur.fetchall( ):
        aws_[ a[ 'speaker' ] ].append( a )
        upcoming_aws_[ a['speaker'].lower( ) ] = a['date']
        # Keep the number of slots occupied at this day.
        upcoming_aws_slots_[ a['date'] ].append( a['speaker'] )

    # Now get all the previous AWSs happened so far.
    cur.execute( 'SELECT * FROM annual_work_seminars' )
    for a in cur.fetchall( ):
        aws_[ a[ 'speaker' ].lower() ].append( a )

    for a in aws_:
        # Sort a list in place.
        aws_[a].sort( key = lambda x : x['date'] )
        # print( a, [ x['date'] for x in aws_[a] ] )

    # Select all aws scheduling requests which have been approved.
    cur.execute( "SELECT * FROM aws_scheduling_request WHERE status='APPROVED'" )
    for a in cur.fetchall( ):
        aws_scheduling_requests_[ a[ 'speaker' ].lower( ) ] = a

    # Now pepare output file.
    speakers = speaker_data( )
    slots = slots_data( )

    graph = nx.DiGraph( )
    graph.add_node( 'source' )
    graph.add_node( 'sink' )
    for s in speakers:
        graph.add_node( s, **speakers[s] )
        graph.add_edge( 'source', s, weight = 0, capacity = 1, cost = 0 )

    for date, i in slots:
        graph.add_node( (date,i), date = '%s' % date, index = i )
        graph.add_edge( (date,i), 'sink', weight=0, capacity = 1, cost = 0 )

    return graph 

def slots_data( ):
    today = datetime.date.today( )
    monday0 = today + datetime.timedelta( days = 7 - today.weekday( ) )
    validSlots = [ ]
    for dayi in range( 60 ):
        monday = monday0 + datetime.timedelta( days = 7 * dayi )
        if monday in holidays_:
            print( 'Monday %s is holiday' % monday  )
            continue
        nSlots = 3
        if monday in upcoming_aws_slots_:
            nAWS = len( upcoming_aws_slots_[monday] )
            print( '%d AWSs are scheduled on this date %s' % (nAWS, monday ))
            nSlots -= nAWS

        for sloti in range(0, nSlots ):
            validSlots.append( (monday,sloti) )
    return validSlots

def speaker_data( ):
    speakers = { }
    keys = tuple( 'login,pi_or_host,specialization,nAWS,last_aws_on'.split( ','))
    for l in speakers_:
        if l in upcoming_aws_:
            print( '-> Name is in upcoming AWS. Ignoring' )
            continue

        piOrHost = speakers_[l].get('pi_or_host', 'UNKNOWN')
        vals = [ ]
        vals.append(l)
        vals.append( '%s' % piOrHost )
        spec = spec_short( specialization_.get( l, 'UNKNOWN' ) )
        vals.append( spec )

        nAws = len( aws_.get( l, [] ) )
        vals.append( '%d' % nAws )
        vals.append( '%s' % lastAwsDate( l ) )

        d = dict( zip(keys, vals) )
        speakers[ l ] = d 

    return speakers

    
def lastAwsDate( speaker ):
    if speaker in aws_:
        awss = [ aws['date'] for aws in aws_[ speaker ] ]
        return sorted( awss )[-1]
    else:
        # joined date.
        return speakers_[speaker][ 'joined_on' ]

def main( ):
    global db_
    data = get_data( )
    db_.close( )
    outfile = '_aws_data.gml'
    if len( sys.argv ) > 1:
        outfile = sys.argv[1]
    nx.write_graphml( data, outfile )
    print( 'Wrote graphml to %s' % outfile )

if __name__ == '__main__':
    main( )
