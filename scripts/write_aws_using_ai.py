#!/usr/bin/env python3

import os
import sys
import subprocess

os.environ[ 'TERM' ] = 'xterm'

def main( ):
    cmd = 'make --quiet generate_sample'.split( )
    res = subprocess.run( cmd, stdout = subprocess.PIPE, cwd = 'hippo-ai')
    if res:
        text = res.stdout.decode( 'utf-8' )
        text = '.'.join( text.split( '.' )[1:] )
        text = text.replace( '\n', ' ' )
        text = text.replace( r'\\', '' )
        print( text )

if __name__ == '__main__':
    main()

