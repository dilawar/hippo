<?php
require_once BASEPATH . 'autoload.php';
echo userHTML( );

if( ! isset($year) )
    $year = getCurrentYear();
if( ! isset($semester) )
    $semester = getCurrentSemester();
echo selectYearSemesterForm( $year, $semester );

// Now get all the feedback available for this year and semester.
$feedback = executeQuery( "SELECT * FROM poll_response WHERE status='VALID' AND external_id LIKE '%.$semester.$year'" );
echo p("Total " . count( $feedback ) . " feedback entries are found." );

?>
