<?php

// Run the controller Test
require_once BASEPATH .'extra/ldap.php';

function test_ldap()
{
    var_dump( getUserInfoFromLdap( 'raunakdutta' ) );
    var_dump( getUserInfoFromLdap( 'shreyal' ) );
}
