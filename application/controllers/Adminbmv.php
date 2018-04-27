<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';


class Adminbmv extends CI_Controller
{

    function index()
    {
        $this->home();
    }

    // Show user home.
    public function home()
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'bookmyvenue_admin' );
    }

}

?>
