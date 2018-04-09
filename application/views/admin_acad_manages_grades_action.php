<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

if( $_POST[ 'response' ] == 'Assign One' )
{
    $student = $_POST[ 'student_id' ];

    $_POST[ 'grade_is_given_on' ] = dbDateTime( 'now' );
    $_POST[ 'grade' ] = $_POST[ $student ];

    $res = updateTable( 'course_registration'
                    , 'student_id,semester,year,course_id'
                    , 'grade,grade_is_given_on'
                    , $_POST
                );

    if( $res )
        echo printInfo( "Successfully assigned grade for " . $student );
    else
        echo alertUser( "Could not assign grade for " . $student );

}
else if( $_POST[ 'response' ] == 'Assign All' )
{
    $year = $_POST[ 'year' ];
    $sem = $_POST[ 'semester' ];

    $regs = array_map(
        function( $x ) { return $x['student_id']; }
        , getCourseRegistrations( $_POST[ 'course_id' ], intval($year), $sem )
        );

    $gradeCSV = explode( PHP_EOL, $_POST[ 'grades_csv' ] );
    $gradeMap = array( );
    foreach( $gradeCSV as $i => $csv )
    {
        $l = splitAtCommonDelimeters( $csv );
        $email = $l[0];
        $grade = $l[1];

        $login = getLoginByEmail( $email );
        if( ! $login )
        {
            echo printWarning( "No valid user found with email <tt>$email</tt>. Ignoring ..." );
            continue;
        }

        if( ! in_array( $login, $regs ) )
        {
            echo printWarning( "<tt>$login</tt> has not registered for this course. Ignoring ..." );
            var_dump( $regs );
            continue;
        }

        // Else assign grade.
        $data = array( 'student_id' => $login, 'grade' => $grade );
        $data = array_merge( $_POST, $data );
        $res = updateTable( 'course_registration'
            , 'student_id,semester,year,course_id'
            , 'grade,grade_is_given_on'
            , $data
        );

        if( $res )
            echo printInfo( "Successfully assigned $grade for $login " );
        else
            echo alertUser( "Could not assign grade for $login " );

    }
}
else
    echo alertUser( 'Unknown type of request ' . $_POST[ 'response' ] );

echo goBackToPageLink( "admin_acad_manages_grades.php", "Go back" );

?>
