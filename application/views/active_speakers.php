<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';

echo "<h2>Active AWS speakers</h2>";
$speakers = getAWSSpeakers( $sortby = 'joined_on' );

echo alertUser( "If you name is not in this list and you are suppose to give
    AWS, kindly inform academic office." );

echo ' <table class="show_speaker"> ';
$i = 0;
foreach( $speakers as $speaker )
{
    $i += 1;
    echo '<tr>';
    echo '<td>' . $i . '</td>';
    echo '<td>' . $speaker[ 'login' ] . '</td>';
    echo '<td>' . $speaker[ 'first_name' ] . ' ' . $speaker[ 'last_name'] . '</td>';
    echo '<td>' . $speaker[ 'laboffice' ] . '</td>';
    echo '</tr>';
}
echo '</table>';

?>

<a href="javascript:window.close();">Close Window</a>
