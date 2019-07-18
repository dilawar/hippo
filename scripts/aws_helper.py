"""aws_helper.py: 

    Helper functions are here.
"""
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import datetime
from logger import _logger
from global_data import *
import random
import numpy as np

random.seed( 0 )
np.random.seed( 0 )

def toDate( datestr ):
    return datetime.datetime.strptime( datestr, fmt_ )

# This is from stackoverflow.
def unique( seq ):
    seen = set()
    seen_add = seen.add
    return [x for x in seq if not (x in seen or seen_add(x))]

def spk2str( s ):
    return '%s(%s)' % (s['speaker'], s['lab'] )

def findReplacement( speaker, date, specialization, piH, schedule ):
    for dateA in sorted( schedule ):
        if dateA <= date:
            continue
        for i, speakerA in enumerate( schedule[dateA] ):
            if speakerA == speaker:
                continue
            slot = '%s,%d' % (dateA,i)
            spec = g_.node[slot]['specialization']
            if spec != specialization:
                continue

            thisPI = speakers_[ speakerA ]['pi_or_host']

            if thisPI == piH:
                continue

            # final check. Make sure the AWS does not come too early.
            nDays = ( toDate(dateA) - toDate(date) ).days
            prevAWS = g_.node[ speakerA ][ 'last_date' ]
            newDate = prevAWS - datetime.timedelta( days = nDays )
            if (newDate - prevAWS).days < 400:
                _logger.warn( 'Costly swapping. ignoring...' )
                continue
            return speakerA, dateA
    _logger.warn( 'Could not find replacement for %s.%s' % (speaker, date) )
    return None


def no_common_labs( schedule, nweeks = 2, ncalls = 0 ):
    # Make sure that first 2 week entries have different PIs.
    if ncalls > 100:
        _logger.warn( "Terminated after 100 calls" )
        return schedule
    failedDates = [ ]
    sortedDates = sorted( schedule )
    for ix, date in enumerate(sortedDates[:nweeks]):
        labs = []
        for i, speaker in enumerate(schedule[ date ]):
            spec = g_.node['%s,%d'%(date,i)]['specialization']
            piH = speakers_[speaker]['pi_or_host']
            if piH in labs:
                spec = specialization_.get( speaker, 'UNSPECIFIED' )
                replaceWith = findReplacement( speaker, date, spec, piH, schedule )
                if replaceWith is not None:
                    speakerB, dateB = replaceWith
                    speakerA, dateA = speaker, date
                    schedule[dateA].append( speakerB )
                    schedule[dateA].remove( speakerA )
                    schedule[dateB].append( speakerA )
                    schedule[dateB].remove( speakerB )
                    _logger.info( 'Swapping %s and %s' % (speakerA, speakerB))
                else:
                    # swap this row by next and try again.
                    failedDates.append( date )
                    _logger.info( "Failed to find alternative for %s" % date )
                    # swap this date with someone else.
                    for iy, datey in enumerate( sortedDates[nweeks:] ):
                        if len( schedule[ datey ] ) == len( schedule[ date ] ):
                            # we can swap with entry.
                            temp = schedule[ datey ]
                            schedule[ datey ] = schedule[ date ]
                            schedule[ date ] = temp
                            return no_common_labs( schedule, nweeks, ncalls + 1)
            else:
                labs.append( piH )

    for fd in failedDates:
        _logger.warn( 'Entry for date %s has multiple speakers from same lab' % fd )
        _logger.warn( 'Moving whole row down to more than %d positions' % nweeks)

    return schedule

def find_replacement( s1, d1, schedule, nolabs ):
    """Find another speaker with same specification and lab.
    """
    print( 'Finding repalcement for', s1 )
    potentialCandidates = [ ]
    for d in schedule:
        diffDays = (d-d1).days
        if diffDays < 1:
            continue
        
        for i, s2 in enumerate(schedule[d]):
            # Do not select from different specialization 
            if s1['spec'] != s2['spec']:
                continue

            # Don't select from same lab.
            if s2['lab'] in nolabs:
                continue

            ndays = s2['ndays'] - diffDays
            potentialCandidates.append( (ndays,d,i) )

    # sorted
    potentialCandidates = sorted( potentialCandidates, key = lambda x: x[0] )
    return potentialCandidates[-1]

def no_common_labs_a( schedule, nweeks = 2, ncalls = 0 ):
    # Make sure that first 2 week entries have different PIs.
    dates = sorted( schedule.keys( ) )
    for date in dates[:4]:
        vals = schedule[ date ]
        print( '[INFO] Fixing AWS schedule for %s' % date )
        pis = [ s['lab'] for s in vals ]
        for i, p in enumerate( unique(pis) ):
            picount = pis.count( p )
            if picount == 1:
                continue
            for j in range(picount-1):
                frm = vals[i+j]
                x, date2, i2 = find_replacement( frm, date, schedule, pis )
                replaceWith = schedule[date2][i2]
                schedule[date][i+j] = replaceWith
                schedule[date2][i2] = frm
                print( 'Replaced %s -> %s' % (spk2str(frm), spk2str(replaceWith)))
    return schedule
