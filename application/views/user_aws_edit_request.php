<?php 
require_once BASEPATH.'autoload.php';

// User submit a request to change some description in his/her AWS. The request 
// must be approved by ACAD_ADMIN.
echo userHTML( );

// If we have come here by a post request, get the speaker and date and fetch 
// the aws as default value.

$default = array( );
$awsId = 0;
if( isset( $_POST['id'] ) )
{
    $awsId = $_POST['id'];
    $default = getAwsById( $awsId );
}

echo "<h3>Edit or add AWS entry</h3>";

echo alertUser( "Supervisor 1 must be a local faculty. Others can be from outside.", false );

echo '<form method="post" action="'.site_url("user/aws/edit_request/submit").'">';
echo editableAWSTable( $awsId );
echo "</form>";

echo goBackToPageLink( "user/aws", "Go back" );

?>
