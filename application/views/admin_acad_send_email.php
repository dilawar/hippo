<?php
require_once BASEPATH.'autoload.php';

$ref = $controller;

// We are here to send an email. 
$email = array( );
if( $_POST )
{
    $templ = json_decode( $_POST[ 'template' ], $assoc = true );
    $email[ 'email_body' ] = $templ[ 'email_body'];
    $email[ 'recipients' ] = $templ[ 'recipients'] ;
    $email[ 'cc' ] = $templ[ 'cc'] ;
    $email[ 'subject'] = $_POST[ 'subject' ];

    echo '<form method="post" action="'.site_url( "$ref/send_email_action" ) . '">';
    echo dbTableToHTMLTable( 'emails', $email, 'recipients,cc,subject,email_body' , 'send' );
    echo '</form>';
}

echo goBackToPageLink( "$ref/manages_talks", 'Go back' );

?>
