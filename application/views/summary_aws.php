<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo "<h1>Annual Work Seminars Summary</h1>";

echo ' <h2>Upcoming AWS</h2> ';
$upcomingAWS = getUpcomingAWS( );
$dateMap = array( );
foreach( $upcomingAWS as $aws )
    $dateMap[ $aws['date'] ][] = $aws;

$table = '<table class="info">';
foreach ($dateMap as $date => $awses)
{
    $table .= '<tr>';
    $table .= '<td>' . humanReadableDate( $date ) . '</td>';
    foreach( $awses as $aws )
    {
        $piOrHost = getPIOrHost( $aws[ 'speaker' ] );
        $table .= '<td>' . loginToHTML( $aws['speaker']) . ' <br /> '
            . $piOrHost . '</td>';
    }
    $table .= '</tr>';
}
$table .= '</table>';
echo $table;

echo ' <h2>List of AWS Speakers</h2> ';
echo alertUser( "If your name is not on this list, please write to "
    . mailto( "acadoffice@ncbs.res.in", "Academic Office" ) . "."
);

$speakers = getAWSSpeakers( );

$table = ' <table class="info sortable"> ';
$table .= '<tr> <th></th> <th>Name</th> <th>PI/HOST</th> <th>Specialization</th> </tr>';
$index = 0;
foreach( $speakers as $i => $speaker )
{
    $login = $speaker['login'];
    $speaker = getLoginInfo( $login );

    if( ! trim($login) )
        continue;

    $index += 1;
    $name = arrayToName( $speaker );
    $pi = getPIOrHost( $login );
    $specialization = getSpecialization( $login, $pi );

    $row = '<tr>';
    $row = "<td>$index<td>$name ($login)</td> <td>$pi</td> <td>$specialization</td>";
    $row .= '</tr>';
    $table .= $row;
}
$table .= '</table>';
echo $table;

echo goBackToPageLink( "admin_acad.php", "Go back" );

?>
