<?php 
include_once( "database.php" );

// Write the list of events in json format. This url is used by Calendar class 
// to read the event list and populate its user-interface accordingly.
$upcomingEvents = getEvents( strtotime( 'today' ) );
$formattedEvents = Array( );
foreach( $upcomingEvents as $event )
{
    $e = Array();
    $e['id'] = $event['gid'] . '.' . $event['eid'];
    $e['class'] = ''; //$event['class'];
    $e['title'] = $event['title'];
    $e['url'] = ''; //$event['url'];
    $e['start'] = strtotime( $event['date'] . ' ' . $event['start_time']);
    $e['end'] = strtotime( $event['date'] . ' ' . $event['end_time']);
    array_push( $formattedEvents, $e );
}

echo ( '{ "success" : 1, "result":' . json_encode( $formattedEvents ) . "}");
?>
