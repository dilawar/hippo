<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

if( ! isJCAdmin( $_SESSION[ 'user' ] ) )
{
    echo printWarning( "You do not have permission to access this page." );
    goToPage( 'user.php' );
    exit;
}


echo '<h1>Edit presentation entry</h1>';

echo printInfo( "
    Consider adding <tt>URL</tt>. This is the place user can find material related
    to this presentation e.g. link to github repo, slideshare, drive etc..
    " );

echo alertUser( "We do not keep backup for your entry!" );

// If current user does not have the privileges, send her back to  home
// page.
if( ! isJCAdmin( $_SESSION[ 'user' ] ) )
{
    echo printWarning( "You don't have permission to access this page" );
    echo goToPage( "user.php", 2 );
    exit;
}

$editables = 'title,description,url';

if( __get__( $_POST, 'response', '' ) == 'Reschdule' )
{
    $editables = 'date';
}

echo '<form action="#" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable( 'jc_presentations', $_POST, $editables );
echo '</form>';

if( __get__( $_POST, 'response', '' ) == 'submit' )
{

    $res = updateTable( 'jc_presentations', 'id,jc_id,presenter,date'
        , 'title,description,url', $_POST
    );

    if( $res )
    {
        echo printInfo( 'Successfully updated presentation entry' );
    }
}


echo " <br /> <br /> ";
echo goBackToPageLink( 'user_jc_admin.php', 'Done editing, Go Back' );

?>
