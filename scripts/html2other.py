#!/usr/bin/env python3
# -*- coding: utf-8 -*-

__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2016, Dilawar Singh"
_credits__          = ["NCBS Bangalore"]
__license__          = "GNU GPL"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
import os
import subprocess
import re
import six
import string
import tempfile
import base64
from logger import _logger
import locale

# Set locale to utf-8 otherwise calling this script from apache will have
# decoding issues.
locale.setlocale( locale.LC_ALL, "en_IN.UTF-8")
os.environ[ "PYTHONENCODING"] = "utf-8"

pandoc_ = True

def _cmd( cmd ):
    output = subprocess.check_output( cmd.split( ), shell = False )
    return output.decode( 'utf-8' )

def fix( msg ):
    return msg

def tomd( htmlfile ):
    # This function is also used by sendemail.py file.
    with open( htmlfile, 'r' ) as f:
        msg = f.read()

    msg = fix( msg )
    # remove <div class="strip_from_md"> </div>
    pat = re.compile( r'\<div\s+class\s*\=\s*"strip_from_md"\s*\>.+?\</div\>', re.DOTALL )
    for s in pat.findall( msg ):
        msg = msg.replace( s, '' )
    msg = msg.replace( u'</div>', '' )
    msg = re.sub( r'\<div\s+.+?\>', '', msg )

    # Write back to original file.
    htmlfile = tempfile.mktemp( prefix = 'hippo', suffix='.html' )
    txtfile = '%s.txt' % htmlfile

    with open( htmlfile, 'w' ) as f:
        f.write(msg)

    _cmd( 'pandoc -t plain -o %s %s' % (txtfile, htmlfile) )
    return msg, txtfile.strip()

def fixInlineImage( msg ):
    """Convert inline images to given format and change the includegraphics text
    accordingly.

    Surround each image with \includewrapfig environment.
    """
    # Sometime we loose = in the end.
    pat = re.compile( r'data\:image\/(?P<fmt>.+?);base64,(?P<data>.+?=)', re.DOTALL | re.I )
    for m in pat.finditer( msg ):
        outfmt = m.group( 'fmt' )
        data = m.group( 'data' )
        fp = tempfile.NamedTemporaryFile( mode='w+b', delete = False, suffix='.'+outfmt )
        fp.write( base64.decodebytes(data.encode()) )
        fp.close( )
        # Replace the inline image with file name.
        msg = msg.replace( m.group(0), "%s" % fp.name )

    # And wrap all includegraphics around by wrapfig
    msg = re.sub( r'(\\includegraphics.+?width\=(.+?)([\],]).+?})'
            , r'\n\\begin{wrapfigure}{R}{\2}\n \1 \n \\end{wrapfigure}'
            , msg, flags = re.DOTALL
            )
    return msg

def toTex( infile ):
    outfile = tempfile.mktemp(  prefix = 'hippo', suffix = 'tex'  )
    _cmd( 'pandoc -f html -t latex -o %s %s' % (outfile, infile ))

    with open( outfile, 'r' ) as f:
        tex = f.read()
    tex = fixInlineImage( tex )
    with open( outfile, 'w' ) as f:
        f.write( tex )

    return outfile.strip()

def htmlfile2md( filename ):
    md, mdfile = tomd( filename )
    return mdfile

def main( infile, outfmt ):
    # Print is neccessary since we are reading stdout in PHP.
    # Force proper encoding
    if outfmt in [ 'md', 'markdown', 'text', 'txt' ]:
        mdfile = htmlfile2md( infile )
        print( mdfile )
        return mdfile
    elif outfmt == 'tex':
        texfile = toTex( infile ) 
        print( texfile )
        return texfile

if __name__ == '__main__':
    infile = sys.argv[1]
    outfmt = sys.argv[2]
    main( infile, outfmt )
