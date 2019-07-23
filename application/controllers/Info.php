<?php

require_once BASEPATH . 'autoload.php';

class Info extends CI_Controller 
{
    // NOTE: Checking for permission is in pre-hook.

    public function loadview( $view, $data = array())
    {
        $data['controller'] = 'info';
        $this->template->set('header', 'header.php');
        $this->template->load($view, $data);
    }

    public function aws($arg='')
    {
        if( $arg === 'search' )
        {
            $this->template->set( 'header', 'header.php' );
            $query = strtolower(__get__($_POST, 'query', ''));

            if( ! $query )
                $res = [];
            else
            {
                $res = $this->db->select('*')
                            ->like('LOWER(abstract)', $query)
                            ->order_by("date", "DESC")
                            ->get('annual_work_seminars')->result_array();
            }
            $data['awses'] = $res;
            $this->template->load('user_aws_search', $data);
        }
        else if( $arg == 'roster' )
        {
            $this->template->set( 'header', 'header.php' );
            $data['upcomingAWS'] = $this->db->get('upcoming_aws')->result_array();
            $data['speakers'] = $this->db->get_where('logins', ['eligible_for_aws'=>'YES'])->result_array();
            $this->template->load( 'aws_roster', $data);

        }
        else
        {
            $this->template->set('header', 'header.php' );

            $date = __get__($_POST, 'date', 'this monday');
            $data['date'] = $date;

            // Get aws.
            $awses = $this->db->get_where('annual_work_seminars', ['date'=>$date])->result_array();
            $upcoming = $this->db->get_where('upcoming_aws', ['date'=>$date])->result_array();
            $awses = array_merge( $awses, $upcoming );
            $data['awses'] = $awses;
            $this->template->load('aws', $data);
        }
    }

    public function talks( )
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('talks' );
    }

    public function booking($date=null)
    {
        if(! $date )
            $date = __get__($_POST, 'date', dbDate('today'));

        $this->template->set('header', 'header.php' );

        // Valid events.
        $this->db->where('status', 'VALID')
             ->where('date', dbDate($date));
        $this->db->select();
        $data['events'] = $this->db->get('events')->result_array();

        // Cancelled events.
        $this->db->where('status', 'CANCELLED')
             ->where('date', dbDate($date));
        $this->db->select();
        $data['cancelled'] = $this->db->get('events')->result_array();

        // Venues.
        $data['venues'] = $this->db->get('venues')->result_array();
        $data['date'] = $date;

        // Pending requests as well.
        $data['requests'] = $this->db->get_where( 'bookmyvenue_requests'
            , ['date'=>$date, 'status'=>'PENDING'])->result_array();

        // and courses.
        $slots = getTableEntries( 'slots' );
        $day = date( 'D', strtotime( $date));
        $slots = getSlotsAtThisDay( $day, $slots );
        $data['slots'] = $slots;

        $this->template->load('allevents',$data);
    }

    public function statistics( )
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('statistics' );
    }

    public function courses( )
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('courses' );
    }

    public function publications()
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('publications' );
    }

    public function rss( )
    {
        redirect( 'Feed/rss' );
    }

    public function jc(string $date='')
    {
        if( ! $date )
            $date = __get__($_POST, 'date', dbDate('today'));

        $jcs = $this->db->get_where('jc_presentations'
            , ["date >="=>$date,  'status'=>'VALID'])->result_array();
        $data['jcs'] = $jcs;
        $data['date'] = $date;

        $this->loadview( 'jc.php', $data );
    }

    public function preprints()
    {
        $this->loadview( 'preprints.php');
    }

    public function phpinfo() 
    {
        echo phpinfo();
    }

}

?>
