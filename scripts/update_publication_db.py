#!/usr/bin/env python3
"""publications_feed.py: 
Run this script from a cron jon to update the json file.
"""
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
import os
from collections import defaultdict
import bibtexparser
import requests
import json
import dateparser
import hashlib 
import re
from db_connect import db_

cur_ = db_.cursor()

sdir_ = os.path.dirname( __file__ )

def download_bibtex():
    url = "https://ncbs.res.in/publications/export/bibtex"
    r2 = requests.get(url)
    return r2.text

def _merge(month, year):
    month, year = [re.sub( r'\/|-', ' ', x) for x in [month, year]]
    a = [x.strip() for x in month.strip().split()]
    b = [x.strip() for x in year.strip().split()]
    c = sorted(set(a + b))
    date = ' '.join(c)
    res = dateparser.parse(date)
    return res if res else dateparser.parse(year)

def _get_publisher( d ):
    if 'journal' in d:
        return d['journal']
    elif 'publisher' in d:
        return d['publisher']
    else:
        return 'NA'

def after_2015():
    global url_
    bib = download_bibtex()
    data = bibtexparser.loads(bib)
    jsonFile = os.path.join( sdir_, '..', 'temp', 'publications.json' )
    with open( jsonFile, 'w' ) as f:
        f.write(json.dumps(data.entries))

    # Update database.
    for pub in data.entries:
        authors = pub['author'].split('and')
        title = pub['title'].strip()
        titleHash = hashlib.sha512(title.encode('utf8')).hexdigest()
        month, year = pub.get('month',''), pub['year']
        date = _merge(month, year)
        publisher = _get_publisher( pub )
        query =  """REPLACE INTO publications 
                (sha512, title, abstract, publisher, type, date, doi,
                metadata_json)
                VALUES ('%s','%s','%s','%s','%s','%s','%s','%s')
            """ % (titleHash, title, ''
                , publisher, pub['ENTRYTYPE'], date
                , pub.get('doi', ''), '' )
        try:
            cur_.execute( query )
        except Exception as e:
            db_.close()
            return
        for auth in authors:
            author = ' '.join(reversed(auth.strip().split(',')))
            cur_.execute( """
                REPLACE INTO publication_authors (author, affiliation,
                publication_title_sha, publication_title ) VALUES
                ( '%s', '%s', '%s', '%s' )
                """ % (author, '', titleHash, title )
                )
            print( author, end = ', ' )
        print()
    db_.commit()
    cur_.close()
    db_.close()

def _process_data( data_file ):
    with open( data_file, 'r' ) as f:
        txt = f.read()

    blocks = txt.split( '\n\n' )
    print( "[INFO ] Total blocks %d" % len(blocks) )
    pat = re.compile( r'(?P<author>.+?)\(\d+\)(?P<title>.+?[.?])(?P<journal>(.+))', re.DOTALL )
    pubs = []
    for i, bl in enumerate(blocks):
        bl = bl.replace( '\n', ' ' )
        bl = re.sub( r'^\d+\.?\s+', '', bl )
        m = pat.search( bl )
        if m:
            print( 'AUTHORS: ', m.group( 'author' ))
            #  print( 'TITLE  : ', m.group( 'title' ) )
            #  print( 'JOURNAL: ', m.group( 'journal' ) )
            pubs.append( m.groupdict() )
        else:
            print( '[WARN] Could not parse' )
            print( bl )
            break


def before_2015():
    dataF = '../data/publications_before_2015_mixed.txt'
    data = _process_data( dataF )

def main():
    before_2015()

if __name__ == '__main__':
    main()
