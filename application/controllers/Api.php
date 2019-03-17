<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Api extends CI_Controller
{

    private function send_data_helper(array $data)
    {
        $json = json_encode($data);
        $this->output->set_content_type('application/json' );
        $this->output->set_output($json);
    }

    public function get_without_auth(string $what)
    {
        $this->send_data($what);
    }

    private function send_data(array $events, string $status='ok')
    {
        $this->send_data_helper(['status'=>$status, 'data'=>$events]);
    }

    // Helper function for process() function.
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
        $this->send_data($events, $status);
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
        if(count($args)==0)
            $args[] = "latest";
        $this->process_events_requests($args);
    }

    // Helper function for aws() function.
    private function process_aws_requests($args)
    {
        $results = [];
        $status = 'ok';
        if($args[0] === 'date')
        {
            $from = dbDate($args[1]);
            $to = dbDate(__get__($args, 2, strtotime('+14 day', strtotime($from))));
            $results = getTableEntries( 'annual_work_seminars', 'date'
                , "date >= '$from' AND date < '$to'"
            );
        }
        else if($args[0] === 'latest')
        {
            $numEvents = __get__($args, 1, 6);
            $from = dbDate('today');
            // echo " x $from $numEvents ";
            $results = getTableEntries('upcoming_aws', 'date'
                , "date >= '$from'", '*', $numEvents
            );
        }
        else
            $status = 'warning';
        $this->send_data($results, $status);
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Return AWS based on GET query.
        * Examples of endpoints:
        *     - /aws/latest/6
        *     - /aws/date/2019-03-01               // Find AWS in this week.
        *     - /aws/date/2019-03-01/2019-04-01    // Find AWS between these  dates.
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function aws()
    {
        $args = func_get_args();
        if(count($args)==0)
            $args = ['latest'];
        $this->process_aws_requests($args);
    }



}

?>
