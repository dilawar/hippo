<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo "<h3>Annual Work Seminars Summary</h3>";

function awsOnThisBlock( $awsDays, $block, $blockSize )
{
    foreach( $awsDays as $awsDay )
    {
        $awsWeek = intval( $awsDay / $blockSize );
        if( 0 == ($block - $awsWeek ) )
            return true;
    }
    return false;
}

function daysToLine( $awsDays, $totalDays, $blockSize = 7)
{
    $today = strtotime( 'now' );
    $totalBlocks = intval( $totalDays / $blockSize ) + 1;
    $line = '<td><small>';

    // These are fixed to 4 weeks (a month).
    if( count( $awsDays ) > 0 )
    {
        $line .= intval( $awsDays[0] / 30.41 ) . ',' ;
        for( $i = 1; $i < count( $awsDays ); $i++ )
            $line .=  intval(( $awsDays[ $i ] - $awsDays[ $i - 1 ] ) / 30.41 ) . ',';

        $line .= "</small></td><td>";

        for( $i = 0; $i <= $totalBlocks; $i++ )
        {
            if( awsOnThisBlock( $awsDays, $i, $blockSize ) )
                $line .= '|';
            else
                $line .= '.';
        }
    }
    $line .= "</td>";
    return $line;
}


// Get AWS in roughly last 5 years.
$allAWS = getAllAWS( );

$table = '<table border="0" class="show_aws_summary">';

$table .= '<tr>
    <th></th><th>Name <small>email</small></th>
    <th><small>Months between AWSes</small></th>
    <th>Previous AWSes</th>
    </tr>';

$i = 0;
$awsGroupedBySpeaker = array( );
foreach( $allAWS as $aws )
{
    if( ! array_key_exists( $aws['speaker'], $awsGroupedBySpeaker ) )
        $awsGroupedBySpeaker[ $aws[ 'speaker' ] ] = array( );
    array_push( $awsGroupedBySpeaker[ $aws[ 'speaker' ] ], $aws );
}

// This is the length of block.
$totalDays = 10 * 365;
foreach( $awsGroupedBySpeaker as $speaker => $awses )
{
    $i +=1 ;
    $speaker = getLoginInfo( $speaker );
    $fname = $speaker['first_name'];
    $lname = $speaker['last_name'];
    $login = __get__( $speaker, 'login', '' );
    if( ! $login )
        continue;

    $piOrHost = $speaker[ 'pi_or_host'];
    $table .= "<tr> <td>$i</td> <td> " . $fname . ' ' . $lname
                . "<br><small> PI: $piOrHost </small>" . "</td>";
    $when = array( );
    foreach( $awses as $aws )
    {
        $awsDay = strtotime( $aws['date'] );
        $ndays = intval(( strtotime( 'today' ) - $awsDay) / (24 * 3600 ));
        array_push( $when, $ndays );
    }

    sort( $when );
    $line = daysToLine( $when, $totalDays, $blockSize = 28 );
    $table .= $line;
    $table .= "</tr>";

}

$table .= "</table>";
echo $table;

echo goBackToPageLink( "admin_acad.php", "Go back" );

?>
