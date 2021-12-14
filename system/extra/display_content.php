<?php

function printErrorSevere($msg, $append = false )
{
    if($append)
        $_SESSION['warning'] = __get__( $_SESSION, 'warning', '') . "<p>$msg </p>";
    else
        $_SESSION['warning'] =  "<p>$msg </p>";

    __log__($msg);
    return $msg;
}


function printWarning($msg, $append = true)
{
    if( ! $msg )
        $msg = '';
    if( $append )
        if(isset($_SESSION))
            $_SESSION['warning'] = __get__( $_SESSION, 'warning', '') . p($msg);
    __log__($msg);
    return $msg;
}

function flashMessage( $msg, $category = 'success' )
{
    // See template.php file how this is flash messages are managed.
    if(isset($_SESSION))
        $_SESSION[ $category ] = $msg;
    return $msg;
}

function printInfo( $msg )
{
    __log__( $msg );
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
    $info ="<div class=\"alert alert-warning\">" . $msg . "</div>";
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
    $r .= "</p>";
    return $r;
}

function printNote( $msg )
{
    return "<p class='note'> $msg </p>";
}



?>
