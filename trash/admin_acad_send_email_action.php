<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN', 'ADMIN' ) );

include_once 'methods.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'mail.php';

if( $_POST['response'] == 'DO_NOTHING' )
{
    echo printInfo( "User said do nothing.");
    goBack( "admin_acad_email_and_docs.php", 0 );
    exit;
}
else if( $_POST['response'] == 'send' )
{
    $to = $_POST[ 'recipients' ];
    $msg = $_POST[ 'email_body' ];
    $cclist = $_POST[ 'cc' ];
    $subject = $_POST[ 'subject' ];

    echo  "<h2>Email content are following</h2>";
    $mdfile = html2Markdown( $msg, true );
    $md = file_get_contents( trim($mdfile) );

    if( $md )
    {
        echo printInfo( "Sending email to $to ($cclist ) with subject $subject" );
        $res = sendHTMLEmail( $msg, $subject, $to, $cclist );

        if( $res )
            echo "Email sent successfully.";
        else
            echo minionEmbarrassed( "Failed to send email" );
    }
    else
    {
        echo printWarning( "Could not send email" );
    }

}

echo goBackToPageLink( 'admin_acad_email_and_docs.php', 'Go back' );


?>
