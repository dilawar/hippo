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

    private function send_events(string $from, string $to)
    {
        $events = getTableEntries( 'events', 'date'
            , "status='VALID' AND date >= '$from' AND date < '$to'"
            );
        $this->send_data($events);
    }

    private function process_get_requests($args)
    {
        $what = $args[0];
        if( $what == 'events' )
        {
            $from = __get__($args, 1, 'today');
            $to = __get__($args, 2, strtotime("+14 days", strtotime($from)));
            $from = dbDate($from);
            $to = dbDate($to);
            $this->send_events($from, $to);
        }
    }

    public function get()
    {
        $args = func_get_args();
        if(count($args) == 0)
        {
            $args['status' ] = 'error';
            $args['msg'] = "You requested nothing.";
            $this->send_data($args);
        }
        else
            $this->process_get_requests($args);
    }

    public function post()
    {
    }
}

?>
