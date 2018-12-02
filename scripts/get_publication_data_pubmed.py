#!/usr/bin/env python3

import os
import requests
import json
import multiprocessing
import time
import dateparser
from collections import defaultdict
import hashlib
from db_connect import db_
cur_ = db_.cursor()

def _sha512( msg ):
    return hashlib.sha512(msg.encode('utf8')).hexdigest()

url_ = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?" + \
        "db=pubmed&retmode=json&retmax=4000&usehistory=yes&" + \
        "term=national+centre+for+biological+sciences[Affiliation]"

def fetch_info( idlist ):
    # assemble the epost URL
    idlist = ','.join(idlist)
    base = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/'
    url = base + "esummary.fcgi?db=pubmed&id=%s&retmode=json" % idlist
    r =  requests.get( url ).text
    time.sleep(0.5)
    return json.loads(r)

def _make_date( x ):
    return dateparser.parse(x)

def _exeucte( cur, q ):
    try:
        cur.execute(q)
    except Exception as e:
        print('[WARN] Failed to execute: \n%s' % q )
        print( '\tError was: %s' % e )
        raise e

def main( args ):
    global url_
    r = requests.get( url_ )
    js = json.loads( r.text )
    res = js['esearchresult']
    idlist = res['idlist']
    # PUBMED allows upto 200 ids in one go and not more than 3 queries per
    # second.
    idSlices = [ idlist[i:i+200] for i in range(0, len(idlist), 200)]
    if not os.path.exists( args.output ) or args.force: 
        with multiprocessing.Pool(2)  as p:
            results = p.map(fetch_info, idSlices)
            with open( args.output, 'w' ) as f:
                #  f.write('\n'.join(results))
                json.dump(results,f)

    # open the reuslt file.
    with open(args.output, 'r') as f:
        sections = json.loads( f.read() )

    i = 0
    for sec in sections:
        data = sec['result']
        for entry in data:
            i+=1
            if entry == "uids":
                continue
            authors = data[entry]['authors']
            auths = ','.join([x['name'] for x in authors])
            v = data[entry]
            title = v['title'].replace('"', "'")
            sha = _sha512(title)
            date = _make_date(v['pubdate'])
            publisher = v['fulljournalname']
            doi = v['elocationid']
            ptype = v['pubtype'][0] if v['pubtype'] else 'NA'
            abstract = 'NA'
            eid = entry
            q = '''INSERT IGNORE INTO publications
                    (sha512,title,abstract,publisher,type
                    ,date,doi,urls,source,external_id
                    ,metadata_json) 
                VALUES
                    ( "%s","%s","%s","%s","%s"
                     ,"%s","%s","%s","%s","%s"
                     ,"%s")''' % ( 
                     sha,title,abstract,publisher,ptype
                    , date, doi, '', 'PUBMED', eid
                    , '' #json.dumps(data)
                    )

            print( "%d: %s\n\t%s; %s; %s" % (i, title, auths, date, publisher) )
            try:
                _exeucte(cur_, q)
            except Exception as e:
                print( 'FAILED' )
                quit()

            # Update authors.
            for auth in authors:
                authName = auth['name']
                affil = 'NA'
                q = '''INSERT IGNORE INTO publication_authors 
                    (author, affiliation, publication_title_sha, publication_title)
                    VALUES ("%s","%s","%s","%s")''' % (authName, affil, sha, title)
                _exeucte( cur_, q )

        db_.commit()



if __name__ == '__main__':
    import argparse
    # Argument parser.
    description = '''Download NCBS data from PUBMED.'''
    parser = argparse.ArgumentParser(description=description)
    parser.add_argument('--force', '-f'
        , action = 'store_true',  default = False
        , help = 'Force download even when data is downloaded before.'
        )
    parser.add_argument('--output', '-o'
        , required = False, type = str
        , default = 'pubmed.json'
        , help = 'Output file'
        )
    class Args: pass 
    args = Args()
    parser.parse_args(namespace=args)
    main( args )
