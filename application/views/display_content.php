<?php

function printErrorSevere($msg)
{
    $err = "<font size=\"4\" color=\"blue\">".$msg."</font><br>";
    error_log( $msg );
    return $err;
}


function printWarning($msg)
{
    $warn ="<p class=\"warn\"><i class='fa fa-exclamation-circle fa-2x'></i> ".$msg."</p>";
    error_log( $msg );
    return $warn;
}


function printInfo( $msg )
{
    error_log( $msg );
    $info ="<p class=\"info\">".$msg."<br></p>";
    return $info;
}

function alertUser( $msg )
{
    $info ="<div class=\"alert_user\">
            <i class=\"fa fa-exclamation-circle fa-1x\"></i> " . $msg . "</div>";
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
