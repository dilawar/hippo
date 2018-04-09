<?php

function __log__( $message, $type = "INFO" )
{
    if( array_key_exists( 'log_file', $_SESSION['conf']['global'] ) )
        $logfile = $_SESSION['conf']['global']['log_file'];
    else
        $logfile = '__jinawar__.log';

    $timestamp = date(DATE_RFC2822);
    $msg = '[' . $type . ']' . ' : ' . $timestamp . ' ' .  $message . PHP_EOL;
    file_put_contents( $logfile, $msg, FILE_APPEND );
}

?>
