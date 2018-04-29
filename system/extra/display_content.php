<?php

function printErrorSevere($msg, $flash = false )
{
    $err = '<div class="alert alert-danger">'.$msg.'</div>';

    if($flash)
        $_SESSION['warning'] = __get__( $_SESSION, 'warning', '') . "<p>$msg </p>";

    error_log( $msg );
    return $err;
}


function printWarning($msg, $flash = true)
{
    $warn ="<div class=\"alert alert-warning\">
        <i class='fa fa-exclamation-circle fa-2x'></i> ".$msg."</div>";
    error_log( $msg );
    if( $flash )
        $_SESSION['warning'] = $msg;
    return $warn;
}

function flashMessage( $msg, $category = 'success' )
{
    $_SESSION[ $category ] = $msg;
}

function printInfo( $msg )
{
    error_log( $msg );
    $info ="<div class=\"alert alert-info\">".$msg."</div>";
    return $info;
}

function msg_fade_out( $msg , $class = "info" )
{
    $info ='<div id="fadein"><p class="' . $class . "\">$msg </p></div>";
    return $info;
}

function alertUser( $msg, $flash = true )
{
    $info ="<div class=\"alert alert-warning\"> <i class=\"fa fa-exclamation-circle fa-1x\"></i> " . $msg . "</div>";
    if($flash)
        $_SESSION['warning'] = __get__( $_SESSION, 'warning', '') . "<p>$msg </p>";
    return $info;
}

function noteWithFAIcon( $msg, $fa )
{
    $icon = '<i class="fa ' . $fa . '"></i>';
    $info ="<div class=\"fa_note\"><p> $icon ".$msg."</p></div>";
    return $info;
}

function minionEmbarrassed( $msg, $info = '' )
{
    echo "<p class=\"embarassed\"> This is embarassing! <br>";
    echo " $msg <br> $info ";
    echo "I have logged this error!. ";
    error_log( "FAILED : " . $msg );
    echo "</p>";
}

function printNote( $msg )
{
    return "<p class='note'> $msg </p>";
}



?>
