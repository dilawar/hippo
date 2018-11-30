#!/usr/bin/env python3

import requests
import json

url_ = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?" + \
        "db=pubmed&retmode=json&retmax=4000&usehistory=yes&" + \
        "term=national+centre+for+biological+sciences[Affiliation]"

def fetch_info( idlist ):
    # assemble the epost URL
    idlist = ','.join(idlist)
    base = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/'
    url = base + "esummary.fcgi?db=pubmed&id=%s&retmode=json" % idlist
    return requests.get( url ).text

def main():
    global url_
    r = requests.get( url_ )
    js = json.loads( r.text )
    res = js['esearchresult']
    idlist = res['idlist']
    for i in range(0, len(idlist), 200):
        data = fetch_info( idlist[i:i+200] )
        print( data )


if __name__ == '__main__':
    main()
