<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Api extends CI_Controller
{

    private function send_data( $data )
    {
        $json = json_encode($data);
        $this->output->set_content_type('application/json' );
        $this->output->set_output($json);
    }

    public function get_without_auth($what)
    {
        $this->send_data($what);
    }

    public function get()
    {
        $args = func_get_args();
        if(count($args) == 0)
            $this->send_data(["Error" => "You requested nothing."]);
        else
            $this->send_data(["Hello"]);
    }

    public function post()
    {
    }
}

?>
