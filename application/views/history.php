<?php 
/* Come to this page, only if history is empty. Come from preference.php */
include('header.php'); 

?>

<h3> Let us know what you did last year </h3>

<p>If you have not done any TA job in the past, or your previous job was in any 
other department then select course code <b>xx</b></p>
<p> <b>Please be careful! Once submitted, you can not change these values.</b> </p>

<?php
include("methods.php");
$init = $_SESSION['init'];
$base_url = $init['base_url'];
$this_sem = $_SESSION['sem'];
$prevSem = getPreviousSem($this_sem, 1);
$pprevSem = getPreviousSem($this_sem, 2);
$course_list_1 = getCourseList($prevSem);
$course_list_2 = getCourseList($pprevSem);

/* make sure empty course list is not fetched. */
if((!$course_list_1) or (!$course_list_2))
{
    echo printErrorSevere("Course list of previous semesters do not exists. ");
    echo printErrorSevere("Please tell Admin about it.");
    exit;
}

?>

<table class="history">
<form action="fill_history.php" method="post">
<tr><td> <small>
<?php echo printSem($prevSem) ?> </small>
<?php echo generateSelect($prevSem, $course_list_1);	?> </td></tr>
<tr><td> <small>
<?php echo printSem($pprevSem) ?> </small>
<?php echo generateSelect($pprevSem, $course_list_2);	?> </td></tr>
</table>
<input class="history" type="submit" name="Submit" value="Submit" />
</form>
<?php

?>
