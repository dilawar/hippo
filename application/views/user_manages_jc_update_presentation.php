<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'USER' ) );

echo userHTML( );

echo '<h1>Edit presentation entry</h1>';

if( __get__( $_POST, 'response', '' ) == 'Edit'
    or __get__( $_POST, 'response', '') == 'Save'
    )
{
    echo printInfo( "
        Consider adding <tt>URL</tt>. This is the place user can find material related
        to this presentation e.g. link to github repo, slideshare, drive etc..
        " );
    echo alertUser( "We do not keep backup for your entry!" );
    $editables = 'title,description,url,presentation_url';

    echo '<form action="#" method="post" accept-charset="utf-8">';
    echo dbTableToHTMLTable( 'jc_presentations', $_POST, $editables, 'Save' );
    echo '</form>';

    $res = updateTable( 'jc_presentations', 'id'
        , 'title,description,url,presentation_url', $_POST);
    if( $res )
    {
        if( $_POST[ 'response' ] == 'Save' )
        {
            echo printInfo( 'Successfully saved your presentation entry.' );
            // We do not exit from here. User might want to edit some more.
            echo goBackToPageLink( "user_manages_jc.php", "Done editing" );
        }
    }
}
else if( __get__( $_POST, 'response', '' ) == 'Add My Vote' )
{
    $_POST[ 'status' ] = 'VALID';
    $_POST[ 'voted_on' ] = dbDate( 'today' );
    $res = insertOrUpdateTable( 'votes', 'id,voter,voted_on'
        , 'status,voted_on', $_POST );
    if( $res )
    {
        echo printInfo( 'Successfully voted.' );
        goToPage( 'user_manages_jc.php', 1 );
        exit;
    }
}
else if( __get__( $_POST, 'response', '' ) == 'Remove My Vote' )
{
    $_POST[ 'status' ] = 'CANCELLED';
    $res = updateTable( 'votes', 'id,voter', 'status', $_POST );
    if( $res )
    {
        echo printInfo( 'Successfully removed  your vote.' );
        goToPage( 'user_manages_jc.php', 1 );
        exit;
    }
}
else if( __get__( $_POST, 'response', '' ) == 'Acknowledge' )
{
    $_POST[ 'acknowledged' ] = 'YES';
    $res = updateTable( 'jc_presentations', 'id', 'acknowledged', $_POST );
    if( $res )
    {
        echo printInfo( 'Successfully acknowleged  your JC presentation.' );
        //goToPage( 'user_manages_jc.php', 1 );
        //exit;
    }
}
else
{
    echo alertUser(
        'This action ' . $_POST[ 'response' ]
        . ' is not supported yet'
    );
    goToPage( 'user_manages_jc.php', 3 );
    exit;
}

echo ' <br />';
echo goBackToPageLink( 'user_manages_jc.php', 'Go Back' );

?>
