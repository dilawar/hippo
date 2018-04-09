<?php 

include_once "header.php" ;
include_once "methods.php" ;
include_once "database.php" ;
include_once "tohtml.php" ;

//var_dump( $_POST );

$gid = $_POST['gid'];

$editable = Array( "title", "description" );

if( strtolower($_POST['response']) == 'edit' )
{
    echo printInfo( "You can only change " . implode( ", ", $editable ) 
        . " here. If you want to change other fields, you have to delete 
        this request a create a new one." );

    $requests = getRequestByGroupId( $gid );
    // We only edit once request and all other in the same group should get 
    // modified accordingly.
    $request = $requests[0];
    echo "<form method=\"post\" action=\"user_show_requests_edit_submit.php\">";
    echo dbTableToHTMLTable( "bookmyvenue_requests", $request, $editable );
    //echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\" />";
    //echo "<button class=\"submit\" name=\"response\" value=\"submit\">Submit</button>";
    echo "</form>";
}

// delete and DO_NOTHING  always come from AreYouSure( ) function.
else if( strtolower($_POST['response']) == 'cancel' || 
    $_POST[ 'response' ] == 'delete' )
{
    $res = changeStatusOfRequests( $_POST['gid'], 'CANCELLED' );
    if( $res )
    {
        echo printInfo( "Successfully cancelled request" );
        goToPage( "user_show_requests.php", 0 );
    }
    else
        echo printWarning( "Could not delete request " . $_POST['gid'] );

}
else if( $_POST[ 'response' ] == 'DO_NOTHING' )
{
    echo printInfo( "User said NO" );
    echo goToPage( __get__( $_SERVER, 'HTTP_REFERER', 'user.php' ), 0 );
    exit;
}
else
{
    echo printWarning( "Bad response " .  $_POST['response']  );
    exit;
}

echo goBackToPageLink( "user_show_requests.php", "Go back");
