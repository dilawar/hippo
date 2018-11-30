#!/usr/bin/env python3

import requests
import json

url_ = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?" + \
        "db=pubmed&retmode=json&retmax=4000&usehistory=yes&" + \
        "term=national+centre+for+biological+sciences[Affiliation]"

def main():
    global url_
    r = requests.get( url_ )
    js = json.loads( r.text )
    print( js )


if __name__ == '__main__':
    main()
