<?php

function printErrorSevere($msg, $append = false )
{
    if($append)
        $_SESSION['warning'] = __get__( $_SESSION, 'warning', '') . "<p>$msg </p>";
    else
        $_SESSION['warning'] =  "<p>$msg </p>";

    error_log( $msg );
    return $msg;
}


function printWarning($msg, $append = true)
{
    if( ! $msg )
        $msg = '';
    if( $append )
        if(isset($_SESSION))
            $_SESSION['warning'] = __get__( $_SESSION, 'warning', '') . p($msg);
    return $msg;
}

function flashMessage( $msg, $category = 'success' )
{
    if(isset($_SESSION))
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
        if(isset($_SESSION))
            $_SESSION['warning'] = __get__( $_SESSION, 'warning', '') . "<p>$msg </p>";
    return $info;
}

function noteWithFAIcon( $msg, $fa )
{
    $icon = '<i class="fa ' . $fa . '"></i>';
    // $info ="<div class=\"fa_note\"><p> $icon ".$msg."</p></div>";
    return $icon . $msg;
}

function minionEmbarrassed( string $msg, string $info = '' ) : string
{
    $r = "<p class=\"embarassed\"> This is embarassing! <br>";
    $r .= " $msg <br> $info ";
    $r .= "I have logged this error!. ";
    error_log( "FAILED : " . $r );
    $r .= "</p>";
    return $r;
}

function printNote( $msg )
{
    return "<p class='note'> $msg </p>";
}



?>
