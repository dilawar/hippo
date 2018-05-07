<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN', 'ADMIN' ) );

include_once 'methods.php';
include_once 'database.php';
include_once 'tohtml.php';

if( $_POST['response'] == 'DO_NOTHING' )
{
    echo printInfo( "User said do nothing.");
    goBack( "admin_acad_manages_courses.php", 0 );
    exit;
}


// Instructor extras has to be reformatted.
$extraInstTxt = '';
if( is_array( __get__( $_POST, 'more_instructors', false) ) )
    $extraInstTxt = implode(',', $_POST[ 'more_instructors' ] );

// Append to already existibg extra instructiors.
if( __get__( $_POST, 'instructor_extras', '' ) && $extraInstTxt )
    if( $extraInstTxt )
        $_POST[ 'instructor_extras' ] .= ',' . $extraInstTxt;
else
    if ( $extraInstTxt )
        $_POST[ 'instructor_extras' ] =  $extraInstTxt;

if( $_POST['response'] == 'delete' )
{
    // We may or may not get email here. Email will be null if autocomplete was 
    // used in previous page. In most cases, user is likely to use autocomplete 
    // feature.


    if( strlen($_POST[ 'id' ]) > 0 )
    {
        $res = deleteFromTable( 'courses_metadata', 'id', $_POST );
        if( $res )
        {
            echo printInfo( "Successfully deleted entry" );
            goBack( 'admin_acad_manages_courses.php', 0 );
            exit;
        }
        else
            echo minionEmbarrassed( "Failed to delete speaker from database" );
    }
}
else if ( $_POST[ 'response' ] == 'Add' ) 
{
    echo printInfo( "Adding a new course in current course list" );
    if( strlen( $_POST[ 'id' ] ) > 0 )
    {
        $res = insertIntoTable( 
            'courses_metadata'
            , 'id,name,credits,description' 
                .  ',instructor_1,instructor_2,instructor_3'
                . ',instructor_4,instructor_5,instructor_6,instructor_extras,comment'
            , $_POST 
            );

        if( ! $res )
            echo printWarning( "Could not add course to list" );
        else
        {
            goBack( 'admin_acad_manages_courses.php', 0 );
            exit;
        }
    }
    else
        echo printWarning( "Course ID can not be empty!" );
    

}
else if ( $_POST[ 'response' ] == 'Update' ) 
{
    $res = updateTable( 'courses_metadata'
            , 'id'
            , 'name,credits,description' 
                .  ',instructor_1,instructor_2,instructor_3'
                . ',instructor_4,instructor_5,instructor_6,instructor_extras,comment'
            , $_POST 
            );

    if( $res )
    {
        echo printInfo( 'Updated course : ' . $_POST[ 'id' ] );
        goBack( 'admin_acad_manages_courses.php', 0 );
        exit;
    }
}

echo goBackToPageLink( 'admin_acad_manages_courses.php', 'Go back' );


?>
