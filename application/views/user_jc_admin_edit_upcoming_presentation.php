<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

//if( ! isJCAdmin( $_SESSION[ 'user' ] ) )
//{
//    echo printWarning( "You do not have permission to access this page." );
//    goToPage( 'user.php' );
//    exit;
//}


echo '<h1>Edit presentation entry</h1>';

echo printInfo( "
    Consider adding <tt>URL</tt>. This should be the place people can find material related
    to this presentation e.g. link to github repo, slideshare, google-drive, dropbox etc. .
    " );

echo printNote( "We do not keep backup for your entry!" );

// If current user does not have the privileges, send her back to  home
// page.
if( ! isJCAdmin( whoAmI() ) )
{
    echo printWarning( "How did you manage to come here? You don't have permission to access this page!" );
    echo goBackToPageLink( "user/home", "Go Home" );
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
echo goBackToPageLink( 'user/jcadmin', 'Done editing, Go Back' );

?>
