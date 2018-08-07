<?php
require_once BASEPATH. 'autoload.php';
echo userHTML( );

$ref = $controller;

echo printInfo( "Here you can only change the host, class, title and description
    of the talk.", false );

if( intval($talkid) < 0 )
{
    printErrors( "Invalid talk or no talk selected." );
    redirect( "$ref/manages_talks" );
}

$data = [ 'id' => $talkid ];
$talk = getTableEntry( 'talks', 'id', $data );

echo '<form method="post" action="'.site_url("$ref/update_talk_action").'">';
echo dbTableToHTMLTable('talks', $talk
    , 'class,coordinator,host,title,description'
    , 'submit');
echo '</form>';

echo goBackToPageLink( "$ref/manages_talks", "Go Back" );

?>
