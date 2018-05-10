"""
Main script file
"""

from retrieval import intranet, analyze

judge_ = True
try:
    from judging import judge
    judge_ = False
except ImportError as e:
    print( '[WARN] Failed to import judge due to %s' % e )
    print( '\t No judging today' )

import logging

THEME = "Portraits"
DEADLINE = "25 January 2018"

def judge_cal():
    global judge_
    if judge_:
        judge.calculate_score("final_Scores.xlsx")

def intra( args ):
    intranet.do_all(THEME, DEADLINE, args)

def check_defaulters():
    analyze.get_defaulters(THEME, DEADLINE)

def main( args ):
    logging.basicConfig( level = args.loglevel 
            , format = '%(asctime)s %(name)-10s %(levelname)-5s %(message)s'
            )
    logging.debug( 'Calling intra' )
    intra( args )

if __name__ == '__main__':
    import argparse
    # Argument parser.
    description = '''Photography Club: It does everything!'''
    parser = argparse.ArgumentParser(description=description)
    parser.add_argument('--output', '-o'
        , required = False
        , help = 'Output file'
        )
    parser.add_argument( '--debug', '-d'
        , required = False, action = 'store_const'
        , dest = 'loglevel', const = logging.DEBUG
        , default = logging.WARNING
        , help = 'Enable debug mode.'
        )
    parser.add_argument( '--verbose', '-v'
        , required = False, action = 'store_const'
        , dest = 'loglevel', const = logging.INFO
        , help = 'Be chatty. Like academic canteen and SLC.'
        )
    parser.add_argument('--task', '-t'
        , required = False, default = 'all'
        , help = 'Which task: json|all'
        )
    class Args: pass 
    args = Args()
    parser.parse_args(namespace=args)
    main( args )
    
