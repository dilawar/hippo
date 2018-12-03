#!/usr/bin/env python3
"""feed_pubmed.py: 

Run this script from cron every week or every day and write the json file. Use
it to populate the list of papers.
"""
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
import os
import feedparser
import json

sdir = os.path.dirname( __file__ )

url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/erss.cgi?rss_guid=1NSu_CQNBizum_oQNyvEnfQmlhOTxJQa5H5sRESYexRAOfuYAI"
d = feedparser.parse(url)
with open( os.path.join( sdir, '..', 'temp', 'pubmed.json'), 'w' ) as f:
    f.write( json.dumps(d) )
