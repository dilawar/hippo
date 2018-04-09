<?php 
include_once( "header.php" );
include_once( "methods.php" );
include_once( "sqlite.php" );

if( strcmp($_POST['response'], 'Go back') == 0  )
{
    goToPage( "index.php", 0 );
    exit(0);
}

var_dump( $_POST );

$conn = connectDB( );
$stmt = $conn->prepare( 'REPLACE INTO venues (name,location,strength,
    hasConference,hasProjector) values ( :name, :location, :strength
        , :hasConference, :hasProjector )' );

$stmt->bindValue( ":name", $_POST['name']);
$stmt->bindValue( ":location", $_POST['location']);
$stmt->bindValue( ':strength', $_POST['strength']);
$stmt->bindValue( ':hasConference', $_POST['hasConference']);
$stmt->bindValue( ':hasProjector', $_POST['hasProjector']);
$status = $stmt->execute( );

$conn->close();

$status = true;
if( $status )
{
    echo printInfo( "Successfully updated the table" );
    goToPage( "manage.php", 1 );
}
else
{
    echo printInfo( "Could not updated the table" );
    goToPage( "manage.php", 2 );
}

?>
