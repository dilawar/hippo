<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'html2text.php';

echo '<table class="info">
<tr>
    <td>
        <a href="user_aws_search.php" target="_blank">Click to search AWS database.</a>
    </td>
    <td>
        <a href="summary_aws.php" target="_blank">List of AWS speakers</a>
    </td>
</tr>
</table>';
echo '<br />';

if( strtotime( 'today' ) == strtotime( 'this monday' ) )
    $today = dbDate( 'this monday' );
else
    $today = dbDate( 'next monday' );
$default = array( 'date' => $today );

if( $_GET )
{
    if( array_key_exists( 'date', $_GET ) )
        $default[ 'date' ] = $_GET[  'date' ];
    else
        $default = array( 'date' => $today );
}

echo '
    <form method="get" action="">
    <table class="aws" border="0">
        <tr>
            <td>Select a Monday</td>
            <td><input class="datepicker" type="text" name="date" value="' .
                    $default[ 'date' ] . '" ></td>
            <td><button type="submit" name="response"
                    title="Show AWS on this day"
                    value="show">Show AWSs on This Day</button></td>
        </tr>
    </table>
    </form>
    ';

echo '<br><br>';

$whichDay = $default[ 'date' ];

echo "<h2>Annual Work Seminars on " .
    humanReadableDate( $default[ 'date' ] ) . " </h2>";

$awses = getTableEntries( 'annual_work_seminars', 'date' , "date='$whichDay'" );
$upcoming = getTableEntries( 'upcoming_aws', 'date', "date='$whichDay'" );
$awses = array_merge( $awses, $upcoming );

if( count( $awses ) < 1 )
{
    echo alertUser( "I could not find any AWS in my database on this day" );
    $holiday = getTableEntry( 'holidays', 'date', array( 'date' => $whichDay ) );
    if( $holiday )
    {
        echo alertUser( "May be due to following"  );
        echo arrayToTableHTML( $holiday, 'info' );
    }

    echo printInfo( "That's all I know!" );
    echo "<br><br>";
}
else
{
    foreach( $awses as $aws )
    {
        $user = $aws[ 'speaker' ];
        $awstext = awsToHTML( $aws, $with_picture = true );

        // Link to pdf file.
        $awstext .= awsPdfURL( $aws[ 'speaker' ], $aws['date' ] );
        echo $awstext;
        echo horizontalLine( );
    }
}

echo closePage( );


?>
