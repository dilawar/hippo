<?php

require_once BASEPATH . 'autoload.php';
echo userHTML();

echo "<h2>Grades for <tt>$course_id</tt> for $year/$semester. </h2>";

$enrollments = getCourseRegistrations($course_id, $year, $semester);
echo showEnrollmenTable($enrollments, $tdintr = 5);

$hide = 'registered_on,last_modified_on,grade_is_given_on';
$table = '<table class="info sortable">';
$ids = [];                    /* Collect all student ids.  */
$grades = [];
$allGradesHTML = '';                // Add all grades to table.

$table .= arrayToTHRow($enrollments[0], 'info', $hide);
foreach ($enrollments as $enrol) {
    $ids[] = encodeEmail($enrol['student_id']);
    $table .= '<tr>';
    $table .= '<form action="' . site_url('adminacad/gradecourse_submit') . '" method="post">';
    $table .= arrayToRowHTML($enrol, 'info', $hide, true, false);
    $table .= '<td>' . gradeSelect(encodeEmail($enrol['student_id']), $enrol['grade']) . '</td>';

    $gradeAction = 'Change';
    if ('X' == __get__($enrol, 'grade', 'X')) {
        $gradeAction = 'Assign';
    }

    $table .= "<td> <button class='btn btn-primary' 
        name='response' value='Assign One'>$gradeAction</button> </td>";

    $table .= '<input type="hidden" name="student_id" value="' . encodeEmail($enrol['student_id']) . '" >';
    $table .= '<input type="hidden" name="year" value="' . $enrol['year'] . '" >';
    $table .= '<input type="hidden" name="semester" value="' . $enrol['semester'] . '" >';
    $table .= '<input type="hidden" name="course_id" value="' . $enrol['course_id'] . '" >';
    $table .= '</form>';
    $table .= '</tr>';
}

$table .= '<input type="hidden" name="student_ids" value="' . implode(',', $ids) . '" >';
$table .= '</table>';

echo '<br /> <br />';
echo '<h2>Modify grades </h2>';
echo $table;

echo '<br />';
echo goBackToPageLink('adminacad/grades', 'Go back');
