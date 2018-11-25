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

sdir_ = os.path.dirname( __file__ )

def download_bibtex():
    url = "https://ncbs.res.in/publications/export/bibtex"
    r2 = requests.get(url)
    return r2.text

def main():
    global url_
    bib = download_bibtex()
    data = bibtexparser.loads(bib)
    jsonFile = os.path.join( sdir_, '..', 'temp', 'publications.json' )
    with open( jsonFile, 'w' ) as f:
        f.write(json.dumps(data.entries))

if __name__ == '__main__':
    main()
