<?php

include_once 'database.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAllOfTheseRoles( Array( 'ADMIN' ) );

echo userHTML( );

$action = 'add';

$faculty = getFaculty( );
$facultyMap = array( );
$default = array( );
$specialization = array( );

foreach( $faculty as $fac )
{
    $facultyMap[ $fac[ 'email' ] ] = $fac;
    $specialization[ $fac[ 'specialization' ] ] = 0;
}

echo "<h2>Add a new faculty or update existing faculty </h3>";

$facultyEmails = array_keys( $facultyMap );
$specialization = array_keys( $specialization );

echo printInfo( '
    If email is not found in database, you may add a faculty, otherwise
    you can update an existing faculty. You can also add instructor of given 
    course.
    ' );

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
    <button type="submit" name="response" value="search">Search</button>
    </form>';

if( $_POST && array_key_exists( 'response', $_POST ) )
{
    $faculty = getTableEntry( 'faculty', 'email'
                    , array( 'email' => $_POST['faculty_email'] ) 
                );
    if( $faculty )
    {
        $default = array_merge( $default, $faculty );
        $action = 'submit';
    }
}

echo '<br/><br/>';

$default[ 'modified_on' ] = dbDateTime( 'now' );

echo '<form method="post" action="admin_manages_faculty_submit.php">';
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

echo goBackToPageLink( "admin.php", "Go back" );

echo '<h2>List of active faculty</h2>';


$hide = 'created_on,modified_on,status';
$faculty = getTableEntries( 'faculty', 'first_name,affiliation', "status='ACTIVE'");

$table = '<table class="info">';
$table .= arrayHeaderRow( $faculty[0], 'row', $hide );
foreach( $faculty as $fac )
    $table .= arrayToRowHTML( $fac, 'row', $hide );
$table .= '</table>';
echo $table;

?>
