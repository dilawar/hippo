<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__.'/ApiHelper.php';
require_once __DIR__.'/Adminservices.php';
require_once __DIR__.'/User.php';

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

    // To enable CORS.
    public function __construct($config = 'rest')
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: login, hippo-api-key, x-requested-with, cache-control, Content-Type');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        parent::__construct();
    }

    private function send_data_helper(array $data)
    {
        try
        {
            $json = json_encode($data);
        } 
        catch ( Exception $e )
        {
            $json = $e->getMessage();
        }
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
        *   - /venue/book/book/venueid/startDateTime/endDateTime
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
            $type = __get__($args, 1, 'all');
            $data = getVenuesByType($type);
            $this->send_data($data, "ok");
            return;
        }

        // Rest of endpoints needs authentication data.
        if(! authenticateAPI(getKey(), getLogin()))
        {
            $this->send_data([], "Not authenticated");
            return;
        }

        if($args[0] === 'info')
        {
            $id = __get__($args, 1, 0);
            $data = getVenueById($id);
            $this->send_data($data, 'ok');
            return;
        }

        if($args[0] === 'status')
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

        if($args[0] === 'book')
        {
            $this->bookVenue($args[1], intval($args[2]), intval($args[3]));
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
    private function bookVenue(string $venueId, int $startDateTime, int $endDateTime)
    {
        if( (! $venueId) || ($startDateTime >= $endDateTime))
        {
            $data = ['msg' => "Invalid request: $venueId $startDateTime $endDateTime."];
            $this->send_data($data, "error" );
            return;
        }

        $request = array_merge($_POST
            , ['venue'=>$venueId
            , 'date' => dbDate($startDateTime)
            , 'start_time' => dbTime($startDateTime)
            , 'end_time' => dbTime($endDateTime)]
        );

        $ret = submitBookingRequest( $request );

        $status = $ret['success']?'ok':'error';
        $ret['payload'] = json_encode($request);
        $this->send_data( $ret, $status);
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

        $this->send_data( ['apikey'=>$token, 'gmapapikey'=>$gmapkey
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
        *     - /api/config/bookmyvenue.class
        *     - /api/config/evnet.class 
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
        if( __get__($args, 0, '') )
        {
            $id = $args[0];
            $data = getTableEntry( 'config', 'id', ["id"=>$id]);
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
            $login = $_POST['login'];
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
                    $this->send_data( ["Request $gid.$rid is deleted"], $res?"ok":"failed");
                    return;
                }
                else
                {
                    // delete the whole group.
                    $res = changeStatusOfRequests($gid, 'CANCELLED');
                    $this->send_data(["Request group $gid is deleted"], $res?"ok":"failed");
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
                    $this->send_data( ["Event $gid.$eid is cancelled"], $res?"ok":"failed");
                    return;
                }
                else
                {
                    // delete the whole group.
                    $res = changeStatusOfEventGroup($gid, $login, 'CANCELLED');
                    $this->send_data(["Event group $gid is cancelled."], $res?"ok":"failed");
                    return;
                }
            }
            else
            {
                $this->send_data( ["Not implemented"], "ok");
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
            $where .= " AND drop_point='$drop_point' ";
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
        *   - /me/aws
        *   - /me/jc
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

        if( $args[0] === 'profile')
        {
            $ldap = getUserInfo($user, true);
            $remove = ['fname', 'lname', 'uid', 'is_active', 'honorific', 'roles'
                , 'valid_until', 'created_on'
                ];
            $data = array_diff_key($ldap, array_flip($remove));
        }
        else if( $args[0] === 'aws')
        {
            $upcoming = getUpcomingAWSOfSpeaker($user);
            if($upcoming)
                $data[] = $upcoming;
            $data = getAwsOfSpeaker($user);
        }
        //else if( $args[0] === 'jc')
        //{
        //    $upcoming = getJCPresentations($login);
        //    if($upcoming)
        //        $data[] = $upcoming;
        //    $data = getAwsOfSpeaker($login);
        //}
        else
            $data = ['Unknown query'];

        $this->send_data($data, 'ok');
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
            $id = getUniqueID( 'accomodation' );
            $_POST['id'] = $id;
            $_POST['status'] = 'AVAILABLE';
            $_POST['created_by'] = getLogin();
            $_POST['created_on'] = dbDateTime( 'now' );

            $res = insertIntoTable( 'accomodation'
                , 'id,type,available_from,available_for,open_vacancies,address,description'
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

            foreach(explode(',', 'session_num,altitude,accuracy,heading,speed') as $key)
                $_POST[$key] = __get__( $_POST, $key, '');

            // 10 Km/Hr = 2.77 m/s
            if(
                floatVal($_POST['latitude']) <= 0 || floatVal($_POST['longitude']) <= 0.0 
                //|| floatVal($_POST['speed']) <= 1.0
            )
            {
                $this->send_data( ["Invalid or not moving."], "warn");
                return;
            }

            $res = insertIntoTable( 'geolocation'
                , 'latitude,longitude,altitude,accuracy,heading,speed,session_num,crypt_id'
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
            $limit = intval(__get__($args, 1, 100));

            // Get last 30 points (doen't matter when)
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
        if( count($args) == 0 )
            $args[0] = 'images';

        $res = [];
        if( $args[0] == 'images')
        {
            $invId = intval(__get__($_POST, 'inventory_id', -1));
            if($invId < 0)
            {
                $this->send_data($res, "Inventory ID is not found.");
                return;
            }

            $storeFolder = getUploadDir();
            if (!empty($_FILES)) 
            {
                $tempFile = $_FILES['file']['tmp_name'];          
                $md5 = md5_file( $tempFile );
                $filename = $md5 . $_FILES['file']['name'];
                $targetFile =  $storeFolder . "/$filename";

                $res['stored'] = move_uploaded_file($tempFile, $targetFile); 

                // Add this value to database.
                $this->db->select_max('id', 'maxid');
                $r = $this->db->get('images')->result_array();
                $id = $r[0]['maxid'];

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
            $this->send_data($res, 'error');
        }
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

}

?>
