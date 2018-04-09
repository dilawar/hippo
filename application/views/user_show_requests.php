<?php

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );
include_once( "tohtml.php" );

echo userHTML( );
echo "<br>";

?>

<?php

$requests = getRequestOfUser( $_SESSION['user'], $status = 'PENDING' );

if( count( $requests ) < 1 )
    echo alertUser( "No pending request found" );
else
    echo alertUser( "You have following pending requests" );

foreach( $requests as $request )
{
    $tobefiltered = Array( 
        'gid', 'created_by', 'rid', 'modified_by', 'timestamp'
        , 'url' , 'status', 'external_id'
    );
    $gid = $request['gid'];

    echo "<div style=\"font-size:small;\">";
    echo "<table class=\"info\" >";
    echo "<tr>";
    echo "<td>" . arrayToTableHTML( $request, "requests", NULL, $tobefiltered );
    echo '<form method="post" action="user_show_requests_edit.php">';
    echo "</td></tr><tr>";
    echo "</td><td><button name=\"response\" title=\"Cancel this request\"
        onclick=\"AreYouSure( this )\" > $symbCancel </button>";
    echo "<td><button name=\"response\" title=\"Edit this request\"
        value=\"edit\"> $symbEdit </button>";
    echo "</td></tr>";
    echo "</table>";
    echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
    echo '</form>';
    echo '</div>';
}

echo goBackToPageLink( "user.php", "Go back" );

?>
