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
    $attachments = __get__($_POST, 'attachments', '');

    echo '<form method="post" action="'.site_url( "$ref/send_email_action" ) . '">';
    echo dbTableToHTMLTable( 'emails', $email
        , 'recipients,cc,subject,email_body'
        , 'send' );
    echo '<input type="hidden" name="attachments" value="'.$attachments.'" />';
    echo '</form>';
    if(file_exists($attachments))
        echo printNote("Attaching: $attachments");
}

echo goBackToPageLink( "$ref/manages_talks", 'Go back' );

?>
