<?php
require_once BASEPATH.'autoload.php';

if(! isset($controller))
    $controller = 'user';

echo userHTML();

$cname = getCourseName( $course_id );
$instructors = getCourseInstructorsEmails( $course_id, $year, $semester );

echo"<h1>Feedback for '$cname ($course_id)' for $semester/$year </h1>";

echo printInfo("You can make a partial submission. Once fully completed, 
    you won't able to modify your answers." );

$questions = getCourseFeedbackQuestions();

if( ! $questions )
{
    echo flashMessage( "No questions found in question back. Ask Academic Admin to 
        prepare a questionairre." );
    echo goBackToPageLink( "$controller/home", "Go Home" );
}

echo ' <form action="'. site_url('user/submitfeedback') .'" method="post">';
echo courseFeedbackForm( $year, $semester, $course_id, $questions, $instructors);
echo '<input type="hidden" name="year" value="' . $year .'" />';
echo '<input type="hidden" name="semester" value="' . $semester .'" />';
echo '<input type="hidden" name="course_id" value="' . $course_id .'" />';
echo '<button class="submit" type="submit">Submit Response</button>';
echo '</form>';

echo '<br />';
echo goBackToPageLink( "$controller/courses", 'Go back' );

?>
