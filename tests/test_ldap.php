<?php

require_once BASEPATH .'extra/ldap.php';

function test_ldap()
{
    var_dump( getUserInfoFromLdap( 'dilawars' ) );
    var_dump( getUserInfoFromLdap( 'bhalla' ) );
    var_dump( getUserInfoFromLdap( 'cpani' ) );
    var_dump( getUserInfoFromLdap( 'ashok' ) );
    var_dump( getUserInfoFromLdap( 'colinj' ) );
    var_dump( getUserInfoFromLdap( 'rashi' ) );
    var_dump( getUserInfoFromLdap( 'jayaprakashp' ) );
    var_dump( getUserInfoFromLdap( 'hrishikeshn' ) );
    var_dump( getUserInfoFromLdap( 'dlakhe' ) );
    var_dump( getUserInfoFromLdap( 'enakshi' ) );
    var_dump( getUserInfoFromLdap( 'dpragati' ) );
    var_dump( getUserInfoFromLdap( 'praghu' ) );
    var_dump( getUserInfoFromLdap( 'anirudhcs' ) );
}
