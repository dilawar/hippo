<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN', 'ADMIN' ) );

include_once 'methods.php';
include_once 'database.php';
include_once 'tohtml.php';

// We are here to send an email. 
$email = array( );
if( $_POST )
{
    $templ = json_decode( $_POST[ 'template' ], $assoc = true );

    $email[ 'email_body' ] = $templ[ 'email_body'];
    $email[ 'recipients' ] = $templ[ 'recipients'] ;
    $email[ 'cc' ] = $templ[ 'cc'] ;
    $email[ 'subject'] = $_POST[ 'subject' ];

    echo '<form method="post" action="admin_acad_send_email_action.php">';
    echo dbTableToHTMLTable( 'emails', $email, 'recipients,cc,subject,email_body' 
        , 'send' );
    echo '</form>';
}


echo goBackToPageLink( 'admin_acad_email_and_docs.php', 'Go back' );


?>
