<?php

require BASEPATH.'autoload.php';

$default = array( 'date' => dbDate( 'today' ) );

if( $_GET )
{
    if( array_key_exists( 'date', $_GET ) )
        $default[ 'date' ] = $_GET[  'date' ];
    else
        $default = array( 'date' => $today );
}

echo '<form method="get" action="">
    <table class="info">
        <tr>
            <td><input class="datepicker" type="text" name="date" value="' .
                    $default[ 'date' ] . '" ></td>
            <td><button type="submit" name="response"
                    title="Show JCs on this day."
                    value="show"> Show All JCs</button></td>
        </tr>
    </table>
    </form>
    ';

$whichDay = $default[ 'date' ];

$jcs = getTableEntries( 'jc_presentations', 'date', "date >= '$whichDay' AND status='VALID'" );

if( count( $jcs ) < 1 )
{
    echo alertUser( "This is embarrassing. I could not find any JC scheduled. That's all I know!", false );
    echo "<br />";
}
else
{
    // Display details of JCs which are withing this week. Otherwise just show
    // the summary.
    echo "<h2>Upcoming presentations in Journal Clubs</h2>";
    foreach( $jcs as $jc )
    {
        if( strtotime( $jc['date'] ) < strtotime( $whichDay ) + 7*24*3600 )
            echo jcToHTML( $jc );
        else
            echo jcToHTML( $jc, true );

        echo horizontalLine();
    }
}

echo closePage( );


?>
