<?php
require_once BASEPATH . 'autoload.php';
echo userHTML( );

if( ! isset($year) )
    $year = getCurrentYear();
if( ! isset($semester) )
    $semester = getCurrentSemester();
echo selectYearSemesterForm( $year, $semester );

function getQuestionName( &$questionBank, $qid )
{
    return $questionBank[$qid];
}

function one2oneMappingWithQID( $feedback, &$questionBank )
{
    $response = [];
    $a = [];
    foreach( $feedback as $f )
        $a[$f['question_id']] = $f['response'];

    foreach( $questionBank as $qid => $q )
        $response[$qid] = __get__( $a, $qid, 'NA' );

    return $response;
}

// Now get all the feedback available for this year and semester.
$feedback = getTableEntries( 'course_feedback_responses'
    , 'last_modified_on',  "status='VALID' AND year='$year' AND semester='$semester'" 
);

// Create a map out of feedback keys will be student id and course id.
$feedbackMap = array();
$instructorMap = array( );
$courseSpecificMap = array();
foreach( $feedback as $f)
{
    $key = $f['course_id'].'@@'.$f['year'] .'@@'.$f['semester'];
    $feedbackMap[$key][$f['login']][] = $f;

    $inst = __get__( $f, 'instructor_email', '' );
    if($inst)
        $instructorMap[$inst] = $f;
    else
        $courseSpecificMap[$inst] = $f;
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
    $caption = str_replace( "@@", ", ", $key);

    $table = '<table class="info">';
    $table .= "<caption style='text-align:left'> <big>$caption </big> </caption>";

    $table .= '<tr>';
    foreach( $questionsById as $qid => $qtitle )
        $table .= "<th> $qid. $qtitle </th>";
    $table .= '</tr>';

    foreach( $feedbacks as $student => $fs )
    {
        $table .= "<tr>";
        // collect by question id.
        $row = one2oneMappingWithQID( $fs, $questionsById );
        foreach( $row as $qid => $response )
            $table .= '<td>' . $response . '</td>';
        $table .= "</tr>";
    }
    $table .= '</table>';
    echo $table;
}

echo goBackToPageLink( "$controller/home", "Go Home" );

?>
