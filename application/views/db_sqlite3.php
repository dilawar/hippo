<?php

/* Local database to manage hippo */

function connect( )
{
    $db = new PDO( 'sqlite:' .__DIR__ . '/hippo.sqlite' );
    return $db;

}

function close( &$db )
{
    $db = NULL;
}

function init_sqlite( )
{
    $db = connect( );
    $db->exec( 
        "CREATE TABLE backgrounds IF NOT EXISTS  (
            filepath VARCHAR(100) NOT NULL PRIMARY KEY
            , caption VARCHAR(200)
            , owner VARCHAR(200)
            , downloaded_on DATETIME 
        )" 
    );
    close( $db );
}



?>
