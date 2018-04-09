<?php
include_once 'header.php';
include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

// Javascript.
$courses = getTableEntries( 'courses_metadata' );

$instructors = array();
foreach( getFaculty( ) as $fac )
    $instructors[ ] = $fac[ 'email' ];

$coursesMap = array( );
$coursesId = array_map( function( $x ) { return $x['id']; }, $courses );

foreach( $courses as $course )
    $coursesMap[ $course[ 'id' ] ] = $course;

?>

<script type="text/javascript" charset="utf-8">
var instructors = <?php echo json_encode( $instructors ); ?>;

$(function() {
  $("#addMoreInstructors").click(function(e) {

    e.preventDefault();
    $("#courses_metadata_extra_rows").append(
        `<tr>
        <td class='db_table_fieldname'>Instructor Extras</td>
        <td><input id='courses_metadata_instructor' type='text' name='more_instructors[]'  /> </td>
        </tr>
        `
    );
    $( "input[id^=courses_metadata_instructor]" ).autocomplete( { source : instructors });
    $( "input[id^=courses_metadata_instructor]" ).attr( "placeholder", "autocomplete" );
  });
});

// Autocomplete speaker.
$( function() {
    var coursesDict = <?php echo json_encode( $coursesMap ) ?>;
    var courses = <?php echo json_encode( $coursesId ); ?>;
    $( "#course" ).autocomplete( { source : courses });
    $( "#course" ).attr( "placeholder", "autocomplete" );
    $( "input[id^=courses_metadata_instructor]" ).autocomplete( { source : instructors });
    $( "input[id^=courses_metadata_instructor]" ).attr( "placeholder", "autocomplete" );
});
</script>

<?php

// Logic for POST requests.
$course = array( 'id' => '', 'day' => '', 'start_time' => '', 'end_time' => '' );


echo '<form method="post" action="#">';
echo '<input id="course" name="id" type="text" value="" >';
echo '<button type="submit" name="response" value="show">Edit Course</button>';
echo '</form>';

$buttonVal = 'Add';
if( __get__( $_POST, 'reponse', 'Edit' ) && __get__( $_POST, 'id', false ) )
{
    $course = __get__( $coursesMap, $_POST['id'], null );
    if( $course )
        $buttonVal = 'Update';
}

echo '<h3>Add/Edit course details</h3>';

echo '<form method="post" action="admin_acad_manages_courses_action.php">';

echo dbTableToHTMLTable( 'courses_metadata', $course
    , 'id,credits:required,name:required,description,'
        . 'instructor_1:required,instructor_2,instructor_3'
        . ',instructor_4,instructor_5,instructor_6,instructor_extras'
        . ',comment'
        , $buttonVal
    );


echo '<button title="Delete this entry" type="submit" onclick="AreYouSure(this)"
    name="response" value="Delete">' . $symbDelete .
    '</button>';
echo '<button id="addMoreInstructors">Add more instructors</button>';

echo '</form>';


echo "<br/><br/>";
echo goBackToPageLink( 'admin_acad.php', 'Go back' );
echo '<br>';


echo "<h1>All courses</h1>";
echo coursesTable( $editable = true, $with_form = true );


?>
