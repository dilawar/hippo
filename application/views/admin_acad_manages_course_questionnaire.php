<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$editable = 'category,question,choices,type';

$defaults = array( 'last_modified_on' => dbDateTime( 'now' )
    , 'choices' => 'Strongly Disagree,Disagree,Neutral,Agree,Strongly Agree'
    , 'id' => getUniqueID( 'course_feedback_questions' )
    );

echo ' <h1>Add new question</h1> ';

echo printInfo( 
   'Make sure to write proper category and subcategory. For example, course feedback question, 
    category is <tt>Course Feedback</tt> and while the subtcategories could be <tt>Personal 
    Qualities Of Instructors</tt>, <tt>Evaluation</tt> etc..'
    );

// Form to add a question.
echo '<form action="'.site_url("adminacad/addquestion").'" method="post">';
echo dbTableToHTMLTable( 'course_feedback_questions', $defaults, $editable, 'Add' );
echo '</form>';

echo goBackToPageLink( "adminacad/home", "Go Back" );


// QUESTION BANK.

$qbank = getTableEntries( 'course_feedback_questions', 'id', "status='VALID'" );
$qbankMap = array();
foreach( $qbank as $ques )
    $qbankMap[$ques['category']][] = $ques;

// Show question bank.
echo '<h1>Question bank</h1>';

echo "<table id=\"question_bank\" class=\"exportable\">";
foreach( $qbankMap as $category => $questions )
    echo courseFeedbackQuestions( $category, $questions, $controller );
echo "</table>";
echo '<hr />';

echo ' <br />';
echo goBackToPageLink( "adminacad/home", "Go Back" );

?>

<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>
