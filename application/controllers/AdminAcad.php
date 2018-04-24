<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

trait AdminAcad
{
    function index()
    {
        $this->home();
    }

    public function acad( )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin_acad.php' );
    }

}

?>
