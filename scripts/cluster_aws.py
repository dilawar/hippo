#!/usr/bin/env python
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
import os
from collections import Counter
import itertools
import numpy as np
import re
import random

random.seed( 1 )
np.random.seed( 1 )

def head_and_tail( vec ):
    head = list(itertools.takewhile(lambda x: x[0] == vec[0][0], vec))
    return head, vec[len(head):]

def find_elem_in_vec( vec, pivot ):
    for i, v in enumerate( vec ):
        if v[2] == pivot:
            return i
    return None

def print_result( result ):
    for r in result:
        try:
            print( [ (x[1], x[2]) or x for x in r ] )
        except Exception as e:
            print( 'INCOMPLETE' )

def cluster_data( vec, result ):
    if len( vec ) < 1:
        return result

    vec = [ list(x) for x in vec ]

    cluster, rest = head_and_tail( vec )
    vec = rest
    specs = [ x[2] for x in cluster ]
    pivotSpec, pivotSpecCount = Counter( specs ).most_common( 1 )[0]
    newcluster = [ None ] * len(cluster)
    prevN = len( vec )

    for i, e in enumerate(cluster):
        clusterDate = e[0]
        if e[2] == pivotSpec:
            newcluster[i] = e
            continue

        fromI = find_elem_in_vec( vec, pivotSpec )

        if fromI is None:
            continue

        #print( 'Replacing %s from %s' % (e, vec[fromI]) )
        # update its date.
        vec[fromI][0], e[0] = e[0], vec[fromI][0]
        newcluster[ i ] = vec[ fromI ]
        del vec[ fromI ]
        vec.insert( fromI, e )
        assert vec[fromI] == e, 'failed to insert'

    assert len( vec ) == prevN, "Length is still the same"
    #print( ' %s\n  %s' % (cluster, newcluster) )

    result.append( newcluster )
    return cluster_data( vec, result )

def cluster_aws( vec ):
    result = [ ]
    return cluster_data( vec, result )

def test( ):
    data = []
    with open( sys.argv[1], 'r' ) as f:
        for line in f:
            data.append( line.strip().split( ',' ) )

    result = [ ]
    cluster_data( data, result )
    print_result( result )

if __name__ == '__main__':
    test( )

