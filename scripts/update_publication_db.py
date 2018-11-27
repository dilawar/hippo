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
    date1 = dateparser.parse(date)
    assert date1, (date, a, b, c)
    return date1 if date1 else date


def main():
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
        titleHash = hashlib.sha256(title.encode('utf8')).hexdigest()
        month, year = pub.get('month',''), pub['year']
        date = _merge(month, year)
        print( '-', date, titleHash, title )
        for auth in authors:
            author = ' '.join(reversed(auth.strip().split(',')))
            print( author, end = ', ' )
        print()

if __name__ == '__main__':
    main()
