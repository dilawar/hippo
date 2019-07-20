<?php

require_once FCPATH.'./tests/test_ldap.php';
require_once FCPATH.'./tests/test_methods.php';

class Test extends CI_Controller 
{

    public function ldap()
    {
        test_ldap();
    }

    public function methods()
    {
        test_methods();
    }

}

?>
