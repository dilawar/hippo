<?php

include_once 'database.php';
include_once './check_access_permissions.php';

mustHaveAllOfTheseRoles( array( "AWS_ADMIN" ) ); 

if( $_POST[ 'response' ] == 'submit' )
{
    if( strlen( $_POST[ 'speaker' ] ) < 1 )
    {
        echo printWarning( "Invlid speaker. Please go back and try again" );
    }
    else
    {

        $res = insertIntoTable( 'annual_work_seminars'
            , array( 'speaker', 'date'
            , 'supervisor_1', 'supervisor_2'
            , 'tcm_member_1', 'tcm_member_2', 'tcm_member_3', 'tcm_member_4'
            , 'abstract', 'title')
            , $_POST );

        if( $res )
        {
            echo printInfo( "Successfully added new AWS entry" );
            echo goToPage( "admin_acad.php",  1 );
            exit;
        }
        else
            echo minionEmbarrassed( "I could not insert AWS entry" );
    }
}

echo goBackToPageLink( "admin_acad_add_aws_entry.php", "Go back" );


?>
