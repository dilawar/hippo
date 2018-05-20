<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$editable = 'category,subcategory,question,choices';

$defaults = array( 'last_modified_on' => dbDateTime( 'now' )
    , 'choices' => 'Strongly Disagree,Disagree,Neutral,Agree,Strongly Agree'
    );

$defaults['id'] = getUniqueID( 'question_bank' );

echo ' <h1>Add new question</h1> ';

echo printInfo( 
   'Make sure to write proper category and subcategory. For example, course feedback question, 
    category is <tt>Course Feedback</tt> and while the subtcategories could be <tt>Personal 
    Qualities Of Instructors</tt>, <tt>Evaluation</tt> etc..'
    );

// Form to add a question.
echo '<form action="'.site_url("adminacad/addquestion").'" method="post">';
echo dbTableToHTMLTable( 'question_bank', $defaults, $editable, 'Add' );
echo '</form>';

echo goBackToPageLink( "adminacad/home", "Go Back" );


// QUESTION BANK.

$qbank = getTableEntries( 'question_bank', 'id', "status='VALID'" );

$qbankMap = array();
foreach( $qbank as $ques )
    $qbankMap[$ques['category']][$ques['subcategory']][] = $ques;

echo '<h1>Question bank</h1>';
foreach( $qbankMap as $category => $qbank )
{
    echo '<div class="">';
    echo "<h3> $category </h3>";
    echo questionBankByCategoryToTable( $qbank, $controller );
    echo '<div>';
    echo '<hr />';
}

echo ' <br />';
echo goBackToPageLink( "adminacad/home", "Go Back" );

?>
