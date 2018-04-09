<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

// Manage courses. Add into course_registration.
if( $_POST[ 'response' ] == 'submit' )
{
    $_POST[ 'last_modified_on' ] = dbDateTime( 'now' );
    $_POST[ 'registered_on'] = dbDateTime( 'now' );
    $res = insertIntoTable( 'course_registration'
                , 'student_id,semester,year,type,course_id,registered_on,last_modified_on'
                , $_POST );

    if( $res )
    {
        echo printInfo( "You are successfully registered for the course" );
        echo arrayToTableHTML( $_POST, 'info' );
        //goBack( 'user_manages_courses.php', 1 );
        //exit;
    }
    else
    {
        echo minionEmbarrassed( "Failed to register you for the course." );
        echo printWarning( "Most likely you are already registered for this course" );
    }

}
else if( in_array( $_POST[ 'response' ], getTableColumnTypes( 'course_registration', 'type' ) ) )
{
    // Drop this course for given user.
    $_POST[ 'type' ] = $_POST[ 'response' ];
    $res = updateTable( 'course_registration'
        , 'student_id,semester,year,course_id', 'type', $_POST
    );
    if( $res )
        echo printInfo( "Successfully changed enrollment." );
    else
        echo minionEmbarrassed( "Failed to change enrollment for this." );
}
else if( $_POST[ 'response' ] == 'enroll_new' )
{
    $emails = splitAtCommonDelimeters( $_POST[ 'logins'] );
    foreach( $emails as $email )
    {
        $user = findAnyoneWithEmail( $email );
        if( ! $user )
        {
            echo printWarning( "$email is not found in my database. Probably
                a mistake. I am ignoring this candidate. "
                );
            continue;
        }

        $_POST[ 'student_id' ] = $user[ 'login'];
        $_POST[ 'registered_on' ] = dbDateTime( 'now' );
        $_POST[ 'last_modified_on' ] = dbDateTime( 'now' );
        $user = getLoginByEmail( $email );
        $res = insertIntoTable( 'course_registration'
            , 'student_id,registered_on,course_id,semester,year,type'
            , $_POST
        );
        if( $res )
            echo printInfo( "Successfully enrolled $user" );
    }
}
else if( $_POST[ 'response' ] == 'quick_enroll' )
{
    $enrolls = explode( PHP_EOL, $_POST[ 'enrollments' ] );
    foreach( $enrolls as $i => $en )
    {
        $l = splitAtCommonDelimeters( $en, ':' );
        if( count( $l ) < 2 )
        {
            echo printWarning( "<tt>$en</tt> is not properly formatted. Ignoring.." );
            continue;
        }

        $email = $l[0];
        $etype = $l[1];

        if( ! in_array( $etype, array( 'AUDIT', 'CREDIT' ) ) )
        {
            echo printWarning( "Unknown registration type: '$etype'. Ignoring ..." );
            continue;
        }


        $login = getLoginByEmail( $email );
        if( ! $login )
        {
            echo printWarning( "No valid login found for $email. Ignoring ... " );
            continue;
        }

        $data = array( );

        $data[ 'registered_on' ] = dbDateTime( 'now' );
        $data[ 'last_modified_on' ] = dbDateTime( 'now' );
        $data[ 'student_id' ] = $login;
        $data[ 'type' ] = $etype;
        $courseId = $_POST[ 'course_id' ];

        $data = array_merge( $data, $_POST );

        try {
            $res = insertOrUpdateTable( 'course_registration'
                , 'student_id,course_id,year,semester'
                , 'student_id,course_id,type,year,semester,registered_on,last_modified_on'
                , $data
            );
        } catch (Exception $e) {
            echo printWarning( "failed to update table. Error was " . $e->getMessage( ) );
            continue;
        }

        if( $res )
            echo printInfo( "Successfully enrolled $login to $courseId with type $etype." );
        else
            echo printWarning( "Failed to enroll $login to $courseId." );

    }
}
else if( $_POST[ 'response' ] == 'drop_course' )
{
    $_POST[ 'status' ] = 'DROPPED';

    $user = $_POST[ 'student_id' ];
    $course = $_POST[ 'course_id' ];
    $sem = $_POST[ 'semester' ];
    $year = $_POST[ 'year' ];

    $res = updateTable( 'course_registration'
        , 'student_id,course_id,year,semester', 'status', $_POST
    );

    if( $res )
        echo printInfo( "Successfully dropped $user from $course $sem/$year " );
    else
        echo printInfo( "Failed to drop $user from $course $sem/$year " );
}
else
    echo alertUser( 'Unknown type of request ' . $_POST[ 'response' ] );

echo goBackToPageLink( "admin_acad_manages_enrollments.php", "Go back" );

?>
