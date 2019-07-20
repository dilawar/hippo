"""query_wiki.py:

    Query wikipedia about a term.

"""

__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import nltk
try:
    from nltk.corpus import stopwords
    from nltk.corpus import punkt
    from nltk.corpus import averaged_perceptron_tagger
    from nltk.corpus import words
    from nltk.corpus import wordnet
except Exception as e:
    nltk.download( 'stopwords' )
    nltk.download( 'punkt' )
    nltk.download( 'words' )
    nltk.download( 'wordnet' )
    nltk.download('averaged_perceptron_tagger')


from nltk.corpus import stopwords
from nltk.corpus import wordnet
import sys
import os
from wikiapi import WikiApi
import urllib2
import html2text

wiki_ = WikiApi( )
common_ = set( nltk.corpus.words.words() )

isNoun = lambda x: x[:2] == 'NN'

def url_exists( url ):
    ret = urllib2.urlopen( url )
    if ret.code == 200:
        return True
    return False

def wiki_link( query ):
    wikiLink = 'https://en.wikipedia.org/wiki/%s' % query
    if url_exists( wikiLink ):
        return wikiLink
    return query

def wikify( html ):
    text = html2text.html2text( html )
    tokens = nltk.word_tokenize( text )
    nouns = [ word for (word,pos) in nltk.pos_tag(tokens) if isNoun(pos) ]
    for n in nouns:
        nn = wordnet.morphy( n )
        if nn is not None and nn not in common_:
            link = wiki_link( nn )
            print(n, link )

def main( ):
    arg = sys.argv[1]
    if os.path.exists( arg ):
        with open( sys.argv[1], 'r' ) as f:
            html = f.read( )
    else:
        # Open url and fetch the html.
        html = urllib2.urlopen( arg ).read( )
    text = wikify( html )

if __name__ == '__main__':
    main()
