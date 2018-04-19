<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  There are traits AWS, Courses etc. which this class can use;
    * since multiple inherihence is not very straightforward in php.
 */
/* ----------------------------------------------------------------------------*/
class Admin extends CI_Controller
{
    function index()
    {
        $this->home();
    }

    // Show user home.
    public function home()
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin' );
    }

}

?>
