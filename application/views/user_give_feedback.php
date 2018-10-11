<?php
require_once BASEPATH.'autoload.php';

if(! isset($controller))
    $controller = 'user';

echo userHTML();

echo"<h1>Feedback $course_id for $semester/$year </h1>";

echo printInfo("You won't able to modify your answer after 24 hours of first submission.", false );

$questionSubcat = getQuestionsWithCategory( 'course feedback' );
if( ! $questionSubcat )
{
    echo flashMessage( "No questions found in question back. Ask Academic Admin to 
        prepare a questionairre." );
    echo goBackToPageLink( "$controller/home", "Go Home" );
}

$responses = getOldCourseFeedback($year, $semester, $course_id);

echo ' <form action="'. site_url('user/submitpoll') .'" method="post">';
echo questionsToPoll( $questionSubcat, $responses );
echo '<input type="hidden" name="year" value="' . $year .'" />';
echo '<input type="hidden" name="semester" value="' . $semester .'" />';
echo '<input type="hidden" name="course_id" value="' . $course_id .'" />';
echo '<button class="submit" type="submit">Submit response</button>';
echo '</form>';

echo '<br />';
echo goBackToPageLink( "$controller/courses", 'Go back' );

?>
