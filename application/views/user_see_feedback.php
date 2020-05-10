<?php

require_once BASEPATH . 'autoload.php';

if (!isset($controller)) {
    $controller = 'user';
}

// Fix the options passed to this page. This could have space in it; they get
// replaced by %20. Time to put them back.
$cid = urldecode($course_id);

echo userHTML();

$cname = getCourseName($cid);

// A associative map of email => HTML
$instructors = getCourseInstructorsEmails($cid, $year, $semester);

echo"<h1>Feedback for '$cname ($cid)' for $semester/$year </h1>";

$questions = getCourseFeedbackQuestions();

if (!$questions) {
    echo flashMessage('No questions found in question back. Ask Academic Admin to 
        prepare a questionairre.');
    echo goBackToPageLink("$controller/home", 'Go Home');
}

$feedbackCourse = getCourseSpecificFeedback($year, $semester, $cid);
$instructorFeedbackMap = [];
foreach ($instructors as $email => $html) {
    $instructorFeedbackMap[$email] = getInstructorSpecificFeedback($year, $semester, $cid, $email);
}

$table = '<table class="info show_info">';
foreach ($questions as $cat => $qs) {
    $table .= "<tr><td colspan='3'> <strong> $cat </strong> </td></tr>";
    foreach ($qs as $q) {
        $qname = $q['question'];
        $qid = $q['id'];
        $qtype = $q['type'];

        if ('INSTRUCTOR SPECIFIC' == $q['type']) {
            $response = '';
            foreach ($instructors as $email => $html) {
                $res = $instructorFeedbackMap[$email][$qid]['response'];
                $response .= " $html <tt> $res </tt> <br /> ";
            }
        } else {
            $response = '<tt>' . $feedbackCourse[$qid]['response'] . '</tt>';
        }

        $table .= "<tr> <td> $qname </td> <td style='min-width:45%'> $response </td> </tr> ";
    }
}
$table .= '</table>';

echo $table;

echo '<br />';
echo goBackToPageLink("$controller/courses", 'Go back');
