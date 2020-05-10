<?php

// Run the controller Test
require_once BASEPATH . 'extra/ldap.php';

function test_ldap()
{
    var_dump(getUserInfoFromLdap('raunakdutta'));
    var_dump(getUserInfoFromLdap('madan'));
    var_dump(getUserInfoFromLdap('spatil@instem.res.in'));
}
