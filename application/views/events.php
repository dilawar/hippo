<?php
require_once BASEPATH . 'autoload.php';

$today = dbDate( 'today' );
$default = array( 'date' => $today );

if( __get__($_GET, 'date', '' ) )
    $default[ 'date' ] = $_GET[  'date' ];


$form = '
    <form method="get" action="">
    <table border="0">
        <tr>
            <td>Select date</td>
            <td><input class="datepicker" type="text" name="date" value="' .
                    $default[ 'date' ] . '" ></td>
            <td><button type="submit" name="response"
                    title="Show events on this day"
                    value="show">Show Events on This Day</button></td>
        </tr>
    </table>
    </form>
    ';

echo "$form <br /> <br />";

$whichDay = $default[ 'date' ];
$eventTalks = getTableEntries( 'events', 'date,start_time' , "date='$whichDay'
        AND status='VALID' AND external_id LIKE 'talks%'"
    );

// Only if a event has an external_id then push it into 'talks'
if( count( $eventTalks ) < 1 )
{
    echo printInfo( "I could not find any talk/seminar/lecture on selected day." );
}
else
{
    $talkHtml = '';

    foreach( $eventTalks as $event )
    {
        $talkId = explode( '.', $event[ 'external_id'])[1];
        $talk = getTableEntry( 'talks', 'id', array( 'id' => $talkId ) );
        if( $talk )
        {
            // $talkHtml .= talkToHTMLLarge( $talk, $with_picture = true );
            $talkHtml .= talkToHTML( $talk, $with_picture = true );

            $talkHtml .= "<br>";
            // Link to pdf file.
            $talkHtml.= '<a style="margin-left:100%;align:right;" target="_blank" href="'
                    . site_url("user/downloadtalk/".$default['date']."/$talkId") . '">
                    <i class="fa fa-download ">PDF</i></a>';
            $talkHtml .= horizontalLine( );
        }
    }
    echo $talkHtml;
    echo '<br>';
}

echo closePage( );

?>
