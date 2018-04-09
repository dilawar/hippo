<?php

include_once 'database.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAllOfTheseRoles( Array( 'ADMIN' ) );

echo userHTML( );

echo '<h3>Add a holiday or non-working day</h3>';

$default = array( );
echo '<form method="post" action="">';
echo dbTableToHTMLTable( 'holidays'
        , $default
        , 'date,description,schedule_talk_or_aws'
        , 'Add'
    );
echo '</form>';

// Add or delete an entry.
if( isset( $_POST[ 'response' ] ) )
{
    if( $_POST[ 'response' ] == 'Add' )
    {
        if( $_POST['date'] && $_POST['description'] )
        {
            $res = insertIntoTable( "holidays"
                , "date,description,schedule_talk_or_aws"
                , $_POST );
            if( $res )
            {
                echo printInfo( "Added holiday successfully" );
                goToPage( "admin_manages_holidays.php", 1);
                exit;
            }
            else
                echo minionEmbarrassed( "Could not add holiday to database" );
        }
        else
        {
            echo printWarning( 
                "Either 'date' or 'description' of holiday was incomplete" 
            );
            goToPage( "admin_manages_holidays.php", 1);
            exit;
        }
    }
    elseif( $_POST[ 'response' ] == 'Delete' )
    {
        $res = deleteFromTable( 'holidays', 'date,description', $_POST );
        if( $res )
        {
            echo printInfo( "Successfully deleted entry from holiday list" );
            goToPage( "admin_manages_holidays.php", 1);
            exit;
        }
        else
        {
            echo minionEmbarrassed( 
                "Could not delete holiday from the list"
            );
        }
    }
}

echo '<h3>List of holidays in my database</h3>';

$holidays = getHolidays( );
foreach( $holidays as $index => $holiday )
{
    echo '<form method="post" action="">';
    echo '<small><table>';
    echo '<tr>';
    echo '<td>' . ($index + 1) . '</td><td>' . arrayToTableHTML( $holiday, 'info' ) . '</td>';
    echo '<td> 
        <input type="hidden" name="date" value="' . $holiday['date'] . '" >
        <input type="hidden" name="description" value="' . $holiday['description'] . '"/>
        <button name="response" value="Delete">Delete</button> 
        </td>';
    echo '</tr>';
    echo '</table></small>';
    echo '</form>';

}

echo goBackToPageLink( "admin.php", "Go back" );

?>
