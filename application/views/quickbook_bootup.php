<?php

include_once 'database.php';
include_once 'methods.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( "USER" ) );

echo userHTML( );

$roundedTimeNow = round( time( ) / (15 * 60) ) * (15 * 60 );

/* Ask user what kind of event she wants to book */
echo '<form method="post" action="#">';
echo 'I want to book for ';
echo arrayToSelectList(
    'bmvclass',
    getChoicesFromGlobalArray( $dbChoices, 'bookmyvenue.class'  ) 
    );
echo '<br>';
echo ' <button class="submit" name="class" value="continue">Continue ...</button> ';
echo '</form>';

if( __get__( $_POST, 'bmvclass', '' ) )
{
    $class = $_POST[ 'bmvclass' ];

    echo "Booking for $class";
}
