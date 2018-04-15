<?php

include_once __DIR__. '/header.php';
include_once BASEPATH. 'extra/tohtml.php' ;
include_once BASEPATH. 'extra/methods.php' ;
include_once BASEPATH. 'calendar/calendar.php' ;

function loginForm()
{
    /* Check if ldap server is alive. */
    $table = "";
    $table .= '<form action="' . site_url('/welcome/login').'" method="post" >';
    $table .= '<table class="login_main">';
    $table .= '<tr><td><input type="text" name="username" id="username" 
        placeholder="NCBS/Instem Username" /> </td></tr>';
    $table .= '<tr><td> <input type="password"  name="pass" 
        id="pass" placeholder="Password" > </td></tr>';
    $table .= '<tr><td> 
            <input style="float:right" type="submit" name="response" value="Login" /> 
        </td></tr>';
    $table .= '</table>';
    $table .= '</form>';
    return $table;
}


$_SESSION['user'] = 'anonymous';

// Now create a login form.
echo loginForm();

// Show background image only on index.php page.
$thisPage = basename( $_SERVER[ 'PHP_SELF' ] );
if( strpos( $thisPage, 'index.php' ) !== false )
{

    // Select one image from directory _backgrounds.
    $background = random_jpeg( "./_backgrounds" );
    if( $background )
    {
        echo "<body style=\"background-image:url($background);
            filter:alpha(Opactity=30);opacity=0.3; width:800px; \">";
    }
}

require_once __DIR__ . '/footer.php';

?>
