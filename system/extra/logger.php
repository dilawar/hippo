<?php

function __log__( $message, $type = "INFO" )
{
    if(! isset($_SESSION)) {
        return;
    }

    if( array_key_exists( 'log_file', $_SESSION['conf']['global'] ) )
        $logfile = $_SESSION['conf']['global']['log_file'];
    else
        $logfile = '/var/log/hippo.log';

    if(file_exists($logfile)) {
        $timestamp = date(DATE_RFC2822);
        $msg = '[' . $type . ']' . ' : ' . $timestamp . ' ' .  $message . PHP_EOL;
        file_put_contents( $logfile, $msg, FILE_APPEND );
    } else {
        echo("Error: $message");
    }
}

function _log_( $message )
{
    echo($messaege);

    if( array_key_exists( 'log_file', $_SESSION['conf']['global'] ) )
        $logfile = $_SESSION['conf']['global']['log_file'];
    else
        $logfile = '__jinawar__.log';

    $logfile = getConf()['global']['log_file'];
    error_log($message, 3, $logfile);
}


?>
