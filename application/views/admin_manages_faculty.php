<?php

require_once BASEPATH. 'autoload.php';

echo userHTML( );

$action = 'add';

$faculty = getFaculty( );
$facultyMap = array( );
$default = array( );
$specialization = array( );

$symbDelete = '<i class="fa fa-trash-o"></i>';


foreach( $faculty as $fac )
{
    $facultyMap[ $fac[ 'email' ] ] = $fac;
    $specialization[ $fac[ 'specialization' ] ] = 0;
}

echo "<h2>Add a new faculty or update existing faculty </h3>";

$facultyEmails = array_keys( $facultyMap );
$specialization = array_keys( $specialization );

echo printInfo( 'To update a faculty, first search him/her.' );

?>

<script type="text/javascript" charset="utf-8">
$( function() {
    var emails = <?php echo json_encode( $facultyEmails ) ?>;
    var specialization = <?php echo json_encode($specialization ) ?>;
    $( "#faculty" ).autocomplete( { source : emails }); 
    $( "#faculty_specialization" ).autocomplete( { source : specialization }); 
    $( "#faculty" ).attr( "placeholder", "valid email id" );
    $( "#faculty_specialization" ).attr( "placeholder", "Specialization" );
});
</script>

<?php

echo '<form method="post" action="">
    Email of facutly <input id="faculty" name="faculty_email">
    <button type="submit" name="response" value="search">Search to update</button>
    </form>';

if( $_POST && array_key_exists( 'response', $_POST ) )
{
    $faculty = getTableEntry( 'faculty', 'email'
                    , array( 'email' => $_POST['faculty_email'] ) 
                );
    if( $faculty )
    {
        $default = array_merge( $default, $faculty );
        $action = 'Update';
    }
}

echo '<br/><br/>';

$default[ 'modified_on' ] = dbDateTime( 'now' );

echo '<form method="post" action="'.site_url('admin/faculty_task'). '">';
echo dbTableToHTMLTable( 'faculty'
    , $default
    , array( 'email', 'first_name', 'middle_name', 'last_name'
    , 'status', 'specialization', 'affiliation', 'url', 'institute' ), $action
);

// If we are updating, do give an delete button.
if( $action == 'submit' )
    echo '<button type="submit" name="response" value="delete">' . 
            $symbDelete . '</button>';

echo "</form>";

echo goBackToPageLink("admin/home");

echo '<h2>List of active faculty</h2>';


$hide = 'created_on,modified_on,status';
$faculty = getTableEntries( 'faculty', 'first_name,affiliation', "status='ACTIVE'");

$table = '<table class="info">';
$table .= arrayHeaderRow( $faculty[0], 'row', $hide );
foreach( $faculty as $fac )
    $table .= arrayToRowHTML( $fac, 'row', $hide );
$table .= '</table>';

echo $table;
echo goBackToPageLink("admin/home");

?>
