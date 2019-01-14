<?php
require_once BASEPATH . 'autoload.php';
echo userHTML( );

if( ! isset($year) )
    $year = getCurrentYear();
if( ! isset($semester) )
    $semester = getCurrentSemester();
echo selectYearSemesterForm( $year, $semester );

// Now get all the feedback available for this year and semester.
$feedback = getTableEntries( 'course_feedback_responses'
    , 'question_id',  "status='VALID' AND year='$year' AND semester='$semester'" 
);

// Create a map out of feedback keys will be student id and course id.
$feedbackMap = array();
foreach( $feedback as $f)
{
    $key = $f['course_id'].'@@'.$f['year'] .'@@'.$f['semester'];
    $feedbackMap[$key][] = $f;
}

echo p("Total " . count( $feedbackMap ) . " feedback entries are found." );

// Get question bank.
$questionBank = getQuestionsWithCategory( 'course feedback' );
$questionsById = array();
foreach( $questionBank as $cat => $qs )
    foreach( $qs as $q )
        $questionsById[$q['id']] = $q['question'];
ksort($questionsById);

echo '<h1> Feedback Data </h1>';

ksort( $feedbackMap );
foreach( $feedbackMap as $key => $feedbacks )
{
    $dataInKey = explode( '@@', $key );

    $caption = str_replace( "@@", " ", $key);
    $table = '<table class="info">';
    $table .= "<caption> $caption </caption>";

    $table .= '<tr>';
    foreach( $questionsById as $qid => $qtitle )
        $table .= "<th>$qtitle </th>";
    $table .= '</tr>';

    foreach( $feedbacks as $fs )
    {
        $table .= "<tr>";
        // $table .= arrayToRowHTML( $fs, 'info' );
        $table .= "</tr>";
    }
    $table .= '</table>';
    echo $table;
}

echo goBackToPageLink( "$controller/home", "Go Home" );

?>
