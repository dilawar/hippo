<?php

require_once FCPATH.'./tests/test_ldap.php';

class Test extends CI_Controller 
{

    public function ldap()
    {
        test_ldap();
    }

}

?>
