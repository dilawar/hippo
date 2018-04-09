<?php

include_once( "header.php" );
include_once( "database.php" );
include_once 'check_access_permissions.php';
include_once './tohtml.php';
include_once './methods.php';

mustHaveAnyOfTheseRoles( array( 'USER' ) );

$columns = Array( 
    'speaker', 'title', 'abstract', 'date', 'time' 
    , 'is_presynopsis_seminar'
    , 'supervisor_1', 'supervisor_2'
    , 'tcm_member_1', 'tcm_member_2', 'tcm_member_3', 'tcm_member_4' 
);


$data = $_POST;
$rid = $_POST[ 'rid' ];

if( $_POST['response'] = 'edit' )
{
    $_POST[ 'id' ] = $rid;
    $res = updateTable( 'aws_requests', 'id', $columns, $data );
    if( $res )
    {
        echo printInfo( "Successfully added a request for updating your AWS" );
        //goToPage( "user.php", 0 );
        //exit( 0 );
    }
    else 
        echo printWarning( "Could not update your entry" );
}

echo goBackToPageLink( "user.php", "Go back" );
exit;

?>
