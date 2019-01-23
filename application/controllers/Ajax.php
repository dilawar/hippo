<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  There are traits AWS, Courses etc. which this class can use;
    * since multiple inherihence is not very straightforward in php.
 */
/* ----------------------------------------------------------------------------*/
class Ajax extends CI_Controller
{

    public function index() 
    {
    }

    public function user_data_submit( )
    {
        $data = [ 'username' => $this->input->post('name') ];
        echo json_encode( $data );
    }
}

?>
