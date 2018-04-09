"""compute_cost.py:

"""

__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2016, Dilawar Singh"
__credits__          = ["NCBS Bangalore"]
__license__          = "GNU GPL"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
import os
import random
import datetime
import math

__fmt__ = '%Y-%m-%d'

def parabola( x0, offset, x ):
    minX = x0 * 0.6
    ys = offset + offset * (x-minX) ** 2.0
    return ys

def linear( nAWS, x ):
    if nAWS <= 2:
        return max(0, x - 1 )
    elif nAWS == 3:
        m, c = 0.8, 0.7
    elif nAWS == 4:
        m, c = 0.7, 0.5
    else:
        m, c = 0.63333, 0.3333
    return max(0, m * x - c )

def computeCost( slot_date, lastDate, nAWS ):
    ndays = ( slot_date - lastDate ).days
    nyears = ndays / 365.0
    cost = 0
    if ndays < 365.0:
        cost = 5
    else:
        cost = linear( nAWS, nyears )
    return int( 100.0 * cost )

def random_date(start, end):
    """
    This function will return a random datetime between two datetime
    objects.
    """
    delta = end - start
    int_delta = (delta.days * 24.0 * 60 * 60) + delta.seconds
    random_second = randrange(int_delta)
    return start + datetime.timedelta(seconds=random_second)

def test( ):
    import matplotlib as mpl
    import matplotlib.pyplot as plt
    mpl.rcParams['text.usetex'] = False
    # Generate random test data.
    start = datetime.datetime.strptime( '2017-03-18', __fmt__ )
    end = datetime.datetime.strptime( '2021-03-18', __fmt__  )
    for naws in range( 0, 6 ):
        xval, yval = [ ], [ ]
        for i in range( 5 * 54 ):
            date = start + datetime.timedelta( days = i * 7 )
            xval.append( (date - start).days / 365.0 )
            yval.append( computeCost( date, start, naws ) )

        plt.xlabel( 'Gap in years between slot and last AWS' )
        plt.ylabel( 'Cost' )
        plt.plot( xval, yval, label = '#AWS = %d' % naws )
        plt.legend( )

    plt.savefig( "%s.png" % sys.argv[0] )

if __name__ == '__main__':
    test()
