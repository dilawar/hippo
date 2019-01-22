<?php
require_once BASEPATH.'autoload.php';

if( strtotime( 'today' ) == strtotime( 'this monday' ) )
    $today = dbDate( 'this monday' );
else
    $today = dbDate( 'next monday' );

$default = array( 'date' => __get__($_GET, 'date', $today) );

$form = '<form method="get" action="">
        <input class="datepicker" type="text" name="date" value="' . $default[ 'date' ] . '" > <br /> 
        <button type="submit" name="response" title="Show AWS" value="show">Show AWSs</button>
    </form>
    ';

$table = '<table class="info">
        <tr>
            <td>
                <a href="'.site_url('info/aws/search'). '">Click to search AWS database.</a>
            </td>
            <td>
                <a href="'.site_url( 'info/aws/roster'). '"> List of AWS speakers <br /> 
                    and upcoming AWSs</a>
            </td>
            <td>' . $form . 
            '</td></tr></table>';

echo "<h3>Annual Work Seminars on " .  humanReadableDate( $default[ 'date' ] ) . ".</h3>";
echo $table;
echo "<br />";

if( $_GET )
{
    if( array_key_exists( 'date', $_GET ) )
        $default[ 'date' ] = $_GET[  'date' ];
    else
        $default = array( 'date' => $today );
}

$whichDay = $default[ 'date' ];

$awses = getTableEntries( 'annual_work_seminars', 'date' , "date='$whichDay'" );
$upcoming = getTableEntries( 'upcoming_aws', 'date', "date='$whichDay'" );
$awses = array_merge( $awses, $upcoming );

if( count( $awses ) < 1 )
{
    echo printInfo( "I could not find any AWS in my database on this day" );
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
        $awstext = awsToHTMLLarge( $aws, $with_picture = true );

        // Link to pdf file.
        // $awstext .= awsPdfURL( $aws[ 'speaker' ], $aws['date' ] );
        $awstext .= '<a href="'.site_url("user/downloadaws/".$aws['date']."/".$aws['speaker']).'"
            target="_blank">Download PDF</a>';

        echo "<div class='notice'>";
        echo $awstext;
        echo "</div>";
    }
}

echo closePage( );


?>
