<?php
include('header.php');
include('methods.php');
include('print.php');
include('mail.php');

$this_sem = $_SESSION['sem'];
$ldap = $_SESSION['ldap'];

echo "<p>Your details are recorded successfully. An email has been sent to your ldap account.</p>";
$details = getStudentInformation($ldap);

$msg = "<html> <body>";
$msg .= "<h3>Basic information <h3>";
$msg .= printStudentInfo($details);
$history = getHistoryOfPrevTwoSem($this_sem, $ldap);
$prevSem = getPreviousSem($this_sem, 1);
$pprevSem = getPreviousSem($this_sem, 2);
$pCourse = $history[0]['job'];
$ppCourse = $history[1]['job'];
$course1 = getCourseNameFaculty($pCourse, $prevSem);
$course2 = getCourseNameFaculty($ppCourse, $pprevSem);

$msg .= "<h3> Last two semesters jobs </h3>";
$msg .= "<table id='table_output_big', border='1'>";
$msg .= "<tr> <td>".printSem($prevSem);
$msg .= "</td> <td> <b>".printCourse($course1);
$msg .= "</b> </td> </tr>";
$msg .= "<tr> <td>".printSem($pprevSem); 
$msg .= "</td> <td> <b>".printCourse($course2); 
$msg .= "</b> </td> </tr> </table>";

$db = "ta".$this_sem;
$init = $_SESSION['init'];
$ldap = $_SESSION['ldap'];

$prefer = getPreferences($this_sem, $ldap);

$msg .= "<h3> Your preferences for this semester </h3>";
$msg .= printPreference($prefer, $this_sem);
echo $msg;
echo "<html><body> <form name='exit' action='index.php' method='post'>";
echo "<br>";
echo "<input class='logout' type='submit' value='Logout' />";
echo "</form> </body>  </html>";

sendEmail($msg, $ldap);
session_destroy();
?>
