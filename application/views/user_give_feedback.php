<?php
require_once BASEPATH.'autoload.php';

if(! isset($controller))
    $controller = 'user';

echo userHTML();

echo"<h1>Feedback $course_id for $semester/$year </h1>";

echo printInfo("You can make a partial submission and come back and edit 
    your answers upto 24 hours. However, once fully completed, you won't 
    able to modify your answers." 
);

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
