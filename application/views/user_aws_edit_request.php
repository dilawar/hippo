<?php 

// User submit a request to change some description in his/her AWS. The request 
// must be approved by AWS_ADMIN.

include_once( "header.php" );
include_once( "methods.php" );
include_once( 'tohtml.php' );
include_once( "check_access_permissions.php" );

mustHaveAnyOfTheseRoles( Array( 'USER' ) );

echo userHTML( );

?>

<?php

// If we have come here by a post request, get the speaker and date and fetch 
// the aws as default value.

$default = Array( );
$awsId = 0;
if( isset( $_POST['id'] ) )
{
    $awsId = $_POST['id'];
    $default = getAwsById( $awsId );
}

echo "<h3>Edit or add AWS entry</h3>";

echo alertUser( "Supervisor 1 must be a local faculty. Others can be from outside." );

echo '<form method="post" action="user_aws_edit_request_submit.php">';
echo editableAWSTable( $awsId );
echo "</form>";


echo goBackToPageLink( "user.php", "Go back" );

?>
