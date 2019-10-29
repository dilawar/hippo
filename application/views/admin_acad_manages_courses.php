<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

// In config/constansts.php
global $symbDelete;

// global to keep the value.
$buttonVal = 'Add';

function showEditCourse( $buttonVal, $course )
{
    global $symbDelete;
    $editHTML = "<h3 id='editcourse'>$buttonVal course</h3>";
    $editHTML .= printNote("<tt>ID</tt> of course can not be more than 8 chars.");
    $editHTML .= '<form method="post" action="'.site_url('adminacad/all_courses_action').'">';
    $editHTML .= dbTableToHTMLTable( 'courses_metadata', $course
            , 'id,credits:required,name:required,description,'
                . 'instructor_1:required,instructor_2,instructor_3'
                . ',instructor_4,instructor_5,instructor_6,instructor_extras'
                . ',comment'
            , $buttonVal
            );

    $editHTML .= ' <button title="Delete this entry" type="submit" onclick="AreYouSure(this)"
            name="response" value="Delete">' . $symbDelete .
        '</button>';
    $editHTML .= '<button id="addMoreInstructors">Add more instructors</button>';
    $editHTML .= '</form>';
    return $editHTML;
}


// Javascript.
$courses = getTableEntries( 'courses_metadata' );

$instructors = array();
foreach( getFaculty( ) as $fac )
    $instructors[ ] = $fac[ 'email' ];

$coursesMap = array( );
$coursesId = array_map( function( $x ) { return $x['id']; }, $courses );

foreach( $courses as $course )
    $coursesMap[ $course[ 'id' ] ] = $course;

echo p( "To update schedule of currently running courses," 
    . goBackToPageLinkInline( "adminacad/courses", "click here." ));
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
    $( "input[id^=courses_metadata_instructor]" ).attr( "placeholder", "Type a course ID to edit" );
  });
});

// Autocomplete speaker.
$( function() {
    var coursesDict = <?php echo json_encode( $coursesMap ) ?>;
    var courses = <?php echo json_encode( $coursesId ); ?>;
    $( "#course" ).autocomplete( { source : courses });
    $( "#course" ).attr( "placeholder", "Type to select a course ID." );
    $( "input[id^=courses_metadata_instructor]" ).autocomplete( { source : instructors });
    $( "input[id^=courses_metadata_instructor]" ).attr( "placeholder", "autocomplete" );
});
</script>

<?php


// Logic for POST requests.
$course = array('id' => '', 'day' => '', 'start_time' => '', 'end_time' => '' );
echo "<h1>All courses</h1>";
echo p( "The form to ADD a new course or EDIT a selected course is at the bottom." );
echo '<input id="filter_all_courses" placeholder="Type to filter courses" />';

echo coursesTable( $editable = true, $with_form = true, $class = "small_font" );

echo '<h1>Edit a course or add a new one</h1>';
echo '<form method="post" action="#editcourse">';
echo '<input id="course" name="id" type="text" value="" >';
echo '<button type="submit" name="response" value="show">Edit Course</button>';
echo '</form>';

// We can edit course using both _GET or _POST.
if( __get__( $_POST, 'response', 'Edit' ) && __get__( $_POST, 'id', false ) )
{
    $course = __get__( $coursesMap, $_POST['id'], null );
    if( $course )
        $buttonVal = 'Update';
}
elseif( __get__( $_GET, 'id', false ) )
{
    $course = __get__( $coursesMap, $_GET['id'], null );
    if( $course )
        $buttonVal = 'Update';
}


echo showEditCourse($buttonVal, $course);

echo "<br/><br/>";
echo goBackToPageLink( 'adminacad/home', 'Go back' );
echo '<br>';
?>

<!-- This should be copy pasted -->
<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>

<!-- Filter table -->
<script type="text/javascript" charset="utf-8">
var $rowsRequest = $('#all_courses tr');
$('#filter_all_courses').keyup(function() {
    var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

    $rowsRequest.show().filter(function() {
        var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
        return !~text.indexOf(val);
    }).hide();
});
</script>

