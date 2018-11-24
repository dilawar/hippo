#!/usr/bin/env python3
"""publications_feed.py: 

"""
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
import os
import matplotlib.pyplot as plt
import numpy as np
from collections import defaultdict
import bibtexparser
import requests
import json

url_ = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/erss.cgi?rss_guid=1NSu_CQNBizum_oQNyvEnfQmlhOTxJQa5H5sRESYexRAOfuYAI"


def ris_to_dict( ris ):
    d = defaultdict(list)
    for l in ris.split('\n'):
        l = [x.strip() for x in l.split('-')]
        if len(l) == 2:
            d[l[0]].append(l[1])
    return d


def process_ris( ris ):
    r2 = ris.split('\n')
    lines = '\n'.join([ x.strip() for x in r2])
    papers = lines.split('\n\n')
    ps = []
    for p in papers:
        ps.append(ris_to_dict(p))
    return ps

def download_ris( ):
    url = "https://ncbs.res.in/publications/export/ris/"
    r2 = requests.get(url)
    if r2.status_code == 200:
        with open( 'pub.ris', 'w' ) as f:
            f.write( r2.text )
    return r2.text

def download_bibtex():
    url = "https://ncbs.res.in/publications/export/bibtex"
    r2 = requests.get(url)
    if r2.status_code == 200:
        with open( 'pub.bib', 'w' ) as f:
            f.write( r2.text )
    return r2.text

def main():
    global url_
    bib = download_bibtex()
    data = bibtexparser.loads(bib)
    print( json.dumps(data.entries) )

if __name__ == '__main__':
    main()
