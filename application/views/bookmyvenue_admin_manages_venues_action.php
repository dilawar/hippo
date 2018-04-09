<?php

include_once "header.php";
include_once "methods.php";
include_once "database.php";
include_once "tohtml.php";
include_once "check_access_permissions.php";

mustHaveAnyOfTheseRoles( array( 'BOOKMYVENUE_ADMIN' ) );

$response = __get__( $_POST, 'response', '' );
if( $response == 'update' )
{
    $res = updateTable( 
            'venues'
            , 'id'
            , 'name,institute,building_name,floor,location,type,strength,' 
                . 'distance_from_ncbs,has_projector,' 
                . 'suitable_for_conference,has_skype'
            , $_POST
        );
    if( $res )
    {
        echo printInfo( "Venue " . $_POST[ 'id' ] . ' is updated successful' );
        goBack( 'bookmyvenue_admin_manages_venues.php', 1 );
        exit;
    }
    else
        echo printWarning( 'Failed to update venue ' . $_POST[ 'id ' ] );
}
else if( $response == 'add new' ) 
{
    if( strlen( $_POST[ 'id' ] ) < 2  )
    {
        echo printInfo( "The venue id is too short to be legal." );
    }
    else
    {
        $res = insertIntoTable( 
                'venues'
                , 'id,name,institute,building_name,floor,location,type,strength,' 
                    . 'distance_from_ncbs,has_projector,' 
                    . 'suitable_for_conference,has_skype'
                , $_POST
            );

        if( $res )
        {
            echo printInfo( "Venue " . $_POST[ 'id' ] . ' is successfully added.' );
            goBack( 'bookmyvenue_admin_manages_venues.php', 1 );
            exit;
        }
        else
            echo printWarning( 'Failed to added venue ' . $_POST[ 'id ' ] );
    }
}
else if( $response == 'delete' ) 
{
    echo printInfo( "Deleting venue " );
    $res = deleteFromTable( 'venues' , 'id' , $_POST);
    if( $res )
    {
        echo printInfo( "Venue " . $_POST[ 'id' ] . ' is successfully deleted.' );
        goBack( 'bookmyvenue_admin_manages_venues.php', 1 );
        exit;
    }
    else
        echo printWarning( 'Failed to added venue ' . $_POST[ 'id ' ] );
}
else if( $response == 'DO_NOTHING' ) 
{
    echo printInfo( "User said DO NOTHING. So going back!" );
    goBack( 'bookmyvenue_admin_manages_venues.php', 1 );
    exit;
}
else
{
    echo printWarning( 'Unknown command from user ' . $_POST[ 'response' ] );
}

echo goBackToPageLink( 'bookmyvenue_admin_manages_venues.php', 'Go back' );

?>
