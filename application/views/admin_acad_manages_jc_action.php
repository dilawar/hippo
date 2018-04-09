<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'methods.php';
include_once 'tohtml.php';


if( $_POST[ 'response' ] == 'Add' )
{
    $res = insertIntoTable( 'journal_clubs'
        , 'id,title,day,status,time,venue,description'
        , $_POST
    );

    if( $res )
    {
        echo "Added JC successfully";
        echo goToPage( "admin_acad_manages_jc.php", 1 );
    }
}
else if( $_POST[ 'response' ] == 'Update' )
{
    $res = updateTable( 'journal_clubs'
        , 'id'
        , 'title,day,status,time,venue,description'
        , $_POST
    );

    if( $res )
    {
        echo "Updated successfully";
        echo goToPage( "admin_acad_manages_jc.php", 1 );
    }
}
else if( $_POST[ 'response' ] == 'Delete' )
{
    $res = deleteFromTable( 'journal_clubs' , 'id' , $_POST );
    if( $res )
    {
        echo "Updated deleted entry";
        echo goToPage( "admin_acad_manages_jc.php", 1 );
    }
}


echo goBackToPageLink( "admin_acad_manages_jc.php", "Go back" );

?>
