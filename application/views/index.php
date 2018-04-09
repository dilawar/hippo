<?php

include_once __DIR__. '/header.php';
include_once BASEPATH. 'extra/tohtml.php' ;
include_once BASEPATH. 'extra/methods.php' ;
include_once BASEPATH. 'calendar/calendar.php' ;

// If user is already authenticated, redirect him to user.php
// NOTE: DO NOT put this block before loading configuration files.
if( array_key_exists( 'AUTHENTICATED', $_SESSION) && $_SESSION[ 'AUTHENTICATED' ] )
{
    if( $_SESSION[ 'user' ] != 'anonymous' )
    {
        echo printInfo( "Already logged-in" );
        goToPage( 'user.php', 0 );
        exit;
    }
}

$_SESSION['user'] = 'anonymous';
$_SESSION[ 'timezone' ] = 'Asia/Kolkata';

// Now create a login form.
echo "<table class=\"index\">";
echo '</tr>';
echo loginForm();
echo '</tr>';
echo "</table>";

// Show background image only on index.php page.
$thisPage = basename( $_SERVER[ 'PHP_SELF' ] );
if( strpos( $thisPage, 'index.php' ) !== false )
{

    // Select one image from directory _backgrounds.
    $background = random_jpeg( "./_backgrounds" );
    if( $background )
    {
        echo "<body style=\" background-image:url($background);
        filter:alpha(Opactity=30);opacity=0.3;
        width:800px;
        \">";
    }
}

require_once 'footer.php';

?>
