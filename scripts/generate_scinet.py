#!/usr/bin/env python 

"""
Fetch all AWS and generate a scinet.


"""
from __future__ import print_function 

    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2016, Me"
__credits__          = ["NCBS Bangalore"]
__license__          = "GNU GPL"
__version__          = "1.0.0"
__maintainer__       = "Me"
__email__            = ""
__status__           = "Development"

import sys
import os
import math
import mysql.connector
import mysql
import ConfigParser
from collections import defaultdict, Counter
import networkx as nx
import datetime 
import tempfile 
import logging
import string
import itertools
import difflib

logging.basicConfig( level=logging.INFO
        , format='%(asctime)s %(name)-12s %(levelname)-8s %(message)s'
        , filemode = 'w'
        , datefmt='%m-%d %H:%M'
        )
logging.info( 'Started on %s' % datetime.datetime.today( ) )

g_ = nx.DiGraph( )

# All AWS entries.
aws_ = defaultdict( list )

scinet_ = defaultdict( int )

config = ConfigParser.ConfigParser( )
thisdir = os.path.dirname( os.path.realpath( __file__ ) )
config.read( os.path.join( thisdir, 'minionrc' ) )
logging.debug( 'Read config file %s' % str( config ) )

class MySQLCursorDict(mysql.connector.cursor.MySQLCursor):
    def _row_to_python(self, rowdata, desc=None):
        row = super(MySQLCursorDict, self)._row_to_python(rowdata, desc)
        if row:
            return dict(zip(self.column_names, row))
        return None

db_ = mysql.connector.connect( 
        host = config.get( 'mysql', 'host' )
        , user = config.get( 'mysql', 'user' )
        , passwd = config.get( 'mysql', 'password' )
        , db = 'minion'
        )

with open( '/var/lib/dict/words') as f:
    words_ = f.read().split( )

def init( cur ):
    """Create a temporaty table for generating scinet. """
    global gb_
    cur.execute( 'DROP TABLE IF EXISTS scinet' )
    cur.execute( 
            """
            CREATE TABLE IF NOT EXISTS scinet (   
                speaker_a VARCHAR(100) NOT NULL
                , speaker_b VARCHAR(100) NOT NULL
                , rank DECIMAL(7,4) NOT NULL DEFAULT '0.0'
                , keywords TEXT
                , PRIMARY KEY (speaker_a, speaker_b )
            ) 
            """
        )
    db_.commit( )


def getAllAWS( ):
    global aws_, db_
    cur = db_.cursor( cursor_class = MySQLCursorDict )
    init( cur )
    cur.execute( 'SELECT * FROM annual_work_seminars ORDER BY date DESC' )
    for a in cur.fetchall( ):
        aws_[ a[ 'speaker' ] ].append( a )


def findKeyword( keyword, speakerDict ):
    matches = []
    for speaker in speakerDict:
        if keyword in speakerDict[ speaker ].split( ):
            matches.append( speaker )
    return matches

def isCommonWord( word ):
    global words_
    if word in words_:
        return True
    # It may be a plural or might have 'ly' in it.
    if word[:-2] in words_:
        return True

    if word[:-3] in words_:
        return True

    return False

def generateScinet( ):
    global aws_
    global g_
    speakers = aws_.keys()

    wordSets = []
    speakerAWSs = { }
    allText = ''
    for s in speakers:
        allAws = ''
        for aws in aws_[ s ]:
            allAws += aws[ 'abstract' ]
        speakerAWSs[ s ] = allAws.lower( )
        allText += allAws


    allText = str( allText ).lower( )
    allText = allText.translate( None, string.punctuation )

    counts = Counter( allText.split() )
    leastCommon = sorted( counts.most_common(  ), key = lambda x: x[1] )
    for w, cnt in leastCommon:
        if cnt > 1:
            if isCommonWord( w ):
                print( '.', end='')
                sys.stdout.flush( )
                continue
            spkrs = findKeyword( w, speakerAWSs )
            if len( spkrs ) > 1:
                for s1, s2 in itertools.combinations( spkrs, 2 ):
                    print( 'x', end='')
                    sys.stdout.flush( )
                    scinet_[ (s1, s2) ] += 1

    print( scinet_ )
    print( '[INFO] Total speakers %d' % len( speakers ) )


def main( outfile ):
    global aws_
    global db_
    logging.info( 'Scheduling AWS' )
    getAllAWS( )
    generateScinet( )
    db_.close( )

if __name__ == '__main__':
    outfile = tempfile.NamedTemporaryFile( ).name
    if len( sys.argv ) > 1:
        outfile = sys.argv[1]
    main( outfile )
