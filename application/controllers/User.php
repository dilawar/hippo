<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once BASEPATH. "extra/methods.php" ;
require_once BASEPATH. "extra/ldap.php" ;
require_once BASEPATH. "database.php" ;
require_once BASEPATH. "extra/helper/imap.php";

class User extends CI_Controller {

    public function home()
    {
        echo "User home";
        $this->load->view('user');
    }

}

?>
