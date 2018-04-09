<?php

include_once 'header.php';
include_once 'tohtml.php';
include_once 'methods.php';
include_once 'database.php';

$queryID = $_GET[ 'id' ];
if( strlen($queryID) < 1 )
{
    echo printInfo( "Empty query ID" );
    exit;
}

$qu = getTableEntry( 'queries', 'id', array( 'id' => $queryID ) );
if( ! $qu )
{
    echo printWarning( "Invalid query." );
    exit;
}

if( $qu[ 'status' ] == 'EXECUTED' )
{
    echo printInfo( "This URL has already been used." );
    exit;
}

// Otherwise here we go.
$login = $qu[ 'who_can_execute' ];

// Ask for user password.
echo "Password please: ";
echo '<form action="execute_submit.php" method="post" accept-charset="utf-8">';
echo '<input type="password" name="password" id="" placeholder="password" />';
echo '<input type="hidden" name="login" value="' . $login . '" />';
echo '<input type="hidden" name="id" value="' . $queryID . '" />';
echo '<button type="" name="response" value="Execute" >Authenticate</button>';
echo '</form>';

?>
