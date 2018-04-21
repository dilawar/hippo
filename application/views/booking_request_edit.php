<?php

// This should be the only interface to edit a booking request. It can be used
// by USER or ADMIN alike. If the current user is not the owner of request, then 
// she must belong of admin group which has the rights to modify any request.

require_once BASEPATH.'autoload.php';
echo userHTML( );
$user = whoAmI();
$editables = 'class,is_public_event,title,description';


$req = getRequestById( $gid, '1' );

if( $req['created_by'] != $user && mustHaveAllOfTheseRoles( 'BOOKMYVENUE_ADMIN') )
{
    flashMessage( "You don't have permission to edit this request.", 'error' );
    // redirect( 'user/book' );
}

if( mustHaveAllOfTheseRoles( 'BOOKMYVENUE_ADMIN') )
    $editables .= ',status';

echo ' <h2>Current entry is following.</h2>';
echo arrayToVerticalTableHTML( $req, 'info' );

echo ' <h1>Edit</h1>';
echo "<br><br>";
echo '<form method="post" action="">';
echo dbTableToHTMLTable( 'bookmyvenue_requests'
    , $defaults = $req
    , $editables = $editables
    , 'Update'
);
echo '<input type="hidden" name="editables" id="" value="'.$editables.'" />';
echo "</form>";


?>
