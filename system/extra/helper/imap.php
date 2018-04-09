<?php

function authenticateUsingIMAP( $ldap, $pass )
{
    /* continue */
    $conn = imap_open( 
        "{imap.ncbs.res.in:993/ssl/readonly}INBOX"
        , $ldap, $pass, OP_HALFOPEN
    );
    if( ! $conn )
        $conn = imap_open( 
            "{mail.instem.res.in:993/ssl/readonly}INBOX"
            , $ldap, $pass, OP_HALFOPEN 
        );

    return $conn;
}

?>
