<?php

require_once BASEPATH . 'autoload.php';

class Info extends CI_Controller
{
    // NOTE: Checking for permission is in pre-hook.

    public function loadview($view, $data = [])
    {
        $data['controller'] = 'info';
        $this->template->set('header', 'header.php');
        $this->template->load($view, $data);
    }

    // no need for authentication (see HippoHooks.php)
    public function photography_club()
    {
        // Return a random image.
        $filename = random_jpeg('temp/_backgrounds/');
        return $this->servepath($filename);
    }

    // no need for authentication (see HippoHooks.php)
    public function photographyclub_image($name) {
        $filename = getUploadDir() . '/' . $name;
        return $this->servepath($filename);
    }

    public function servepath($filename)
    {
        // Return a random image.
        if (file_exists($filename)) {
            $mime = mime_content_type($filename); //<-- detect file type
            header('Content-Length: ' . filesize($filename)); //<-- sends filesize header
            header("Content-Type: $mime"); //<-- send mime-type header
            header('Content-Disposition: inline; filename="' . $filename . '";'); //<-- sends filename header
            readfile($filename); //<--reads and outputs the file onto the output buffer
            die(); //<--cleanup
            exit; //and exit
        }
    }


    public function courses()
    {
        $year = __get__($_GET, 'year', getCurrentYear());
        $sem = __get__($_GET, 'semester', getCurrentSemester());
        $runningCourses = getSemesterCourses($year, $sem);
        $data = ['cRunningCourses' => $runningCourses, 'cSemester' => $sem, 'cYear' => $year];
        $this->loadview('courses', $data);
    }

    public function aws($arg = '')
    {
        if ('search' === $arg) {
            $this->template->set('header', 'header.php');
            $query = strtolower(__get__($_POST, 'query', ''));

            if (!$query) {
                $res = [];
            } else {
                $res = $this->db->select('*')
                            ->like('LOWER(abstract)', $query)
                            ->order_by('date', 'DESC')
                            ->get('annual_work_seminars')->result_array();
            }
            $data['awses'] = $res;
            $this->template->load('user_aws_search', $data);
        } elseif ('roster' == $arg) {
            $this->template->set('header', 'header.php');
            $data['upcomingAWS'] = $this->db->get('upcoming_aws')->result_array();
            $data['speakers'] = $this->db->get_where('logins', ['eligible_for_aws' => 'YES'])->result_array();
            $this->template->load('aws_roster', $data);
        } else {
            $this->template->set('header', 'header.php');

            $date = __get__($_POST, 'date', dbDate('this monday'));
            $data['date'] = $date;

            // Get aws.
            $awses = $this->db->get_where('annual_work_seminars', ['date' => $date])->result_array();
            $upcoming = $this->db->get_where('upcoming_aws', ['date' => $date])->result_array();
            $awses = array_merge($awses, $upcoming);
            $data['awses'] = $awses;
            $this->template->load('aws', $data);
        }
    }

    public function talks()
    {
        $this->template->set('header', 'header.php');
        $this->template->load('talks');
    }

    public function booking($date = null)
    {
        if (!$date) {
            $date = __get__($_POST, 'date', dbDate('today'));
        }

        $this->template->set('header', 'header.php');

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
        $data['requests'] = $this->db->get_where('bookmyvenue_requests', ['date' => $date, 'status' => 'PENDING'])->result_array();

        // and courses.
        $slots = getTableEntries('slots');
        $day = date('D', strtotime($date));
        $slots = getSlotsAtThisDay($day, $slots);
        $data['slots'] = $slots;

        $this->template->load('allevents', $data);
    }

    public function statistics()
    {
        $this->template->set('header', 'header.php');
        $this->template->load('statistics');
    }

    public function publications()
    {
        $this->template->set('header', 'header.php');
        $this->template->load('publications');
    }

    public function rss()
    {
        redirect('Feed/rss');
    }

    public function jc(string $date = '')
    {
        if (!$date) {
            $date = __get__($_POST, 'date', dbDate('today'));
        }

        $jcs = $this->db->get_where('jc_presentations', ['date >=' => $date,  'status' => 'VALID'])->result_array();
        $data['jcs'] = $jcs;
        $data['date'] = $date;

        $this->loadview('jc.php', $data);
    }

    public function preprints()
    {
        $this->loadview('preprints.php');
    }

    public function phpinfo()
    {
        echo phpinfo();
    }
}
