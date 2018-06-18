<?php

include_once BASEPATH.'autoload.php';
echo userHTML( );

echo '<h1>Edit presentation entry</h1>';

if( __get__( $_POST, 'response', '' ) == 'Edit'
    or __get__( $_POST, 'response', '') == 'Save' )
{
    echo printNote( "
        Consider adding <tt>URL</tt>. This is the place user can find material related
        to this presentation e.g. link to github repo, slideshare, drive etc..
        " );
    echo printNote( "We do not keep backup for your entry!" );
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
            echo flashMessage( 'Successfully saved your presentation entry. To 
                go back, click on <a disabled>Done Editing</a> link at the bottom of page.
                ' );
            // We do not exit from here. User might want to edit some more.
            echo goBackToPageLink( "user/jc", "Done editing" );
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
        goToPage( 'user_jc.php', 1 );
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
        goToPage( 'user_jc.php', 1 );
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
        goToPage( "user/jc" );
        exit;
    }
}
else
{
    echo alertUser(
        'This action ' . $_POST[ 'response' ]
        . ' is not supported yet'
    );
    goToPage( 'user/jc' );
    exit;
}

echo ' <br />';
echo goBackToPageLink( 'user/jc', 'Go Back' );

?>
