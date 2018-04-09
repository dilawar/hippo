<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'html2text.php';

$default = array( 'date' => dbDate( 'today' ) );

if( $_GET )
{
    if( array_key_exists( 'date', $_GET ) )
        $default[ 'date' ] = $_GET[  'date' ];
    else
        $default = array( 'date' => $today );
}

echo "<h2>Journal Clubs  on " . humanReadableDate( $default[ 'date' ] ) . " </h2>";
echo '
    <form method="get" action="">
    <table class="aws" border="0">
        <tr>
            <td>Select a day</td>
            <td><input class="datepicker" type="text" name="date" value="' .
                    $default[ 'date' ] . '" ></td>
            <td><button type="submit" name="response"
                    title="Show JCs on this day."
                    value="show"> Show All JCs</button></td>
        </tr>
    </table>
    </form>
    ';

echo '<br><br>';

$whichDay = $default[ 'date' ];

$jcs = getTableEntries( 'jc_presentations', 'date'
    , "date='$whichDay' AND status='VALID'" 
    );

if( count( $jcs ) < 1 )
{
    echo alertUser( "I could not find any Journal Club in my database on this day." );
    echo printInfo( "That's all I know!" );
    echo "<br><br>";
}
else
{
    foreach( $jcs as $jc )
    {
        echo jcToHTML( $jc );
    }
}

echo closePage( );


?>
