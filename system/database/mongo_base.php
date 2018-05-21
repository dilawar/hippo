<?php

require_once BASEPATH.'autoload.php';

function connect_mongo_db( $dbname )
{
    $dbserver = getConfigValue( 'MONGO_DB_SERVER' );
    $dbdir = getConfigValue( 'MONGO_DB_DIR' );

    $client = new MongoDB\Driver\Manager("mongodb://$dbserver:27017");
    // var_dump( $client );
}

?>
