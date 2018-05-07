<?php

include_once 'header.php';
include_once 'database.php';

$_POST[ 'speaker' ] =  $_SESSION[ 'user' ];

$res = insertIntoTable( 'aws_requests'
        , array( 'speaker', 'title', 'abstract', 'supervisor_1', 'supervisor_2'
        , 'tcm_member_1', 'tcm_member_2', 'tcm_member_3', 'tcm_member_4' 
        , 'date', 'time' 
    ) , $_POST 
    );

if( $res )
{
    echo printInfo( 'Successfully created a request to edit AWS details ' );
    goToPage( 'user_aws.php', 1 );
    exit;
}
else
{
    echo minionEmbarrassed( 'I could not create a request to edit your AWS' );
}

echo goBackToPageLink( 'user_aws.php', 'Go back' );

?>
