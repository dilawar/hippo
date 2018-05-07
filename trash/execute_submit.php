<?php

include_once 'header.php';
include_once 'tohtml.php';
include_once 'methods.php';
include_once 'database.php';

if( $_POST[ 'response' ] == 'Execute' )
{
    $login = $_POST[ 'login' ];
    $pass = $_POST[ 'password' ];
    $id = $_POST[ 'id' ];
    $auth = authenticate( $login, $pass );
    if( ! $auth )
    {
        echo "Authentication failed. Try again.";
        goToPage( "execute.php?id=$id", 2 );
        exit;
    }

    $query = getTableEntry( 'queries', 'id', $_POST );

    $res = executeURlQueries( $query['query'] );
    if( $res )
    {
        $_POST[ 'status' ] = 'EXECUTED';
        $res = updateTable( 'queries', 'id', 'status', $_POST );
        if( $res )
            echo printInfo( "Success! " );
    }
}

echo closePage( );



?>
