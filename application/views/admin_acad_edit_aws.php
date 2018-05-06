<?php
include_once 'header.php';
include_once 'database.php';
include_once 'methods.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles('AWS_ADMIN' );
echo userHTML( );

$aws = getTableEntry( 'annual_work_seminars', 'id', $_POST );

if( $_POST['response'] == 'edit' )
{
    $editable = 'is_presynopsis_seminar,title,abstract';
    echo ' <form action="#" method="post" accept-charset="utf-8"> ';
    echo dbTableToHTMLTable( 'annual_work_seminars', $aws, $editable, 'Update' );
    echo '</form>';
}

if( $_POST[ 'response' ] == 'Update' )
{
    $res = updateTable( 'annual_work_seminars', 'id', 'is_presynopsis_seminar,title,abstract', $_POST );
    if( $res )
    {
        echo printInfo( "Updated successfully" );
        echo goBack( "admin_acad.php", 1 );
    }
}

if( $_POST[ 'response' ] == 'delete' )
{
    $res = deleteFromTable( 'annual_work_seminars', 'id', $_POST );
    if( $res )
    {
        echo printInfo( "Successfully deleted entry" );
        echo goBack( "admin_acad.php", 1 );
    }
}

if( $_POST[ 'response' ] == 'DO_NOTHING' )
{
    echo goBack( "admin_acad.php", 0);
}


echo goBackToPageLink( "admin_acad.php", "Go Back" );


?>

