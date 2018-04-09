<?php 
include_once("sqlite.php");
function printStudentInformation($data) 
{

	$str = "<b> Roll no : </b> : ";
	$str .= $data['roll']; 
	$str .= "<br>";

	if(strlen($data['roll']) < 7)
	{
		$_SESSION['completeInfo'] = "no";
	} 
	$str .= "<b> Specialization : </b>";
	$course = $data['specialization'];
	if($course == "xx") {
		$str .= "Not Given <br>";
		$_SESSION['completeInfo'] = "no";
	}
	else if($course == "ee1") {
		$str .= "Communication <br>";
	}
	else if($course == "ee2") {
		$str .= "Control and Computing <br>";
	}
	else if($course == "ee3") {
		$str .= "Power Electronics and System <br>";
	}
	else if($course == "ee4") {
		$str .= "Microelectronics and VLSI <br>";
	}
	else {
		$str .= "Electronic Systems <br>";
	}
	
	$str .= "<b> Program </b>";
	$prog = $data['program'];
	switch($prog) 
	{
	case "xx" :
		$str .= "Not given <br>";
		$_SESSION['completeInfo'] = "no";
		break;
	case "rs" :
		$str .= "Research Scholar <br>";
		break;
	case "mtech" :
		$str .= "Master of Technology <br>";
		break;
	case "dd" :
		$str .= "Dual Degree (B.Tech + M.Tech.) <br>";
		break;
	} 

	$str .= "<b> Category : </b>";

	$cat = $data['category'];
	switch($cat) 
	{
	case "xx" :
		$str .= "Not given <br>";
		$_SESSION['completeInfo'] = "no";
		break;
	case "ta" :
		$str .= "Teaching Assistant <br>";
		break;
	case "ira" :
		$str .= "Institute Research Assistant <br>";
		break;
	case "pra" :
		$str .= "Project Research Assistant <br>";
		break;
	case "sf":
		$str .= "Self Financed <br>";
		break;
	case "qip" :
		$str .= "Qualility Imporvement Program <br>";
		break;
	case "sponsored" :
		$str .= "Sponsored <br>";
		break;
	}

	$gradYear = $data['gradYear'];
	$gradMonth = $data['gradSem'];
	
	$str .= "<b> Graduating on </b> : ".$gradYear." , ".$gradMonth." semester <br>";

	return $str;
}

function printCourse($course) 
{
	$id = $course['id'];
	$name = $course['name'];
	$faculty = $course['faculty'];
	$fac = getFacultyName($faculty);

	return $id." : ".$fac['lname'].", ".$fac['fname'];
}

function printPreference($preferences, $this_sem)
{
  $first = getCourseNameFaculty($preferences['preference1'], $this_sem);
	$second = getCourseNameFaculty($preferences['preference2'], $this_sem);
	$third = getCourseNameFaculty($preferences['preference3'], $this_sem);
	$faculty1 = getFacultyName($first['faculty']);
	$faculty2 = getFacultyName($second['faculty']);
	$faculty3 = getFacultyName($third['faculty']);

	$str .= "<table id='table_output_big' border='1'>";
	$str .= "<tr> <td> First Preference </td> <td> <b>".$first['id']." : ".$faculty1['lname'].", ".$faculty1['fname']."</b> </td> </tr>";
	$str .= "<tr> <td> Second Second</td> <td> <b>".$second['id']." : ".$faculty2['lname'].", ".$faculty2['fname']."</b> </td> </tr>";
	$str .= "<tr> <td> Third Preference </td> <td> <b>".$third['id']." : ".$faculty3['lname'].", ".$faculty3['fname']."</b> </td> </tr>";
	$str .= "</table>";
	
	return $str;
}
?>
