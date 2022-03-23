<?php

//
// This function must not write to stdout. 
//
function __log__( $message, $type = "INFO" )
{
    $timestamp = date(DATE_RFC2822);
    $logfile = $_SESSION['conf']['global']['log_file'] ?? '/var/log/hippo.log';
    $msg = $timestamp . ': ' . $type . ': ' . $message . PHP_EOL;
    if(file_exists($logfile))
        file_put_contents( $logfile, $msg, FILE_APPEND );
}

function _log_( $message )
{
    echo($messaege);
    $logfile = $_SESSION['conf']['global']['log_file'] ?? '__hippo__.log';
    error_log($message, 3, $logfile);
}


?>
