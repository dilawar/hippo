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

    private function send_events($events, $status='ok')
    {
        $this->send_data(['status'=>$status, 'data'=>$events]);
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  
        *
        * @Param $args
        *   Following type of get requests are supported.
        *     /date/2019-01-03                   // On this date.
        *     /date/2019-01-03/2019-01-11        // From one date to another.
        *     /latest                            // Return last 20s.
        *     /latest/100                        // Return last 100.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    private function process_events_requests($args)
    {
        $events = [];
        $status = 'ok';
        if( $args[0] === 'date')
        {
            $from = __get__($args, 1, 'today');
            $to = __get__($args, 2, strtotime("+1 day", strtotime($from)));
            $from = dbDate($from);
            $to = dbDate($to);
            $events = getTableEntries( 'events', 'date,start_time,end_time'
                , "status='VALID' AND date >= '$from' AND date < '$to'"
                , "class,title,description,date,venue,created_by,start_time,end_time,url"
                );
        }
        else if( $args[0] === 'latest')
        {
            $numEvents = __get__($args, 1, 20);

            // Maximum limit event when 'all' is given.
            if( $numEvents == 'all')
                $numEvents = 1000;

            $from = dbDate('today');
            $events = getTableEntries( 'events', 'date,start_time,end_time'
                , "status='VALID' AND date >= '$from'"
                , "class,title,description,date,venue,created_by,start_time,end_time,url"
                , min($numEvents, 1000)
                );
        }
        else
        {
            $status = 'error';
            $events['msg'] = "Unknow request: " . $args[0];
        }

        $this->send_events($events, $status);
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Return events based on GET query.
        * Examples of endpoints,
        *     - events/latest                       Latest 20 events.
        *     - events/latest/50             
        *     - events/date/2019-03-01              On this date.
        *     - events/date/2019-03-01/2019-04-01   From this date to this date.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function events()
    {
        $args = func_get_args();
        $this->process_events_requests($args);
    }

}

?>
