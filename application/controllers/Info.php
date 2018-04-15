<?php

class Info extends CI_Controller 
{
    public function __construct( )
    {
        parent::__construct();
        $this->load->view( 'header' );
    }

    public function aws( )
    {
        $this->load->view('aws' );
    }

    public function events( )
    {
        $this->load->view('events' );
    }
}

?>
