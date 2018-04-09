import mysql 

def fToQuery( fs ):
    vc = 'YES' if 'vc' == fs[-2].lower() else 'NO'
    skype = 'YES' if 'skype' == fs[-2].lower() else 'NO'
    hasProjector = fs[-1].upper()
    query = """REPLACE INTO venues (
            id, name, institute
            , building_name, floor, location
            , type, strength , has_projector
            , suitable_for_conference, has_skype 
        )
        VALUES ( 
            '%s', '%s', '%s'
            , '%s', '%s', '%s'
            , '%s', '%s', '%s'
            , '%s', '%s' 
        );""" % ( fs[2], fs[2], fs[0]
                , fs[5], fs[4], fs[0]
                , fs[1], fs[3], hasProjector
                , vc, skype 
                )
    
    with open( 'venues.sql', 'a' ) as vF:
        print( '[INFO] Wrote query  to query file' )
        vF.write( query + '\n' )


def main( ):
    with open( "./venues.csv", 'r' ) as f:
        lines = f.read( ).split( '\n' )

    with open( 'venues.sql', 'w' ) as f:
        f.write( 'USE hippo;\n' )

    for l in lines[1:]:
        if len(l) < 5:
            continue
        fields = [x.strip() for x in l.split(',') ]
        query = fToQuery( fields )

if __name__ == '__main__':
    main()
