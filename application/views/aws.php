<?php
require_once BASEPATH.'autoload.php';

$form = '<form method="post" action="'.site_url('info/aws').'">
        <input class="datepicker" type="text" name="date" value="' . $date . '" > 
        <button class="btn btn-primary" 
                type="submit" name="response" title="Show AWS" value="show">Show AWSs</button>
    </form>
    ';

$table = '<table class="table table-info">
        <tr>
            <td>
                <a href="'.site_url('info/aws/search'). '">Click to search AWS database.</a>
            </td>
            <td>
                <a href="'.site_url( 'info/aws/roster'). '"> List of AWS speakers <br /> 
                    and upcoming AWSs</a>
            </td> 
        </tr>
        <tr>
             <td colspan="2">' . $form .  '</td>
        </tr></table>';

echo $table;
echo "<br />";

$whichDay = $date;
echo heading("Annual Work Seminars on " .  humanReadableDate($whichDay), 5);

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

        echo $awstext;
    }
}

echo closePage( );


?>
