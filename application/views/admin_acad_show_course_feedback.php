<?php
require_once BASEPATH . 'autoload.php';
echo userHTML( );

if( ! isset($year) )
    $year = getCurrentYear();
if( ! isset($semester) )
    $semester = getCurrentSemester();
echo selectYearSemesterForm( $year, $semester );

// Now get all the feedback available for this year and semester.
$feedback = executeQuery( "SELECT * FROM poll_response WHERE status='VALID' 
    AND external_id LIKE '$year.$semester.%'" 
    );

// Create a map out of feedback keys will be student id and course id.
$feedbackMap = array();
foreach( $feedback as $f)
{
    $key = $f['login'].'@@'.$f['external_id'];
    $feedbackMap[$key][] = $f['response'];
}

echo p("Total " . count( $feedbackMap ) . " feedback entries are found." );

// Get question bank.
$questionBank = getQuestionsWithCategory( 'course feedback' );
$questionsById = array();
foreach( $questionBank as $cat => $qs )
    foreach( $qs as $q )
        $questionsById[$q['id']] = $q['question'];
ksort($questionsById);

echo '<h1> Data </h1>';
$table = '<table class="info">';

$table .= '<tr><th></th>';
foreach( $questionsById as $qid => $qtitle )
    $table .= "<th>$qtitle </th>";
$table .= '</tr>';

ksort( $feedbackMap );
foreach( $feedbackMap as $key => $answers )
{
    $table .= "<tr>";
    $dataInKey = explode( '@@', $key );
    $student = $dataInKey[0];
    $table .= "<td> $student </td>";
    foreach( $answers as $res )
        $table .= "<td> $res </td>";
    $table .= "</tr>";
}

$table .= '</table>';
echo $table;


?>
