#!/usr/bin/env python3

import sys
import csv
import networkx as nx
import compute_cost
import datetime
import itertools
import numpy as np
import random
import aws_helper
from db_connect import db_
from collections import defaultdict, Counter
from collections import OrderedDict

random.seed( 'HIPPO' )
np.random.seed( 10 )

g_ = None
ideal_gap_ = 7 * 55
specs_ = [ ]
facs_ = defaultdict( set )

def strToDate( date ):
    return datetime.datetime.strptime( date, '%Y-%m-%d' ).date()

def short( pi ):
    return pi.split('@')[0][:5]

def prune( p ):
    global g_
    global ideal_gap_
    speaker, slot = p
    lastAWS =  g_.node[speaker][ 'last_aws_on' ] 
    slotDate = g_.node[slot]['date'] 
    diffD = slotDate - lastAWS
    if (slotDate - lastAWS).days < ideal_gap_:
        return False
    return True 

def commit_schedule( schedule ):
    global db_
    cur = db_.cursor( )
    for date in sorted(schedule):
        for s in schedule[ date ]:
            query = """
                INSERT INTO aws_temp_schedule (speaker, date) VALUES ('{0}', '{1}')
                ON DUPLICATE KEY UPDATE date='{1}'
                """.format( s[ 'speaker' ], date )
            cur.execute( query )
    db_.commit( )
    print( '[INFO] Committed to database' )

def number_of_valid_speakers( date, sp ):
    speakers = filter( 
            lambda x: g_.node[x]['specialization'] == sp,
            g_.successors( 'source' ) 
            )
    speakers = filter( 
            lambda x: (date-g_.node[x]['last_aws_on']).days > ideal_gap_,
            speakers 
            )
    speakers = list( speakers )
    return len(speakers)

def select_randoom_spec( date, specs, sp = None ):
    global g_
    if sp is None:
        random.shuffle( specs )
        sp = random.choice( specs )
        # How many speakers are available for this spec.
        while number_of_valid_speakers( date, sp ) < 6:
            specs.remove( sp )
            sp  = random.choice( specs )
    try:
        specs.remove( sp )
    except ValueError as e:
        print( '%s not found in list' % sp )
    return sp

def init( ):
    global g_
    global specs_
    global facs_

    speakers = g_.successors( 'source' )
    for s in speakers:
        g_.node[s]['last_aws_on'] = strToDate( g_.node[s]['last_aws_on'] )
        spec = g_.node[s]['specialization'] 
        specs_.append( spec )
        pi = g_.node[s]['pi_or_host']
        facs_[ pi ].add( spec )
        facs_[ spec ].add( pi )

    slots = g_.predecessors( 'sink' )
    for s in slots:
        g_.node[s]['date'] = strToDate( g_.node[s]['date'] )

def assign_weight_method_a( edges ):
    global g_
    for speaker, slot in edges:
        add_edge( speaker, slot )


def add_edge( speaker, slot ):
    global ideal_gap_
    date, si = slot
    lastAWS = g_.node[ speaker ]['last_aws_on']
    dayDiff = (slot[0] - lastAWS).days
    if dayDiff < ideal_gap_:
        return 
    nAWS = int( g_.node[ speaker ]['nAWS'] )
    cost = compute_cost.computeCost( date, lastAWS, nAWS )
    g_.add_edge( speaker, slot, capacity = 1, weight = cost )

def assign_weight_method_b( edges, specializations = [ ] ):
    global specs_
    global g_
    slots = sorted( g_.predecessors('sink') )
    specs = specs_[:]

    if not specializations:
        prevDate = slots[0][0]
        sp = select_randoom_spec( prevDate, specs )
        for i, slot in enumerate( slots ):
            thisDate = slot[0]
            if thisDate != prevDate:
                prevDate = thisDate
                sp = select_randoom_spec( thisDate, specs )
            else:
                sp = select_randoom_spec( thisDate, specs, sp )
            specializations.append( sp )

    for slot, sp in zip( slots, specializations ):
        # Assign speicalization to node and add an arrow from possible speakers.
        g_.node[slot]['specialization'] = sp
        for speaker in g_.successors( 'source' ):
            ssp = g_.node[speaker]['specialization']
            if ssp == sp:
                add_edge( speaker, slot )

    return specializations


def construct_flow_graph( method = 'b' ):
    global g_
    speakers = g_.successors( 'source' )
    slots = g_.predecessors( 'sink' )
    pairs = itertools.product( speakers, slots )
    edges = filter( prune, pairs )
    if method == 'a':
        assign_weight_method_a( edges )
    elif method == 'b':
        specs = assign_weight_method_b( edges )
    else:
        raise RuntimeWarning('No method is specified' )

    print( 'Flow graph is constructed' )

def flow_to_solution( flow ):
    res = defaultdict( list )
    for u in flow:
        for v in flow[u]:
            if u in [ 'source', 'sink' ]:
                continue
            if v in [ 'source', 'sink' ]:
                continue
            if flow[u][v] > 0:
                res[v[0]].append( u )

    scheduled, cost = [ ], [ ]
    solution = OrderedDict( )
    for date in sorted( res ):
        speakers = [ ]
        for s in res[ date ]:
            scheduled.append( s )
            ndays = (date - g_.node[s]['last_aws_on']).days
            cost.append( ndays )
            lab = short( g_.node[s]['pi_or_host'] )
            _spec = g_.node[s]['specialization']
            n = g_.node[s]['nAWS']
            ss = '%20s %4s %s (%d)' % (s+'.'+lab, _spec,n,ndays)
            speakers.append( dict(speaker=s,lab=lab,spec=_spec,ndays=ndays) )
        solution[ date ] = speakers

    # Who are left 
    notScheduled = set( g_.successors( 'source' ) ) - set( scheduled )
    return solution, notScheduled

def print_solution( scheduled ):

    def toStr( d ):
        global g_
        speaker = d['speaker']
        pi = g_.node[ speaker ]['pi_or_host' ][:4]
        spec = g_.node[ speaker ]['specialization']
        ndays = d['ndays' ] 
        nAWS = g_.node[ speaker ]['nAWS']
        return '%20s (%4s %s) %s-%s ' % (speaker, spec, pi, nAWS, ndays)

    global g_
    ndays = [ ]
    for date in scheduled:
        print( date, end = ' ' )
        speakers = scheduled[ date ]
        for s in speakers:
            ndays.append( s['ndays' ] )
            print( toStr(s), end = '' )
        print( '' )
    return ndays

def print_unscheduled( notScheduled ):
    for i, s in enumerate( notScheduled ):
        lastAwsON = g_.node[ s ]['last_aws_on' ]
        nAWS = g_.nodes[s]['nAWS']
        spec = g_.node[s]['specialization']
        print( '%20s %s %s %s' % (s, spec, lastAwsON, nAWS), end =  ' ')
        if i % 4 == 0:
            print( '' )

def compute_solution( ):
    global g_
    flow = nx.max_flow_min_cost( g_, 'source', 'sink' )
    scheduled, notScheduled = flow_to_solution( flow )
    daysToAWS = print_solution( scheduled )
    cost = nx.cost_of_flow( g_, flow )
    print( 'Cost of solution = %d' % cost )
    print( 'Mean gap=%d, Min gap=%d, Max Gap=%d' % (np.mean(daysToAWS),
            min(daysToAWS), max(daysToAWS))
        )
    return scheduled

def schedule_aws( args ):
    init( )
    construct_flow_graph( args.method )
    return compute_solution( )

def main( args ):
    global g_
    infile = args.gml
    g_ = nx.read_graphml( infile )
    mapping = { k : eval(k) for k in g_.predecessors( 'sink' ) }
    nx.relabel_nodes( g_, mapping, copy = False )
    schedule = schedule_aws( args )
    schedule = aws_helper.no_common_labs_a( schedule )
    text = []
    for s in schedule:
        vals = schedule[s]
        text.append( '%s,%s' % (s, ','.join([ x['speaker'] for x in vals ])) )

    print( '[INFO] Writing schedule to %s' % args.output, end = '' )
    with open( args.output, 'w' ) as f:
        f.write( '\n'.join( text ) )
    print( ' ... [DONE]' )
    print_solution( schedule )
    commit_schedule( schedule )

if __name__ == '__main__':
    import argparse
    # Argument parser.
    description = '''Schedule AWS'''
    parser = argparse.ArgumentParser(description=description)
    parser.add_argument('--gml', '-g', required = True
        , help = 'Graph file (gml)'
        )
    parser.add_argument('--output', '-o'
        , required = False, default = '/tmp/_hippo_aws_schedule.csv'
        , help = 'Output file'
        )
    parser.add_argument( '--method', '-m'
        , required = False
        , default = 'b'
        , help = 'method to use'
        )
    class Args: pass 
    args = Args()
    parser.parse_args(namespace=args)
    main( args )
