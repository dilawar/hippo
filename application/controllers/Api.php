<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__.'/ApiHelper.php';
require_once __DIR__.'/Adminservices.php';
require_once __DIR__.'/User.php';

require_once BASEPATH . '/extra/bmv.php';
require_once BASEPATH . '/extra/people.php';
require_once BASEPATH . '/extra/search.php';
require_once BASEPATH . '/extra/acad.php';
require_once BASEPATH . '/extra/talk.php';
include_once BASEPATH . '/extra/acad.php';
include_once BASEPATH . '/extra/services.php';
include_once BASEPATH . '/extra/me.php';

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Authenticate a given user with given key.
    *
    * @Param $apikey
    * @Param $user
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function authenticateAPI($apikey, $user='')
{
    $where = 'apikey';
    if($user)
        $where .= ",login";

    $res = getTableEntry( 'apikeys', $where, ['apikey'=>$apikey, 'login'=>$user]);
    if($res)
        return true;
    return false;

}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get a value from header.
    *
    * @Param $key This is the key to fetch.
    *
    * @Returns  The value of key if available; empty otherwise.
 */
/* ----------------------------------------------------------------------------*/
function getHeader($key)
{
    return __get__(getallheaders(), $key, '');
}

function getKey()
{
    return __get__( $_POST, 'HIPPO-API-KEY', getHeader('HIPPO-API-KEY'));
}

function getLogin()
{
    return __get__($_POST, 'login', getHeader('login'));
}


class Api extends CI_Controller
{

    // To enable CORS just for this API. DO NOT CHANGE THEM IN apache2.conf or 
    // httpd.conf file.
    public function __construct($config = 'rest')
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: cache-control,login,hippo-api-key,x-requested-with,Content-Type');
        header("Access-Control-Allow-Methods: GET,POST,OPTIONS,PUT,DELETE");
        parent::__construct();
    }

    private function send_data_helper(array $data)
    {
        $json = json_encode($data
            , JSON_ERROR_SYNTAX | JSON_NUMERIC_CHECK 
            | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES 
        );
        $this->output->set_content_type('application/json', 'utf-8');
        $this->output->set_output($json);
    }

    public function get_without_auth(string $what)
    {
        $this->send_data($what);
    }

    private function send_data(array $data, string $status='ok')
    {
        $this->send_data_helper(['status'=>$status, 'data'=>$data]);
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Status of Hippo API. 
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function status()
    {
        $this->send_data(["status"=>"alive"], "ok");
    }

    // Helper function for process() function.
    private function process_events_requests($args)
    {
        // all dates are unix timestamp.
        $events = [];
        $status = 'ok';
        if( $args[0] === 'date')
        {
            $from = intval(__get__($args, 1, strtotime('today')));
            $to = intval(__get__($args, 2, strtotime("+1 day", $from)));
            $from = dbDate($from);
            $to = dbDate($to);
            $events = getAllBookingsBetweenTheseDays( $from, $to );
        }
        else if( $args[0] === 'latest')
        {
            // We'll get twice as many events. Because we fetch requests as
            // well.
            $numEvents = intval(__get__($args, 1, 100))/2;
            $startFrom = intval(__get__($args, 2, 0));
            $events = getNumBookings($numEvents, $startFrom);
        }
        else if( $args[0] === 'class')
        {
            $this->send_data("ok", $dbChoices["events.class"]);
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
        * @Synopsis  Get various info.
        *    - /info/news/latest
        *    - /info/news
        *    - /info/venues/available/[all|venueid]
        *    - /info/bmv/bookingclasses
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function info()
    {
        $args = func_get_args();
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }
        else if($args[0] === 'flashcards') 
        {
            $data = getPublicEventsOnThisDay('today');
            $data = array_filter( $data
                , function($ev) {
                    return (strtotime($ev['end_time']) >= strtotime('now'));
                });
            $tom = getPublicEventsOnThisDay('tomorrow');
            $data = array_merge($tom, $data);
            $cards = [];
            foreach($data as $item)
            {
                $cards[] = [ 'title'=>$item['title']
                    , 'date'=>$item['date']
                    , 'time'=>$item['start_time']
                    , 'venue'=>$item['venue']
                ];
            }
            $this->send_data($cards, 'ok');
            return;
        }
        else if($args[0] === 'venues')
        {
            if($args[1] === 'availability')
            {
                $data = getVenuesWithStatusOnThisDayAndTime($_POST['date']
                    , $_POST['start_time'], $_POST['end_time']
                );
                $this->send_data($data, 'ok');
                return;
            }
        }

        else if($args[0] === 'externalid')
        {
            $externalID = explode('.', $args[1]);
            if(count($externalID) != 2)
            {
                $this->send_data([], 'ok');
                return;
            }
            $tableName = $externalID[0];
            $id = $externalID[1];
            $info = getTableEntry($tableName, 'id,status'
                , ['id'=>$id, 'status'=>'VALID']
            );
            $this->send_data($info, 'ok');
            return;
        }
        else if($args[0] === 'bmv')
        {
            if($args[1] === 'bookingclasses')
            {
                $public = getTableEntry('config', 'id', ["id"=>'BOOKMYVENUE.CLASS']);
                $public = explode(',', __get__($public, 'value', ''));

                $nopublic = getTableEntry('config', 'id', ["id"=>'BOOKMYVENUE.NOPUBLIC.CLASS']);
                $nopublic = explode(',', __get__($nopublic, 'value', ''));
                $all = array_unique(array_merge($public, $nopublic));
                $this->send_data(['all'=>$all, 'public'=>$public, 'nonpublic'=>$nopublic], "ok");
                return;
            }
        }
        else if($args[0] === 'aws_schedule')
        {
            $awses = getTentativeAWSSchedule();
            $data = [];
            foreach($awses as &$aws)
            {
                $info = getExtraAWSInfo($aws['speaker']);
                $aws = array_merge($aws, $info);
                $data[$aws['date']][] = $aws;
            }
            $this->send_data($data, 'ok');
            return;
        }
        else if($args[0] === 'repeatpat')
        {
            $pat = base64_decode($args[1]);
            $data = repeatPatToDays($pat);
            $this->send_data($data, 'ok');
            return;
        }
        $this->send_data(['Unknown request'], "ok");
        return;
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Search API.
        *
        * @Returns   Send array only. Do 
     */
    /* ----------------------------------------------------------------------------*/
    public function search()
    {
        $args = func_get_args();
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $q = urldecode($args[1]);
        if($args[0] === 'awsspeaker')
        {
            $logins = searchInLogins($q, "AND eligible_for_aws='YES'");
            $this->send_data_helper($logins);
            return;
        }
        else if($args[0] === 'speaker')
        {
            $speakers = searchInSpeakers($q);
            foreach($speakers as &$speaker)
            {
                $speaker['email'] = __get__($speaker, 'email', '');
                $speaker['html'] = speakerToHTML($speaker);
            }
            $this->send_data_helper($speakers);
            return;
        }
        else if($args[0] === 'host')
        {
            $speakers = searchInSpeakers($q);
            $faculty = searchInFaculty($q);
            $this->send_data_helper(array_merge($speakers, $faculty));
            return;
        }
        else if($args[0] === 'faculty')
        {
            $faculty = searchInFaculty($q);
            $this->send_data_helper($faculty);
            return;
        }
        else if($args[0] === 'login')
        {
            $logins = searchInLogins($q);
            $this->send_data_helper($logins);
            return;
        }
        else
        {
            $this->send_data(['Unsupported query']);
            return;
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Course related API.
        *
        *    - /courses/running
        *    - /courses/register/course_id/[CREDIT,AUDIT,DROP]
        *    - /courses/feedback/questions
        *       Return questions for feedback.
        *    - /courses/metadata
        *       Return metadata for all courses.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function courses()
    {
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $args = func_get_args();
        if(count($args)==0)
            $args[] = "running";

        if($args[0] === 'running')
        {
            $year = __get__($args, 1, getCurrentYear());
            $semester = __get__($args, 2, getCurrentSemester());
            $data = getSemesterCourses($year, $semester);

            // For convinience, let user know if he/she can register for this
            // course.
            $this->send_data($data, "ok");
            return;
        }
        else if($args[0] === 'register')
        {
            $data = ['type' => strtoupper($args[2])];
            $data['student_id'] = getLogin();
            assert($args[1]);

            // We are sending base64 encoded string because course id can have
            // banned characters e.g. '&' in B&B
            $fs = splitAt(base64_decode($args[1]), '-');
            assert(count($fs)==3);

            $course = getRunningCourseByID($fs[0], $fs[2], $fs[1]);

            // Do not send email when using APP.
            $res = handleCourseRegistration($course, $data, $data['type']
                ,  getLogin(), getLogin());

            if($res['success'])
                $this->send_data($res, 'ok');
            else
                $this->send_data($res, 'error');

            $this->send_data($res, 'ok');
            return;
        }
        else if($args[0] === 'metadata')
        {
            $cids = base64_decode(__get__($args, 1, base64_encode('all')));
            if( $cids === 'all')
            {
                $data = [];
                $metadata = getTableEntries('courses_metadata', 'id', "status='VALID'");
                foreach($metadata as $m)
                {
                    $m['instructors'] = getCourseInstructors($m['id']);
                    $data[$m['id']] = $m;
                }
            }
            else
            {
                $data = [];
                foreach(explode(',', $cids) as $cid)
                    $data[$cid] = getCourseById($cid);
            }

            ksort($data);
            $this->send_data($data, "ok");
            return;
        }
        else if($args[0] === "registration")
        {
            $crs = explode('-', base64_decode($args[1]));
            $data = getCourseRegistrations($crs[0], intval($crs[2]), $crs[1]);
            $this->send_data($data, "ok");
            return;
        }
        else if($args[0] === "feedback")
        {
            $data = [];
            $request = __get__($args, 1, '');
            if($request === "questions")
            {
                $data = getCourseFeedbackQuestions();
            }
            else if($request === "get")
            {
                $fs = explode('-', base64_decode($args[2]));
                assert(count($fs)==3);
                $data = getCourseSpecificFeedback($fs[2], $fs[1], $fs[0], getLogin());
            }
            else
                $data = ["Unsupported request: $request"];
            $this->send_data($data, "ok");
            return;
        }
        else
        {
            $this->send_data(["Unknown request"], "error");
            return;
        }
    }

    // Only acadadmin can do these.
    public function course()
    {
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $login = getLogin();
        if(! in_array('ACAD_ADMIN', getRoles($login)))
        {
            $this->send_data([], "Forbidden");
            return;
        }

        $args = func_get_args();
        if($args[0] === 'metadata')
        {
            if($args[1] === 'get')
            {
                $cid = base64_decode($args[2]);
                $data = getCourseById($cid);
                $this->send_data($data, "ok");
                return;
            }
            else if($args[1] === 'update')
            {
                $res = updateCourseMetadata($_POST);
                $this->send_data(['success'=>$res], "ok");
                return;
            }
            else if($args[1] === 'add')
            {
                $isValid = true;
                foreach(['id', 'name'] as $k)
                    if(! $_POST[$k])
                        $isValid = false;

                if($isValid) {
                    $res = insertCourseMetadata($_POST);
                    $this->send_data(['success'=>$res, 'payload'=>$_POST], "ok");
                } else {
                    $this->send_data(['success'=>'Invalid entry', 'payload'=>$_POST], "ok");
                }
                return;
            }
            else if($args[1] === 'delete')
            {
                $cid = base64_decode($args[2]);
                $_POST['id'] = $cid;
                $_POST['status'] = 'INVALID';
                $res = updateTable('courses_metadata', 'id', 'status', $_POST);
                $this->send_data(['success'=>$res], "ok");
                return;
            }
            else if($args[1] === 'deactivate')
            {
                $cid = base64_decode($args[2]);
                $_POST['id'] = $cid;
                $_POST['status'] = 'DEACTIVATED';
                $res = updateTable('courses_metadata', 'id', 'status', $_POST);
                $this->send_data(['success'=>$res], "ok");
                return;
            }
            else if($args[1] === 'activate')
            {
                $cid = base64_decode($args[2]);
                $_POST['id'] = $cid;
                $_POST['status'] = 'VALID';
                $res = updateTable('courses_metadata', 'id', 'status', $_POST);
                $this->send_data(['success'=>$res], "ok");
                return;
            }
            else
            {
                $this->send_data(["Unknown request"], "error");
                return;
            }
        }
        else if($args[0] === 'running')
        {
            $endpoint = $args[1];
            if($endpoint === 'update')
            {
                $res = @addOrUpdateRunningCourse($_POST, 'update');
                $this->send_data($res, "ok");
                return;
            }
            else if($endpoint === 'add')
            {
                $res = @addOrUpdateRunningCourse($_POST, 'add');
                $this->send_data($res, "ok");
                return;
            }
            else if($endpoint === 'remove')
            {
                $res = deleteRunningCourse($_POST);
                $this->send_data($res, "ok");
                return;
            }
            else if($endpoint === 'assignslotvenue')
            {
                $res = assignSlotVenueRunningCourse($_POST);
                $this->send_data($res, "ok");
                return;
            }
            else
            {
                $this->send_data(["Unknown endpoint slot/$endpoint"], "error");
                return;
            }
        }
        if($args[0] === 'slot')
        {
            $endpoint = $args[1];
            if($endpoint === 'all')
            {
                $data = getSlots();
                $this->send_data($data, "ok");
                return;
            }
            else
            {
                $this->send_data(["Unknown endpoint slot/$endpoint"], "error");
                return;
            }
        }
        else
        {
            $this->send_data(["Unknown request"], "error");
            return;
        }
    }



    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Journal club endpoint.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function jc()
    {
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $args = func_get_args();
        if($args[0] === 'update')
        {
            $res = updateTable('jc_presentations', 'id'
                , 'title,description,url,presentation_url', $_POST);
            $this->send_data([$res?'Success':'Failed'], 'ok');
            return;
        }
        else if($args[0] === 'acknowledge')
        {
            $_POST['acknowledged'] = 'YES';
            $_POST['id'] = $args[1];
            $res = updateTable('jc_presentations', 'id','acknowledged', $_POST);
            $this->send_data([$res?'Success':'Failed'], 'ok');
            return;
        }
        else if($args[0] === 'info')
        {
            $jcID = __get__($args, 1, 'all');
            if($jcID !== 'all')
                $data = getTableEntry('journal_clubs', 'id,status'
                , ["id"=>$jcID, 'status'=>'ACTIVE']);
            else
                $data = getTableEntries('journal_clubs', 'id', "status='ACTIVE'");
            $this->send_data($data, 'ok');
            return;
        }
        else if($args[0] === 'subscriptions')
        {
            $jcID = $args[1];
            $data = getTableEntries('jc_subscriptions', 'login'
                , "jc_id='$jcID' AND status='VALID'");
            $this->send_data($data, 'ok');
            return;
        }
        else
        {
            $this->send_data(['msg'=>"Unknown request", 'success'=>false], "ok");
            return;
        }
    }

    public function jcadmin()
    {
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        // These requires JC ADMIN privileges.
        if(! isJCAdmin(getLogin()) )
        {
            $this->send_data([msg=>"You are not an admin", 'success'=>false], 'ok');
            return;
        }

        // JC ADMIN tasks.
        $args = func_get_args();
        if($args[0] === 'remove')
        {
            $_POST['status'] = 'INVALID';
            $_POST['id'] = $args[1];
            $res = removeJCPresentation($_POST);
            $this->send_data($res, 'ok');
            return;
        }
        else if($args[0] === 'update')
        {
            $res = updateTable('jc_presentations', 'id'
                , 'title,description,url,presentation_url', $_POST);
            $this->send_data([$res?'Success':'Failed'], 'ok');
            return;
        }
        else if($args[0] === 'assign')
        {
            $_POST['date'] = dbDate($_POST['date']);
            $_POST['time'] = dbTime($_POST['time']);
            $res = assignJCPresentationToLogin($_POST['presenter'], $_POST );
            $this->send_data($res, 'ok');
            return;
        }
        else if($args[0] === 'unsubscribe')
        {
            $jcid = urldecode($args[1]);
            $login = urldecode($args[2]);
            $data = ['jc_id' => $jcid, 'login'=>$login];
            $res = unsubscribeJC($data);
            $this->send_data($data, 'ok');
            return;
        }
        else if($args[0] === 'subscribe')
        {
            $jcid = $args[1];
            $login = $args[2];
            $res = subscribeJC( ['jc_id'=>$jcid,  'login'=>$login]);
            $this->send_data($res, 'ok');
            return;
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Return events based on GET query.
        * Examples of endpoints,
        *     - events/latest                       Latest 20 events.
        *     - events/latest/50                    Last 50 events.
        *     - events/latest/50/10                 Last 40 events starting from 10
        *     - events/date/2019-03-01              On this date.
        *     - events/date/2019-03-01/2019-04-01   From this date to this date.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function events()
    {
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

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
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $args = func_get_args();
        if(count($args)==0)
            $args = ['latest'];
        $this->process_aws_requests($args);
    }


    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  API related to venues. This require authentication.
        *   - /venue/list/{type}|all
        *   - /venue/info/{venue}
        *   - /venue/book/venueid/startDateTime/endDateTime
        *       (rest of the information is in POST request. If POST request
        *       has incomple information. Send back error message.
        *
        *   For following venues is a csv list of venues ID.
        *   - /venue/status/{venues}  -- Will query for 'now()'.
        *   - /venue/status/{venues}/startDateTime/endDateTime
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function venue()
    {
        // This require authentication.
        $data = [];
        $args = func_get_args();
        if(count($args) == 0)
        {
            $this->send_data(["Invalid URL"], "error");
            return;
        }
        $this->process_venue_request($args);
    }

    // Show documentation of venue().
    private function process_venue_request($args)
    {
        // List of venues are available to all even without authentication.
        // Required for MAP to work.
        if( $args[0] === 'list')
        {
            $types = __get__($args, 1, 'all');
            // For courses.
            if($types === 'course')
                $types = 'LECTURE HALL,AUDITORIUM';
            $data = [];
            foreach(explode(',', $types) as $type) {
                $type = urldecode($type);
                $data = array_merge($data, getVenuesByType($type));
            }
            $this->send_data($data, "ok");
            return;
        }

        // Rest of endpoints needs authentication data.
        if(! authenticateAPI(getKey(), getLogin()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }
        else if($args[0] === 'info')
        {
            $id = __get__($args, 1, 0);
            $data = getVenueById($id);
            $this->send_data($data, 'ok');
            return;
        }
        else if($args[0] === 'status')
        {
            $data = [];
            // Get the status of given venus Venues id are send by csv.
            $venues = explode(',', __get__($args, 1, 'all'));
            // Select all venues.
            if(! $venues || ($venues[0] == 'all'))
                $venues = getVenuesNames();

            $startDateTime = intval(__get__($args, 2, strtotime('now')));
            $endDateTime = intval(__get__($args, 3, $startDateTime+15*3600));

            // Only for a day.
            $date = dbDate($startDateTime);
            $time = dbTime($startDateTime);
            $end_time = dbTime($endDateTime);

            $res = [];
            foreach($venues as $venue)
            {
                $eventsAndReqs = getVenueBookingsOnDateTime($venue, $date, $time, $end_time);
                $res[] = ['id'=>$venue, 'events'=>$eventsAndReqs];
                $data[$venue] = $eventsAndReqs;
            }

            $data['REQ_DATE'] = $date;
            $data['REQ_START_TIME'] = $time;
            $data['REQ_END_TIME'] = $end_time;
            $data['venues'] = $res;
            $this->send_data($data, 'ok');
            return;
        }
        else if($args[0] === 'book')
        {
            $this->bookVenue(base64_decode($args[1])
                , intval(__get__($args,2,0))
                , intval(__get__($args,3,0))
                , $_POST
            );
            return;
        }
        else
        {
            $this->send_data(["unknown endpoint" . $args[0]], "ok");
            return;
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Helper function to book venue.
        *
        * @Param $venueid
        * @Param $startDateTime 
        * @Param $endDateTime   
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    private function bookVenue(string $venueId, int $startDateTime=0
        , int $endDateTime=0, array $data)
    {
        if($startDateTime == 0)
            $startDateTime = __get__($data, 'start_date_time', 0);
        if($endDateTime == 0)
            $endDateTime = __get__($data, 'end_date_time', 0);

        $startDateTime = is_numeric($startDateTime)?$startDateTime:intval($startDateTime);
        $endDateTime = is_numeric($endDateTime)?$endDateTime:intval($endDateTime);

        if( (! $venueId) || ($startDateTime >= $endDateTime))
        {
            $data = ['msg' => "Could not book for: venue=$venue, startDateTime=$startDateTime
                and endTime=$endTime"];
            $this->send_data($data, "error" );
            return;
        }

        // Who is creating.
        $data['created_by'] = getLogin();

        // Now check if 'dates' or 'repeat_pat is given.
        $request = array_merge($data
            , ['venue'=>$venueId, 'date' => dbDate($startDateTime)
            , 'start_time' => dbTime($startDateTime)
            , 'end_time' => dbTime($endDateTime)]
        );

        $ret = submitBookingRequest( $request );
        $status = $ret['success']?'ok':'error';
        // $ret['payload'] = json_encode($request);
        $this->send_data($ret, $status);
        return;
    }

    public function authenticate( )
    {
        $user = __get__($_POST,'login','NA');
        $password = __get__($_POST,'password', 'NA');
        $pass = trim(base64_decode($password));

        $res = authenticateUser($user, $pass);
        $token = '';
        $gmapkey = '';

        // If $res is true then return a token. User can use this token to login
        // as many time as she likes.
        if($res)
        {
            $token = __get__(getUserKey($user), 'apikey', '');
            if(! $token)
                $token = genererateNewKey($user);
            $gmapkey = getConfigValue('GOOGLE_MAP_API_KEY');
        }

        $this->send_data(['apikey'=>$token, 'gmapapikey'=>$gmapkey
            , 'authenticated'=>$res?true:false], $token?'ok':'erorr');
        return;
    }

    public function authenticate_by_key( )
    {
        $user = $_POST['user'];
        $key = $_POST['HIPPO-API-KEY'];
        if(authenticateAPI($key, $user))
            $this->send_data(['authenticated' => true ], "ok");
        else
            $this->send_data(['authenticated' => false ], "error");
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  
        *
        * One can query key value pair from config table. Make sure not to put
        * any sensitivie information in config table. 
        * TODO: I may have to redo this table.
        *
        *   - /api/config/key e.g.
        *   - /api/config/bookmyvenue.class
        *   - /api/config/evnet.class 
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function config( )
    {
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $args = func_get_args();
        if(in_array(strtoupper($args[0]), ['CLIENT_SECRET', 'GOOGLE_MAP_API_KEY']))
        {
            $this->send_data(["Not allowed"], "ok");
            return;
        }

        if( __get__($args, 0, '') )
        {
            $id = $args[0];
            $data = getTableEntry('config', 'id', ["id"=>$id]);
            $this->send_data($data, "ok");
            return;
        }

        $this->send_data(["Empty query"], "ok");
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis  mybooking related actioons.
     *  - /api/mybooking/list/[date] -- return all booking by 'login' (in post)
     *  - /api/mybooking/delete/request/gid.[rid] -- delete request gid.rid
     *  - /api/mybooking/delete/event/gid.[eid] -- delete request gid.rid
     */
    /* ----------------------------------------------------------------------------*/
    public function mybooking( )
    {
        // Only need api key
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }
        $args = func_get_args();

        $args = func_get_args();
        if( $args[0] === 'list')
        {
            $startDate = dbDate(intval(__get__($args, 1, strtotime('today'))));
            $login = getLogin();
            $requests = getRequestOfUser($login);
            $events = getEventsOfUser($login, $startDate);

            // Group them by gid.
            $requestsGrouped = [];
            $eventsGrouped = [];
            foreach($requests as $r)
                $requestsGrouped[$r['gid']][] = $r;
            foreach($events as $e)
                $eventsGrouped[$e['gid']][] = $e;

            $this->send_data(
                ["date"=>$startDate, "requests"=>$requestsGrouped , "events"=>$eventsGrouped]
                , "ok");
            return;
        }

        // Delete given request of events.
        if($args[0] === 'delete')
        {
            $login = $_POST['login'];
            if($args[1] === 'request')
            {
                $data = explode('.', $args[2]);
                $gid = $data[0];
                $rid = __get__($data, 1, '');
                if($rid)
                {
                    $res = changeRequestStatus($gid, $rid, 'CANCELLED');
                    $this->send_data( ['success'=>$res, 'msg'=>"Request $gid.$rid is deleted"], "ok");
                    return;
                }
                else
                {
                    // delete the whole group.
                    $res = changeStatusOfRequests($gid, 'CANCELLED');
                    $this->send_data(['success'=>$res
                        , 'msg'=>"Request group $gid is deleted"]
                        , "ok");
                    return;
                }
            }
            else if($args[1] == 'event')
            {
                $data = explode('.', $args[2]);
                $gid = $data[0];
                $login = $_POST['login'];
                $eid = __get__($data, 1, '');
                if($eid)
                {
                    $res = changeStatusOfEvent($gid, $eid, $login, 'CANCELLED');
                    $this->send_data(['success'=>$res, 
                        'msg'=>"Event $gid.$eid is cancelled"], "ok");
                    return;
                }
                else
                {
                    // delete the whole group.
                    $res = changeStatusOfEventGroup($gid, $login, 'CANCELLED');
                    $this->send_data(['msg'=>"Event group $gid is cancelled."
                        , 'success'=>$res], "ok");
                    return;
                }
            }
            else
            {
                $this->send_data(['success'=>false, 'msg'=>"Not implemented"], "ok");
                return;
            }
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Return public events from a given date.
        *  - /publicevents/[date=today]/[numtofetch=20]/[offset=0]
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function publicevents()
    {
        // People can access it without login.
        //if(! authenticateAPI(getKey()))
        //{
        //    $this->send_data([], "Not authenticated");
        //    return;
        //}

        $args = func_get_args();
        $startDate = dbDate(intval(__get__($args, 0, strtotime('today'))));
        $limit = intval(__get__($args, 1, 20));
        $offset = intval(__get__($args, 2, 0));
        $data = getUpcomingPublicEventsFormatted($startDate, $limit, $offset);
        $this->send_data( $data, 'ok');
        return;
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Transport details.
        *    - /api/transport/day/[from]/[to]/[vehicle]
        *    e.g., 
        *       - /api/tranport/day/MANDARA/NCBS/Buggy
        *
     */
    /* ----------------------------------------------------------------------------*/
    public function transport()
    {
        $args = func_get_args();
        $day = __get__($args, 0, 'all');
        $where = "status='VALID'";
        if($day != 'all')
            $where .= " AND day='$day'";

        $pickupPoint = __get__($args, 1, '');
        $dropPoint = __get__($args, 2, '');
        $vehicle = __get__($args, 3, '');

        if( $pickupPoint )
            $where .= " AND pickup_point='$pickupPoint' ";
        if( $dropPoint )
            $where .= " AND drop_point='$dropPoint' ";
        if( $vehicle )
            $where .= " AND vehicle='$vehicle' ";

        $data = getTableEntries('transport', 'day,pickup_point,trip_start_time', $where);
        $timetableMap = [];
        foreach( $data as $d )
        {
            $timetableMap[strtolower($d['day'])]
                [strtolower($d['pickup_point'])]
                [strtolower($d['drop_point'])][] = $d;
        }

        // Get routes.
        $routes = executeQuery( 
            "SELECT DISTINCT pickup_point,drop_point,url FROM transport WHERE status='VALID'"
        );
        $res = ['timetable'=> $timetableMap, 'routes'=>$routes];
        $this->send_data($res, 'ok');
        return;
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Transport details.
        *    - /api/transportation/timetable/day/[from]/[to]/[vehicle]
        *    e.g., 
        *       - /api/tranport/schedule/day/MANDARA/NCBS/Buggy
        *    - /api/transportation/vehicle/[list|remove|add|update]
        *    - /api/transportation/routes/[list|remove|add|update]
        *
     */
    /* ----------------------------------------------------------------------------*/
    public function transportation()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $login = getLogin();
        $roles = getRoles($login);
        if(! in_array('SERVICES_ADMIN', $roles))
        {
            $this->send_data([$roles], "Forbidden.");
            return;
        }

        $args = func_get_args();
        $endpoint = $args[0];

        if($endpoint === 'timetable') 
        {
            $days = strtolower(__get__($args, 1, 'all'));
            $where = "status='VALID'";
            if($days != 'all')
            {
                $where .= ' AND (';
                $temp = [];
                foreach(explode(',', $days) as $day)
                    $temp[] = " day='$day' ";
                $where .= implode(" OR ", $temp);
                $where .= " )";
            }

            $pickupPoint = __get__($args, 2, '');
            $dropPoint = __get__($args, 3, '');
            $vehicle = __get__($args, 4, '');

            if( $pickupPoint )
                $where .= " AND pickup_point='$pickupPoint' ";
            if( $dropPoint )
                $where .= " AND drop_point='$dropPoint' ";
            if( $vehicle )
                $where .= " AND vehicle='$vehicle' ";

            $data = getTableEntries('transport', 'vehicle,trip_start_time,day', $where);
            $this->send_data($data, 'ok');
            return;
        }
        else if($endpoint === 'vehicle')
        {
            if($args[1] === 'list')
            {
                $res = executeQuery("SELECT DISTINCT vehicle FROM transport WHERE status='VALID'");
                $this->send_data(array_map(function($x) { return $x['vehicle']; }, $res), 'ok');
                return;
            }
            else if($args[1] === 'add')
            {

            }
            else if($args[1] === 'remove')
            {

            }
            else if($args[1] === 'update')
            {

            }
            else
            {
                $res = ["unknown endpoint $endpoint/".$args[1]];
                $this->send_data($res, 'ok');
                return;
            }
        }
        else if($endpoint === 'schedule')
        {
            if($args[1] === 'list')
            {

            }
            else if($args[1] === 'add')
            {
                $res = [];
                try {
                    $res = addNewTransportationSchedule($_POST);
                }
                catch (Exception $e) {
                    $res = ['success'=>false, 'msg' => $e->getMessage()];
                }

                if(!$res['success'])
                    $res['payload'] = $_POST;
                $this->send_data($res, 'ok');
                return;
            }
            else if($args[1] === 'remove' || $args[1] === 'delete')
            {
                if(intval($args[2]) < 0) {
                    $this->send_data(["Invalid entry."], 'ok');
                    return;
                }
                foreach(explode(',', $args[2]) as $id) {
                    $data = ['id'=> $id];
                    $res = deleteFromTable('transport', 'id', $data);
                }
                $this->send_data(['success'=>$res], 'ok');
                return;
            }
            else if($args[1] === 'update')
            {
                $keys = 'trip_start_time,trip_end_time,day,comment,day,';
                $keys .= "edited_by,last_modified_on";
                $_POST['last_modified_on'] = strtotime('now');
                $_POST['edited_by'] = getLogin();
                $res = updateTable('transport', 'id', $keys, $_POST);
                $this->send_data(['success'=>$res], 'ok');
                return;
            }
            else
            {
                $res = ["unknown endpoint $endpoint/".$args[1]];
                $this->send_data($res, 'ok');
                return;
            }
        }
        else if($endpoint === 'route')
        {
            if($args[1] === 'list')
            {
                $routes = executeQuery( 
                    "SELECT DISTINCT pickup_point,drop_point,url FROM transport WHERE status='VALID'"
                );
                $this->send_data($routes, 'ok');

            }
            else if($args[1] === 'add')
            {

            }
            else if($args[1] === 'remove')
            {

            }
            else if($args[1] === 'update')
            {

            }
            else
            {
                $res = ["unknown endpoint $endpoint/".$args[1]];
                $this->send_data($res, 'ok');
                return;
            }
        }
        else 
        {
            $res = ["unknown endpoint $endpoint"];
            $this->send_data($res, 'ok');
            return;
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Find a person over ldap.
        *
        * @Param $query
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function ldap( $query )
    {
        // Only need api key
        if(! authenticateAPI(getKey(), getLogin()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }
        $res = getUserInfoFromLdapRelaxed($query);
        $data = [];
        foreach($res as $ldap)
        {
            if(strtolower($ldap['is_active']) != 'true')
                continue;

            $phone = '';
            if( is_numeric(__get__($ldap, 'extension', 'NA')))
                $phone = '+91 80 2366 ' . $ldap['extension'];
            $data[] = [ 
                'name'=> implode(' ', [__get__($ldap,'fname',''), __get__($ldap,'lname','')])
                , 'email'=>$ldap['email'] 
                , 'phone' => $phone
                , 'group' => $ldap['laboffice']
                , 'extension' => $ldap['extension']
            ];
        }
        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  API related to user profile.
        *
        * @Param 
        *   - /me/profile
        *   - /me/photo
        *   - /me/aws
        *   - /me/jc
        *   - /me/roles
        *   - /me/talk
        *             /register
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function me()
    {
        // Only need api key
        if(! authenticateAPI(getKey(), getLogin()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $user = getLogin();
        $args = func_get_args();

        if($args[0] === 'profile')
        {
            $endpoint = __get__($args, 1, 'get');
            if($endpoint === 'get') 
            {
                $ldap = getUserInfo($user, true);
                $remove = ['fname', 'lname', 'mname', 'roles'];
                $data = array_diff_key($ldap, array_flip($remove));
                $this->send_data($data, "ok");
                return;
            }
            elseif($endpoint === 'update')
            {
                $data = ['success'=>false, 'msg'=>''];
                $editables = array_keys(getProfileEditables());
                $_POST['login'] = getLogin();
                $res = updateTable('logins', 'login', $editables, $_POST);
                if($res)
                    $data['success'] = true;
                else
                    $data['msg'] .= 'Failed to update profile.';
                $this->send_data($data, "ok");
                return;
            }
            elseif($endpoint === 'editables') 
            {
                $this->send_data(getProfileEditables(), "ok");
                return;
            }
            else
            {
                $this->send_data(["unknown request $endpoint."], "ok");
                return;
            }
        }
        else if($args[0] === 'roles')
        {
            $data = executeQuery("SELECT roles FROM logins WHERE login='$user'");
            $this->send_data($data[0], "ok");
            return;
        }
        else if($args[0] === 'photo')
        {
            $pic = getUserPhotoB64(getLogin());
            $this->send_data(['base64'=>$pic], "ok");
            return;
        }
        else if($args[0] === 'request')
        {
            $params = explode('.', $args[1]);
            $where = 'gid';
            if(intval(__get__($params, 1, -1)) >= 0)
                $where .= ',rid';
            $res = updateTable('bookmyvenue_requests'
                , $where
                , 'title,description,is_public_event,class'
                , $_POST);
            $this->send_data(['success'=>$res, 'msg'=>'Success'], "ok");
            return;
        }
        else if($args[0] === 'event')
        {
            $params = explode('.', $args[1]);
            $where = 'gid';
            if(intval(__get__($params, 1, -1)) >= 0)
                $where .= ',eid';
            $res = updateTable('events'
                , $where
                , 'title,description,is_public_event,class'
                , $_POST);
            $this->send_data(['success'=>$res, 'msg'=>'Success'], "ok");
            return;
        }
        else if( $args[0] === 'roles')
        {
            $info = getUserInfo($user, true);
            $data = explode(',', $info['roles']);
            $this->send_data($data, "ok");
            return;
        }
        else if( $args[0] === 'aws')
        {
            $data = getAwsOfSpeaker($user);
            $upcoming = getUpcomingAWSOfSpeaker($user);
            if($upcoming)
                $data[] = $upcoming;
            $this->send_data($data, 'ok');
            return;
        }
        else if( $args[0] === 'acknowledge_aws')
        {
            $user = getLogin();
            $awsID = $args[1];
            $res = updateTable('upcoming_aws', 'id,speaker', 'acknowledged'
                , ['id'=>$awsID, 'speaker'=>$user, 'acknowledged'=>'YES']
            );
            $this->send_data(['res'=>$res], 'ok');
            return;
        }
        else if($args[0] === 'talk')
        {
            if($args[1] === 'register' || $args[1] === 'add')
            {
                $_POST['created_by'] = getLogin();
                $_POST['created_on'] = dbDateTime('now');
                $_POST['speaker'] = speakerName($_POST['speaker_id']);
                $data = addNewTalk($_POST);
                $this->send_data($data, 'ok');
                return;
            }
            else if($args[1] === 'all')
            {
                $data = getMyTalks(getLogin());
                $this->send_data($data, 'ok');
                return;
            }
            else if($args[1] === 'upcoming')
            {
                $data = getMyUnscheduledTalks(getLogin());
                $this->send_data($data, 'ok');
                return;
            }
            else if($args[1] === 'cancel')
            {
                $data = cancelTalk($args[2]);
                $this->send_data($data, 'ok');
                return;
            }
            $this->send_data(["unknown requests"], 'ok');
            return;
        }
        else if( $args[0] === 'course')
        {
            $data = getMyAllCourses($user);
            ksort($data);
            $this->send_data($data, "ok");
            return;
        }
        else if( $args[0] === 'jc')
        {
            $endpoint = __get__($args, 1, 'presentations');
            if($endpoint === 'presentations') {
                $data = getUpcomingJCPresentations();
                $this->send_data($data, "ok");
                return;
            }
            else if( $endpoint === 'list') {
                $jcs = [];
                foreach(getUserJCs($user) as $jc)
                    $jcs[$jc['jc_id']] = $jc;
                $this->send_data($jcs, "ok");
                return;
            }
            else if( $endpoint === 'subscribe') {
                $jcid = $args[2];
                $data = ['jc_id'=>$jcid, 'login'=>getLogin()];
                $res = subscribeJC($data);
                $this->send_data($res, "ok");
                return;
            }
            else if( $endpoint === 'unsubscribe') {
                $jcid = $args[2];
                $data = ['jc_id'=>$jcid, 'login'=>getLogin()];
                $res = unsubscribeJC($data);
                $this->send_data($res, "ok");
                return;
            }
            else {
                $this->send_data(["unknown endpoint: $endpoint."], 'ok');
                return;
            }
        }
        else
        {
            $data = ['Unknown query'];
            $this->send_data($data, 'ok');
            return;
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  API related to user accomodation.
        *
        * @Param 
        *   - /accomodation/list/{all|10}  -- list 10/all 
        *   - /accomodation/update         -- POST shall have id.
        *   - /accomodation/delete/id
        *   - /accomodation/comment/list/[id] -- get comment for given ids(csv)
        *   - /accomodation/comment/post/[id] -- get comment for given ids(csv)
        *   - /accomodation/comment/delete/[id] -- Delete given id ids(csv)
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function accomodation()
    {
        $user = getLogin();
        $args = func_get_args();

        if( $args[0] === 'list')
        {
            $limit = __get__($args, 1, 0);
            $data = [];
            $available = getTableEntries( 'accomodation', 'status,available_from'
                        , "status != 'EXPIRED' AND status != 'INVALID'"
                        , "*", $limit 
                    );

            // Add number of comments.
            foreach( $available as &$item)
            {
                $extID = 'accomodation.' . $item['id'];
                $item['num_comments'] = getNumberOfRowsInTable('comment'
                    , "external_id='$extID' AND status='VALID'"
                );
            }

            $data['list'] = $available;
            $data['count'] = count($available);
            $data['types'] = getTableColumnTypes('accomodation', 'type');
            $data['status'] = getTableColumnTypes('accomodation', 'status');
            $data['available_for'] = getTableColumnTypes('accomodation', 'available_for');
            $this->send_data( $data, 'ok');
            return;
        }

        // After this we need authentication.
        if(! authenticateAPI(getKey(), getLogin()))
        {
            $this->send_data(["Not authenticated"], "error");
            return;
        }

        if($args[0] === 'create')
        {
            $id = getUniqueID('accomodation');
            $_POST['id'] = $id;
            $_POST['status'] = 'AVAILABLE';
            $_POST['created_by'] = getLogin();
            $_POST['created_on'] = dbDateTime( 'now' );

            $res = insertIntoTable( 'accomodation'
                , 'id,type,available_from,available_for,open_vacancies,address,description'
                . ',status,owner_contact,rent,extra,advance,url,created_by,created_on'
                , $_POST);

            if($res)
                $this->send_data( ['success'=>true, 'id'=>$id, 'msg'=>''],  'ok');
            else
                $this->send_data( ['success'=>false, 'msg'=>'Failed'], 'error');
            return;
        }
        if($args[0] === 'update')
        {
            $_POST['last_modified_on'] = dbDateTime('now');
            $res = updateTable( 'accomodation', 'id'
                , 'type,available_from,available_for,last_modified_on,open_vacancies,address,description'
                . ',status,owner_contact,rent,extra,advance,url,last_modified_on,created_by,created_on'
                , $_POST
            );

            if($res)
                $this->send_data(['id'=>$_POST['id']],  'ok');
            else
                $this->send_data( ['Failed'],  'error');
            return;
        }
        else if($args[0] === 'comment')
        {
            $data = $this->handleCommentActions( array_slice($args, 1) );
            $this->send_data($data,  'ok');
            return;
        }
        else
            $this->send_data(['Unknown query ' + $args[0]],  'ok');
    }

    private function handleCommentActions($args)
    {
        if( $args[0] == 'list')
        {
            $ids = __get__($args, 1, '');
            if(! $ids )
            {
                $ids = array_map(
                    function($x){ return $x['id']; }
                , executeQuery("SELECT id FROM accomodation WHERE status!='INVALID'")
                );
            }
            else
                $ids = explode(',', $ids);

            // Created external ids.
            $extIds = array_map(function($id) { return "'accomodation.$id'";}, $ids);
            $extIds = implode(',', $extIds);
            $comments = executeQuery("SELECT * FROM comment WHERE 
                external_id in ($extIds) AND status='VALID'");
            $data = ['ids' => $ids, 'comments' => array_values($comments) ];
            return $data;
        }
        else if( $args[0] == 'post')
        {
            // posting comment.
            $_POST['commenter'] = getLogin();
            $_POST['external_id'] = 'accomodation.'.$_POST['id'];
            $res = User::postComment($_POST);
            return $res;
        }
        else if( $args[0] == 'delete')
        {
            // posting comment.
            $id = __get__($args, 1, 0);
            $res = User::deleteComment($id);
            return $res;
        }
        else
            return [ 'This action is not available ' . json_encode($args) ];
    }

    public function comment( )
    {
        // After this we need authentication.
        if(! authenticateAPI(getKey(), getLogin()))
        {
            $this->send_data(["Not authenticated"], "error");
            return;
        }

        $args = func_get_args();
        if( $args[0] == 'delete')
        {
            $id = __get__($args, 1, 0);
            $res = User::deleteComment($id);
            $this->send_data($res, 'ok');
            return;
        }
        else if( $args[0] == 'post')
        {
            // posting comment.
            $_POST['commenter'] = getLogin();
            $_POST['external_id'] = $_POST['external_id'];
            $res = User::postComment($_POST);
            $this->send_data($res, 'ok');
            return;
        }
        else if( $args[0] == 'get')
        {
            // Fetching comments.
            $limit = __get__($args, 1, 20);
            $this->db->select('*')
                 ->where(["external_id" => $args[1], 'status' => 'VALID'])
                 ->order_by( 'created_on DESC' )
                 ->limit($limit);
            $comms = $this->db->get("comment")->result_array();
            $this->send_data($comms, 'ok');
            return;
        }
        else
        {
            $this->send_data(['unsupported ' + $args[0]], 'failure');
            return;
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Inventory management.
        *
        *     - /inventory/list/[num=100]
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function inventory()
    {
        $user = getLogin();
        $args = func_get_args();

        // After this we need authentication.
        if(! authenticateAPI(getKey(), getLogin()))
        {
            $this->send_data(["Not authenticated"], "error");
            return;
        }

        if( $args[0] === 'list')
        {
            $limit = intval(__get__($args, 1, 300));
            $data = [];

            // Generate list of inventories.
            $this->db->select('*')
                 ->where([ 'status'=>'VALID'])
                 ->limit($limit)
             ;

            $inventories = $this->db->get('inventory')->result_array();
            $available = [];
            foreach($inventories as $inv )
            {
                // Fetch imgage is any.
                $inv['image_id'] = [];
                $invID = $inv['id'];

                $this->db->select('id')
                     ->where(['external_id' => "inventory.$invID"]);

                $imgs = $this->db->get('images')->result_array();
                foreach($imgs as $img)
                    $inv['image_id'][] = $img['id'];

                $available[] = $inv;
            }

            $data['list'] = $available;
            $data['count'] = count($available);
            $data['item_conditions'] = getTableColumnTypes('inventory', 'item_condition');
            $this->send_data( $data, 'ok');
            return;
        }

        if($args[0] === 'create')
        {
            $id = getUniqueID( 'inventory' );
            $_POST['id'] = $id;
            $_POST['status'] = 'AVAILABLE';
            $_POST['created_by'] = getLoginEmail(getLogin());
            $_POST['created_on'] = dbDateTime( 'now' );

            $res = insertIntoTable( 'inventory'
                , 'id,type,available_from,open_vacancies,address,description'
                . ',status,owner_contact,rent,extra,advance,url,created_by,created_on'
                , $_POST
            );

            if($res)
                $this->send_data( ['id'=>$id],  'ok');
            else
                $this->send_data( ['Failed'],  'error');
            return;
        }
        if($args[0] === 'update')
        {
            $res = updateTable( 'inventory', 'id'
                , 'type,available_from,open_vacancies,address,description'
                . ',status,owner_contact,rent,extra,advance,url,created_by,created_on'
                , $_POST
            );

            if($res)
                $this->send_data(['id'=>$_POST['id']],  'ok');
            else
                $this->send_data( ['Failed'],  'error');
            return;
        }
        else
            $this->send_data(['Unknown request ' . $args[0]],  'error');

        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  My inventory management.
        *
        *     - /labinventory/list/[num=100]
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function labinventory()
    {
        $user = getLogin();
        $piOrHost = getPIOrHost($user);
        $args = func_get_args();
        $data = [];

        // After this we need authentication.
        if(! authenticateAPI(getKey(), getLogin()))
        {
            $this->send_data(["Not authenticated"], "error");
            return;
        }

        if( $args[0] === 'list')
        {
            $limit = intval(__get__($args, 1, 500));
            $data = [];

            $this->db->select('*')
                 ->where(['status'=>'VALID', 'faculty_in_charge'=>$piOrHost])
                 ->limit($limit);
            $available = $this->db->get( 'inventory')->result_array();

            $itemsToSend = [];

            // Should have a default value.
            $item['borrowing'] = [ ['borrower' => ''] ];

            foreach($available as &$item)
            {
                $id = $item['id'];
                $bres = $this->db->get_where('borrowing'
                   , ['inventory_id'=>$id, 'status'=>'VALID'])->result_array();
                $item['borrowing'] = $bres;

                // Get the thumbnail.
                $this->db->select('id, path')->where(['external_id'=>"inventory.$id"]);
                $images = $this->db->get('images')->result_array();
                $thumbs = [];
                foreach( $images as $img )
                {
                    $path = getUploadDir() . '/' . $img['path'];
                    if(file_exists($path))
                    {
                        $thumb = getBase64JPEG($path, 100, 0);
                        $thumbs[] = [ 'id' => $img['id'], 'base64' => $thumb ] ;
                    }
                }
                $item['thumbnails'] = $thumbs;
                $itemsToSend[] = $item;
            }

            $data['list'] = $itemsToSend;
            $data['count'] = count($available);
            $data['item_conditions'] = getTableColumnTypes('inventory', 'item_condition');
            $this->send_data( $data, 'ok');
            return;
        }

        else if($args[0] === 'create' || $args[0] === 'update')
        {
            $id = getUniqueID( 'inventory' );
            $_POST['id'] = $id;
            $_POST['edited_by'] = getLogin();
            $_POST['last_modified_on'] = dbDateTime( 'now' );
            $res = User::add_inventory_item_helper( $_POST );

            if($res['status'])
                $this->send_data( ['id'=>$id, 'payload'=>json_encode($_POST)],  'ok');
            else
                $this->send_data( [$res['msg']],  'error');
            return;
        }
        else if($args[0] === 'lend')
        {
            $_POST['lender'] = getLogin();
            $_POST['inventory_id'] = $_POST['id'];
            $res = Lab::lend_inventory($_POST);
            $this->send_data([$res['msg']], $res['status']?'ok':'error');
            return;
        }
        else if($args[0] === 'gotback')
        {
            $invId = __get__($args, 1, 0);
            if( !  $this->db->set('status', 'RETURNED')
                     ->where('inventory_id', $invId)
                     ->update('borrowing') )
            {
                $this->send_data($this->db->error(), 'error');
                return;
            }
            $this->send_data([], 'ok');
        }
        else if($args[0] === 'delete')
        {
            $id = __get__($args, 1, 0);
            $res = updateTable('inventory', 'id', 'status', ['id'=>$id, 'status'=>'INVALID']);

            if($res)
                $this->send_data(['id'=>$_POST['id']],  'ok');
            else
                $this->send_data( ['Failed'],  'error');
            return;
        }
        else
            $this->send_data(['Unknown request ' . $args[0]],  'error');

        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Submit geolocation data.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function geolocation( )
    {
        $args = func_get_args();
        if($args[0] === 'submit') 
        {
            $salt = 'ZhanduBalmZhanduBalmPeedaHariBalm';
            $crypt_id = crypt(getUserIpAddr(), $salt);
            $_POST['crypt_id'] = $crypt_id;


            foreach(explode(',', 'session_num,device_id,altitude,accuracy,heading,speed') as $key)
                $_POST[$key] = __get__( $_POST, $key, '');

            // 10 Km/Hr = 2.77 m/s
            // || floatVal($_POST['speed']) <= 1.0 // Enable it when 
            // debugging is over.
            if( floatVal($_POST['latitude']) <= 0 || floatVal($_POST['longitude']) <= 0.0)
            {
                $this->send_data( ["Invalid data."], "warn");
                return;
            }

            $res = insertIntoTable( 'geolocation'
                , 'latitude,longitude,altitude,device_id,accuracy,heading,speed,session_num,crypt_id'
                , $_POST
            );

            if($res)
                $this->send_data( ["Success"], "ok");
            else
                $this->send_data( ["Failure"], "error");
            return;
        }
        else if( $args[0] === 'latest')
        {
            $limit = intval(__get__($args, 1, 500));

            // Get last 100 points (doen't matter when)
            $res = getTableEntries('geolocation', 'timestamp DESC', "", '*', $limit); 

            // crypt_id is the key. Since we don't know the route. Each crypt id 
            // is a polyline.
            $data = [];
            foreach($res as $e)
                $data[$e['crypt_id']][] = $e;

            $this->send_data($data, 'ok');
            return;
        }
        else if( $args[0] === 'get')
        {
            $mins = intval(__get__($args, 1, 30));
            $timestamp = dbDateTime(strtotime('now') - $mins * 60);
            $res = getTableEntries('geolocation', 'crypt_id,timestamp', "timestamp > '$timestamp'"); 
            $data = [];
            foreach($res as $e)
                $data[$e['crypt_id']][] = $e;

            $this->send_data($data, 'ok');
            return;
        }
        else
            $this->send_data( ["Unknown request: " . $args[0]], "warn");

        //// From here we need authentication.
        //// After this we need authentication.
        //if(! authenticateAPI(getKey(), getLogin()))
        //{
        //    $this->send_data(["Not authenticated"], "error");
        //    return;
        //}
        return;
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Download images.
        *
        * @Param $arg
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function images()
    {
        $args = func_get_args();
        if( count($args) == 0)
            $args[] = 'get';

        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        if( $args[0] === 'get')
        {
            $ids = $args[1];
            $data = ['args' => $ids];
            foreach(explode(',', $ids) as $id)
            {
                $images = $this->db->get_where('images', ['id' => trim($id)])->result_array();
                foreach($images as $res)
                {
                    if( ! __get__($res, 'path', ''))
                        continue;

                    $filepath = getUploadDir() . '/' . $res['path'];
                    if(! file_exists( $filepath))
                        continue;
                    try
                    {
                        $data[$id][] = getBase64JPEG($filepath);
                    } 
                    catch (Exception $e) 
                    {
                        $data['exception'] = $e->getMessage();
                    }
                }
            }
            $this->send_data($data, "ok");
            return;
        }
        if( $args[0] === 'delete')
        {
            $ids = $args[1];
            $data = ['args' => $ids, 'msg' => ''];
            foreach(explode(',', $ids) as $id)
            {
                $images = $this->db->get_where('images', ['id' => trim($id)])->result_array();
                foreach($images as $res)
                {
                    $filepath = getUploadDir() . '/' . $res['path'];
                    if(! file_exists( $filepath))
                    {
                        $data['msg'] .= " $filepath not found." ;
                        // File not found. Mark it invalid.
                        $this->db->set('status', 'INVALID')
                             ->where('id', $res['id'])
                             ->update('images');
                        continue;
                    }

                    // Its here delete the file and update the table.
                    unlink($filepath);
                    $data['removed_filepath'] = $filepath;
                    $this->db->set('status', 'DELETED')
                         ->where('id', $res['id'])
                         ->update('images');
                }
            }
            $this->send_data($data, "ok");
            return;
        }
        else
        {
            $this->send_data([], "Unsupported command $get");
            return;
        }

        $this->send_data([], "error");
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Upload images.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function upload()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $args = func_get_args();
        $res = [];
        if( $args[0] === 'image')
        {
            $endpoint = __get__($args, 1, 'inventory');
            if($endpoint === 'inventory') 
            {
                $invId = intval(__get__($_POST, 'inventory_id', -1));
                if($invId < 0)
                {
                    $this->send_data($res, "Inventory ID is not found.");
                    return;
                }

                if (! empty($_FILES)) 
                {
                    $storeFolder = getUploadDir();
                    $tempFile = $_FILES['file']['tmp_name'];          
                    $md5 = md5_file( $tempFile );
                    $filename = $md5 . $_FILES['file']['name'];
                    $targetFile = getLoginPicturePath(getLogin());
                    $res['stored'] = move_uploaded_file($tempFile, $targetFile); 
                    // Add this value to database.
                    $this->db->select_max('id', 'maxid');
                    $r = $this->db->get('images')->result_array();
                    if($r)
                        $id = $r[0]['maxid'];
                    else
                        $id = 0;

                    // Prepare data to send back to client.
                    $data = [ 'external_id' => 'inventory.' . $invId ];
                    $data['path'] = $filename;
                    $data['uploaded_by'] = getLogin();
                    $data['id'] = intval($id)+1;
                    $this->db->insert('images', $data);
                    $res['dbstatus'] = $this->db->error();
                    $this->send_data($res, 'ok');
                    return;
                }
                else
                {
                    $this->send_data( $res, 'No file uploaded.');
                    return;
                }
            }
            else if($endpoint === 'profile') 
            {
                $img = $_FILES[ 'file' ];
                $ext = explode("/", $img['type'])[1];
                $tempFile = $img['tmp_name'];
                $conf = getConf();
                $targetFile = $conf['data']['user_imagedir'].'/'.getLogin().'.jpg';
                saveImageAsJPEG($tempFile, $ext, $targetFile);
                $res['stored'] = $targetFile;
                $this->send_data($res, 'ok');
                return;
            }
            else if($endpoint === 'speaker') 
            {
                $id = $args[2];
                $img = $_FILES[ 'file' ];
                $ext = explode("/", $img['type'])[1];
                $tempFile = $img['tmp_name'];
                $conf = getConf();
                $targetFile = $conf['data']['user_imagedir']."/$id.jpg";
                saveImageAsJPEG($tempFile, $ext, $targetFile);
                $res['stored'] = $targetFile;
                $this->send_data($res, 'ok');
                return;
            }
            else
            {
                $this->send_data( ['Unknow request: ' +$endpoint], 'ok');
                return;
            }
            $this->send_data($res, 'error');
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Forum API.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function forum()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $args = func_get_args();
        $data = [];

        if(count($args) == 0)
            $args[0] = 'list';

        if($args[0] === 'list')
        {
            $limit = 100;
            if(count($args) > 1)
                $limit = intval($args[1]);

            $this->db->select('*')
                 ->where('status', 'VALID')
                 ->order_by('created_on DESC')
                 ->where('created_on >=', 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)', FALSE)
                 ->limit( $limit );

            $data = $this->db->get('forum')->result_array();

            // Convert all tags to a list and also collect number of comments.
            foreach($data as &$e)
            {
                $e['tags'] = explode(',', $e['tags']);
                $eid = 'forum.' . $e['id'];
                $this->db->select('id')->where(['external_id'=>$eid, 'status'=>'VALID']);
                $e['num_comments'] = $this->db->count_all_results('comment');
            }

            $this->send_data( $data, 'ok' );
            return;
        }
        else if( $args[0] === 'delete' )
        {
            $id = __get__($args, 1, -1);
            $this->db->set('status', 'DELETED')->where('id', $id);
            $this->db->update('forum');

            // Remove any notifications for this id.
            $this->db->where( 'external_id', 'forum.'.$id)
                ->set('status', 'INVALID')
                ->update('notifications');

            $this->send_data(['deleted' => $id], 'ok');
            return;
        }
        else if( $args[0] === 'subscribe')
        {
            $forumName = $args[1];
            $login = getLogin();
            User::subscribeToForum($this, $login, $forumName);
            $this->send_data(["Subscribed"], "ok");
            return;
        }
        else if( $args[0] === 'unsubscribe')
        {
            $forumName = $args[1];
            if($forumName == 'emergency') {
                $this->send_data(["Can't unscribe from emergency"], "ok");
                return;
            }
            $login = getLogin();
            User::unsubscribeToForum($this, $login, $forumName);
            $this->send_data(["Unsubscribed"], "ok");
            return;
        }
        else if( $args[0] === 'subscriptions')
        {
            $login = getLogin();
            $data = User::getBoardSubscriptions($this, $login);
            $this->send_data($data, "ok");
            return;
        }
        else if( $args[0] === 'post' )
        {

            // Unique id for the forum post.
            $id = __get__( $_POST, 'id', 0);
            $action = 'update';
            if( $id == 0 )
            {
                $this->db->select_max('id', 'maxid');
                $r = $this->db->get('forum')->result_array();
                $id = intval($r[0]['maxid'])+1;
                $action = 'new';
            }

            $createdBy = getLogin();
            $tags = implode(',', $_POST['tags']);

            // Commit to table.
            if($action === 'new')
            {
                $this->db->insert('forum'
                    , ['id'=>$id, 'created_by'=>$createdBy, 'tags'=>$tags
                        , 'title'=>$_POST['title']
                        , 'description'=>$_POST['description']
                    ]);
                $data['db_error'] = $this->db->error();

                // Post to FCM

                // Also add notifications for subscribed users.
                foreach( $_POST['tags'] as $tag)
                {
                    // Get the list of subscribers.
                    sendFirebaseCloudMessage($tag, $_POST['title'], $_POST['description']);

                    $subs = $this->db->select('login')
                        ->get_where('board_subscriptions'
                        , ['board' => $tag, 'status' => 'VALID' ])->result_array();

                    // Create notifications for each subscriber.
                    foreach($subs as $sub)
                    {
                        $this->db->insert("notifications"
                            , ['login'=>$sub['login'], 'title'=>$_POST['title']
                                , 'text' => $_POST['description'] 
                                , 'external_id' => 'forum.' . $id 
                            ]);
                    }
                }
            }
            else
            {
                $this->db->where('id', $id)
                    ->update('forum', ['tags'=>$tags
                        , 'title'=>$_POST['title']
                        , 'description'=>$_POST['description']
                    ]);
                $data['db_error'] = $this->db->error();
            }
            $this->send_data($data, 'ok');
            return;
        }
        else if( $args[0] === 'alltags' )
        {
            // fixme: This should be from database.
            $tags = explode(',', getConfigValue('ALLOWED_BOARD_TAGS'));
            sort($tags, SORT_STRING);
            $this->send_data($tags, 'ok' );
            return;
        }

        $data['status'] = "Invalid request " . $args[0];
        $this->send_data($data, 'ok' );
        return;
    }

    public function notifications()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $login = getLogin();
        $args = func_get_args();
        $data = [];
        if(count($args) == 0)
          $args[] = 'get';

        if( $args[0] === 'get')
        {
            $limit = __get__($args, 1, 10);
            $notifications = User::getNotifications($this, $login, $limit);
            $this->send_data($notifications, "ok");
            return;
        }
        else if($args[0] === 'dismiss' || $args[0] == 'markread')
        {
            $id = __get__($args, 1, 0);
            $this->db->where('id', $id)
                ->where( 'login', $login)
                ->update("notifications", ["is_read"=>true]);

            $this->send_data(["Marked read: $id"], "ok");
            return;
        }
        else if($args[0] == 'markunread')
        {
            $id = __get__($args, 1, 0);
            $this->db->where('id', $id)
                ->where( 'login', $login)
                ->update("notifications", ["is_read"=>false]);

            $this->send_data(["Marked unread: $id"], "ok");
            return;
        }
        else
          $this->send_data($data, "Unknown request");
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Menu management.
        *
        *     - /menu/list/(day=sun|mon|tue|wed|thu|fri|sat)
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function menu()
    {
        $user = getLogin();
        $args = func_get_args();

        if( $args[0] === 'list')
        {
            $day = __get__($args, 1, date('D', strtotime('today')));
            $available = getTableEntries( 'canteen_menu', 'canteen_name,which_meal,available_from'
                        , "status = 'VALID' AND day='$day'");
            $data['list'] = $available;
            $data['count'] = count($available);
            $canteens = executeQuery("SELECT DISTINCT canteen_name FROM canteen_menu WHERE status='VALID'");
            $canteens = array_map( function($x) { return $x['canteen_name']; }, $canteens);

            $meals = executeQuery("SELECT DISTINCT which_meal FROM canteen_menu WHERE status='VALID'");
            $meals = array_map( function($x) { return $x['which_meal']; }, $meals);

            $data['canteens'] = $canteens;
            $data['meals'] = $meals;
            $this->send_data( $data, 'ok');
            return;
        }

        // After this we need authentication.
        if(! authenticateAPI(getKey(), getLogin()))
        {
            $this->send_data(["Not authenticated"], "error");
            return;
        }
        else if($args[0] === 'create')
        {
            $_POST['modified_by'] = getLogin();
            $id = Adminservices::addToCanteenMenu( $_POST );
            $res = ['req' => json_encode($_POST), 'id' => $id];
            if($id > 0)
                $this->send_data($res,  'ok');
            else
                $this->send_data( ['Failed'],  'error');
            return;
        }
        else if($args[0] === 'update')
        {
            $res = Adminservices::updateCanteenItem($_POST);
            if($res)
                $this->send_data(['id'=>$_POST['id']],  'ok');
            else
                $this->send_data( ['Failed'],  'error');
            return;
        }
        else if($args[0] === 'delete')
        {
            $id = __get__($args, 1, 0);
            $res = Adminservices::deleteCanteenItem($id);
            if($res)
                $this->send_data(['id'=>$id],  'ok');
            else
                $this->send_data( ['Failed'],  'error');
            return;
        }
        else
            $this->send_data(['Unknown request ' . $args[0]],  'error');

        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Called should check the permissions.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function __commontasks() 
    {
        $args = func_get_args();
        if($args[0] === 'events')
        {
            $data = [];
            $subtask = $args[1];
            if($subtask === 'upcoming')
            {
                $from = intval(__get__($args, 2, 0));
                $to = intval(__get__($args, 3, 30));
                $limit = $to - $from; // these many groups
                $data = getEventsGID('today', 'VALID', $limit, $from);
                $this->send_data($data, "ok");
                return;
            }
            else if($subtask === 'cancel')
            {
                $gid = $args[2];
                $eid = __get__($args, 3, '');  // csv of eid
                $data = cancelEvents($gid, $eid, getLogin(), __get__($_POST,'reason',''));
                $this->send_data($data, "ok");
                return;
            }
            else if($subtask === 'gid')
            {
                $gid = $args[2];
                // All events by gid.
                $data = getEventsByGroupId($gid, 'VALID', 'today');
                $this->send_data($data, "ok");
                return;
            }
            else
            {
                $data['flash'] = "Unknown request " . $subtask;
                $this->send_data($data, "ok");
                return;
            }

            $this->send_data($data, "ok");
            return;
        }
        else if($args[0] === 'table')
        {
            if($args[1] === 'fieldinfo') 
            {
                $data = getTableFieldInfo($args[2]);
                $this->send_data($data, "ok");
                return;
            }
            else if($args[1] === 'types') 
            {
                $ctypes = getTableColumnTypes($args[2], $args[3]);
                $this->send_data($ctypes, "ok");
                return;
            }
        }
        else if($args[0] === 'event')
        {
            $data = [];
            $subtask = __get__($args, 1, 'update');
            if($subtask === 'delete' || $subtask === 'cancel') 
            {
                $_POST['status'] = 'CANCELLED';
                $subtask = 'update';
            }

            if($subtask === 'update')
            {
                $res = updateEvent($_POST['gid'], $_POST['eid'], $_POST);
                $data['flash'] = 'successfully updated';
            }
            else
                $data['flash'] = "Unknown request " . $subtask;
            $this->send_data($data, "ok");
            return;
        }
        else if($args[0] === 'email')
        {
            if($args[1] === 'talk')
            {
                $tid = intval(__get__($args, 2, '-1'));
                if($tid < 0) {
                    $this->send_data(
                        ['success' => false, 'msg'=>"Invalid talk id $tid"]
                        , "ok");
                    return;
                }
                $email = talkToEmail($tid);
                $this->send_data($email, 'ok');
                return;
            }
            else if($args[1] === 'post') 
            {
                $data = $_POST;
                $res = sendHTMLEmail($data['email_body'], $data['subject']
                    , $data['recipient'], $data['cc'], $data['attachments'] 
                );
                $this->send_data($res, 'ok');
                return;
            }
            else 
            { 
                $this->send_data(['msg' => 'Unknown Request', 'success'=>false], "ok");
                return;
            }
        }
        else if($args[0] === 'speaker')
        {
            if($args[1] === 'fetch')
            {
                // Fetch speaker.
                $speakerId = intval($args[2]);
                if( $speakerId < 0) {
                    $this->send_data(
                        ['success'=>false, 'msg'=>"Invalid speaker ID" . $speakerId]
                        , "ok");
                    return;
                 }

                $data = getSpeakerByID($speakerId);
                $picpath = getSpeakerPicturePath($speakerId);
                $photo = '';
                if(file_exists($picpath))
                    $photo = base64_encode(file_get_contents($picpath));
                $data['photo'] = $photo;
                $this->send_data($data, "ok");
                return;
            }
            if($args[1] === 'update' || $args[1] === 'new')
            {
                // Updating speaker.
                $res = addUpdateSpeaker($_POST);
                $this->send_data($res, "ok");
                return;
            }
            if($args[1] === 'delete')
            {
                // Delete speakers only if there is no valid talks associated
                // with it.
                $speakerID = intval(__get__($args, 2, '-1'));
                if($speakerID < 0) {
                    $this->send_data(['success'=>false, 'msg'=>'Invalid speaker ID'], "ok");
                    return;
                }
                $talks = getTableEntries('talks', 'id'
                    , "status='ACTIVE' and speaker_id='$speakerID'"
                );
                if($talks && count($talks) > 0) {
                    $this->send_data(['success'=>false, 'msg'=>'Speaker has valid talks.'], "ok");
                    return;
                }
                $res = deleteFromTable('speakers', 'id', ['id'=>$speakerID]);
                $res = deleteFromTable('talks', 'speaker_id', ['speaker_id'=>$speakerID]);
                $this->send_data(['success'=>$res, 'msg'=>'Successfully deleted'], "ok");
                return;
            }
            else
            {
                $this->send_data([
                    'msg' => 'Unknown Request ' . json_encode($args)
                    , 'success'=>false], "ok");
                return;
            }
        }
        else
        {
            $this->send_data(['success' => false, 'msg' => $args], "ok");
            return;
        }
    }

    // BMV ADMIN
    public function bmvadmin()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $login = getLogin();
        if(! in_array('BOOKMYVENUE_ADMIN', getRoles($login)))
        {
            $this->send_data([], "Forbidden");
            return;
        }

        $args = func_get_args();
        if($args[0] === 'requests')
        {
            $data = [];
            $subtask = __get__($args, 1, 'pending');
            if($subtask === 'pending')
                $data = getPendingRequestsGroupedByGID();
            else if($subtask === 'date')
                $data = getPendingRequestsOnThisDay($args[2]);
            else
                $data = ['flash' => 'Unknown request'];
            $this->send_data($data, "ok");
            return;
        }
        else if($args[0] === 'request')
        {
            $data = [];
            $subtask = __get__($args, 1, 'status');
            if($subtask === 'clash')
            {
                $jcLabmeets = getLabmeetAndJC();
                $jcOrLab = clashesOnThisVenueSlot($_POST['date'], $_POST['start_time']
                    , $_POST['end_time'], $_POST['venue']
                    , $jcLabmeets);
                $data['clashes'] = $jcOrLab;
            }
            else if($subtask === 'approve')
            {
                // Mark is a public event, if admin says so.
                if($_POST['is_public_event'] === "YES")
                    updateTable('bookmyvenue_requests', 'gid,rid', 'is_public_event', $_POST);

                $ret = actOnRequest($_POST['gid'], $_POST['rid'], 'APPROVE', true, $_POST);
                $data['msg'] = 'APPROVED';
            }
            else if($subtask === 'reject')
            {
                $ret = actOnRequest($_POST['gid'], $_POST['rid'], 'REJECT', true, $_POST);
                $data['msg'] = 'REJECTED';
            }
            else
                $data = ['flash' => 'Unknown request'];

            // Send final data.
            $this->send_data($data, "ok");
            return;
        }
        else if($args[0] === 'events')
            return $this->__commontasks(...$args);
        else if($args[0] === 'event')
            return $this->__commontasks(...$args);
        else
        {
            $this->send_data(['flash' => 'Unknown Request'], "ok");
            return;
        }
    }

    // Common admin tasks.
    public function admin()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $login = getLogin();
        $roles = getRoles($login);
        if(! (in_array('ACAD_ADMIN', getRoles($login)) 
            || in_array('BOOKMYVENUE_ADMIN', $roles))) 
        {
            $this->send_data([], "Forbidden");
            return;
        }

        $args = func_get_args();
        $data = [];
        if($args[0] === 'talk')
        {
            $endpoint = __get__($args, 1, 'list');
            if($endpoint === 'list')
            {
                $limit = __get__($args, 2, 100);

                // Don't fetch any talk created 6 months ago.
                $talks = getTableEntries('talks', 'created_on DESC'
                    , "status!='INVALID' AND created_on > DATE_SUB(now(), INTERVAL 6 MONTH)"
                    , '*', $limit); 

                $data = [];
                foreach($talks as &$talk) {
                    $event = getEventsOfTalkId($talk['id']);
                    // if event was in past then don't use this talk;
                    if($event) {
                        if(strtotime($event['date']) < strtotime('yesterday'))
                            continue;
                        $talk['event'] = $event;
                    }
                    $req = getBookingRequestOfTalkId($talk['id']);
                    if($req)
                        $talk['request'] = $req;
                    $data[] = $talk;
                }
                $this->send_data($talks, 'ok');
                return;
            }
            else if($endpoint === 'get')
            {
                $tid = intval(__get__($args, 2, '-1'));

                if($tid < 0) 
                {
                    $this->send_data(["Invalid talk id $tid."], 'ok');
                    return;
                }

                // Get talks only in future.
                $talk = getTableEntry('talks', 'id,status'
                    , ['id'=>$tid, 'status'=>'VALID']);
                $event = getEventsOfTalkId($talk['id']);
                if($event)
                    $talk['event'] = $event;
                $req = getBookingRequestOfTalkId($talk['id']);
                if($req)
                    $talk['request'] = $req;
                $this->send_data($talk, 'ok');
                return;
            }
            else if($endpoint === 'delete')
            {
                $res = updateThisTalk($_POST);
                $this->send_data($res, 'ok');
                return;
            }
            else if($endpoint === 'email')
            {
                $tid = $args[2];
                $email = talkToEmail($tid);
                $this->send_data($email, 'ok');
                return;
            }
            else
            {
                $this->send_data(["Unknown request"], "ok");
                return;
            }
        }
        else if($args[0] === 'event') 
            return $this->__commontasks(...$args);
        else if($args[0] === 'speaker')
            return $this->__commontasks(...$args);
        else if($args[0] === 'table')
            return $this->__commontasks(...$args);

        // NOTE: Usually admin can not approve requests; he can do so for some
        // requests associated with talks. 
        else if($args[0] === 'request') 
        {
            if($args[1] === 'cancel' || $args[1] === 'delete')
            {
                $res = changeRequestStatus( $request[ 'gid' ]
                    , $request[ 'rid' ], $request[ 'created_by' ]
                    , 'CANCELLED');
                $this->send_data(['status'=>$res], "ok");
                return;
            }
            else if($args[1] === 'approve')
            {
                // make sure that it is a PUBLIC EVENT.
                $ret = actOnRequest($_POST['gid'], $_POST['rid'], 'APPROVE', true, $_POST);
                $this->send_data($ret, "ok");
                return;
            }
            else
            {
                $data = ['flash' => 'Unknown request'];
                $this->send_data($data, "ok");
                return;
            }
        }
        $this->send_data(["Unknown request: ", $args], "ok");
        return;
    }

    // Admin acad
    public function acadadmin()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $login = getLogin();
        if(! in_array('ACAD_ADMIN', getRoles($login)))
        {
            $this->send_data([], "Forbidden");
            return;
        }

        $args = func_get_args();
        $data = [];
        if($args[0] === 'aws')
        {
            if($args[1] === 'upcoming')
            {
                $awses = getUpcomingAWS();
                foreach($awses as &$aws)
                {
                    $aws['by'] = loginToHTML($aws['speaker']);
                    $data[$aws['date']][]=$aws;
                }
                $this->send_data($data, 'ok');
                return;
            }
            else if($args[1] === 'assign')
            {
                $_POST['venue'] = __get__($_POST, 'venue'
                    , getDefaultAWSVenue($_POST['date'])
                );
                $data = assignAWS($_POST['speaker'], $_POST['date'], $_POST['venue']);
                $this->send_data($data, 'ok');
                return;
            }
            else if($args[1] === 'cancel')
            {
                $data = cancelAWS($_POST, getLogin());
                $this->send_data($data, 'ok');
                return;
            }
            else if($args[1] === 'update')
            {
                $data = updateAWS($_POST, getLogin());
                $this->send_data($data, 'ok');
                return;
            }
            else
            {
                $this->send_data(["Unknown request"], "ok");
                return;
            }
        }
        else if($args[0] === 'course')
        {
            assert($args[1]) or die("/course/x requires a valid 'x'");

            if($args[1] === 'registration')
            {
                // We are sending base64 encoded string because course id can have
                $data = $_POST;
                $course = getRunningCourseByID($data['course_id']
                    , $data['year'], $data['semester']);

                assert($course) or die("No valid course is found.");
                assert(__get__($args, 2,'')) or die("Empty TYPE ");

                // Do not send email when using APP.
                $res = handleCourseRegistration($course, $data, $args[2]
                    , $data['student_id'], getLogin());

                $this->send_data($res, 'ok');
                return;
            }
            if($args[1] === 'grade')
            {
                // We are sending base64 encoded string because course id can have
                // Do not send email when using APP.
                assert(isset($_POST['student_id'])) or die('No valid student id');
                $res = assignGrade($_POST, getLogin());
                $this->send_data($res, 'ok');
                return;
            }
        }
        else if($args[0] === 'awsroster')
        {
            if($args[1] === 'fetch')
            {
                $speakers = getAWSSpeakers($sortby='pi_or_host');
                foreach($speakers as &$speaker)
                {
                    if(! $speaker)
                        continue;
                    $extraInfo = getExtraAWSInfo($speaker['login'], $speaker);
                    $speaker = array_merge($speaker, $extraInfo);
                }
                $this->send_data($speakers, 'ok');
                return;
            }
            else if($args[1] === 'remove')
            {
                $whom = urldecode($args[2]);
                assert($whom);
                $res = removeAWSSpeakerFromList($whom, __get__($_POST, 'reason',''));
                $this->send_data($res, 'ok');
                return;
            }
            else if($args[1] === 'add')
            {
                $whom = urldecode($args[2]);
                $res = addAWSSpeakerToList($whom);
                $this->send_data($res, 'ok');
                return;
            }
            else if($args[1] === 'available_dates')
            {
                $numDates = intval(__get__($args, 2, 5)); // get next 5 slots.
                $datesAvailable = awsDatesAvailable($numDates);
                $this->send_data($datesAvailable, "ok");
                return;
            }
            else
            {
                $this->send_data(["Unknown request"], "ok");
                return;
            }
        }
        else if($args[0] === 'reschedule')
        {
            rescheduleAWS();
            $this->send_data(['success'=>true, 'msg'=> "Reschedule OK."], "ok");
            return;
        }
        $this->send_data(["Unknown request"], "ok");
        return;
    }


    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  Handles people related queries and post.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function people()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $args = func_get_args();
        if($args[0] === 'speaker')
        {
            if($args[1] === 'add')
            {
                $name = splitNameIntoParts($_POST['name']);
                $_POST = array_merge($_POST, $name);
                $ret = addUpdateSpeaker($_POST);
                $this->send_data($ret, 'ok');
                return;
            }
        }
        $this->send_data(["Unknown request"], "ok");
        return;
    }

    /* --------------------------------------------------------------------------*/
    /**
        * @Synopsis  TALK api.
        *
        * @Returns   
     */
    /* ----------------------------------------------------------------------------*/
    public function talk()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }
        $args = func_get_args();

        if($args[0] === 'get')
        {
            $talkid = $args[1];
            $data = getTalkWithBooking($talkid, getLogin());
            $this->send_data($data, "ok");
            return;
        }
        else if($args[0] === 'update')
        {
            $data = updateThisTalk($_POST);
            $this->send_data($data, "ok");
            return;
        }
        else if($args[0] === 'remove' || $args[0] == "cancel")
        {
            $data = removeThisTalk($args[1], getLogin());
            $this->send_data($data, "ok");
            return;
        }
        $this->send_data(['msg'=>"Unknown request", 'status'=>false], "ok");
        return;
    }

    // Emails.
    public function email()
    {
        if(! authenticateAPI(getKey()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        $args = func_get_args();
        return $this->__commontasks('email', ...$args);
    }
}

?>
