<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once BASEPATH . 'autoload.php';

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
        $this->load->view('ajax_post_view');
    }

    public function user_data_submit(): bool
    {
        $fname = $this->input->post('function');
        if ('isVenueAvailable' == $fname) {
            $venue = $this->input->post('venue');
            $date = $this->input->post('date');
            $startT = $this->input->post('start_time');
            $endT = $this->input->post('end_time');
            $es = getEventsOnThisVenueBetweenTime($venue, $date, $startT, $endT);
            $rs = getRequestsOnThisVenueBetweenTime($venue, $date, $startT, $endT);
            $nEvents = (count($rs) + count($es));
            echo $nEvents;

            return true;
        }
    }
}
