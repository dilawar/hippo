<?php
include_once FCPATH.'system/autoload.php';
echo userHTML( );

// We remain on this page to show 
$form = '<form method="post" action="#">
    <table class="">
        <tr>
        <td> <input type="text" name="user" value="" placeholder="student@ncbs.res.in" /> </td>
        <td> <button type="submit">Show Enrollment</button></td>
        </tr>
    </table>
    </form>';
echo $form;

if( __get__( $_POST, 'user', '' ) != '' )
{
    $user = $_POST['user'];
    echo "<h2> Showing enrollments of user '$user'</h2>";
    $enrolls = getTableEntries( 'course_registration', 'year', "status='VALID' AND student_id='$user'"); 
    foreach( $enrolls as &$enroll )
    {
        $enroll['course_name'] = getCourseName( $enroll['course_id'] );
    }

    if( count($enrolls) == 0 )
    {
        echo printInfo( "No course found fro user $user in my database." );
    }
    else
    {
        echo p("Found " . count( $enrolls ) . " record(s).");
        echo arraysToCombinedTableHTML( $enrolls, 'info exportable' );
    }
}
else
{
    echo printInfo("Please select a user id to continue.");
}



echo ' <br />';
echo goBackToPageLink( "$controller/home", "Go Back" );

?>

<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>
