<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/ApiHelper.php';
require_once __DIR__ . '/Adminservices.php';
require_once __DIR__ . '/User.php';

require_once BASEPATH . '/extra/bmv.php';
require_once BASEPATH . '/extra/people.php';
require_once BASEPATH . '/extra/search.php';
require_once BASEPATH . '/extra/acad.php';
require_once BASEPATH . '/extra/talk.php';
require_once BASEPATH . '/extra/acad.php';
require_once BASEPATH . '/extra/services.php';
require_once BASEPATH . '/extra/me.php';
require_once BASEPATH . '/extra/charts.php';
require_once BASEPATH . '/extra/covid19.php';

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Authenticate a given user with given key.
 *
 * @Param $apikey
 * @Param $user
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function authenticateAPI($apikey, $user = '')
{
    $where = 'apikey';
    if ($user) {
        $where .= ',login';
    }

    $res = getTableEntry('apikeys', $where, ['apikey' => $apikey, 'login' => $user]);
    if ($res) {
        return true;
    }

    return false;
}

function getKey()
{
    // NOTE: headers are case insensitive. So getHeader function must search
    // for both cases.
    return __get__($_POST, 'HIPPO-API-KEY', getHeader('HIPPO-API-KEY'));
}

function hasRoles($whichRoles): bool
{
    $roles = getRoles(getLogin());
    foreach (explode(',', $whichRoles) as $r1) {
        foreach ($roles as $r2) {
            if ($r1 === $r2) {
                return true;
            }
        }
    }

    return false;
}

class Api extends CI_Controller
{
    // To enable CORS just for this API. DO NOT CHANGE THEM IN apache2.conf or
    // httpd.conf file.
    public function __construct($config = 'rest')
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: cache-control,hippo-login,login,hippo-api-key,x-requested-with,Content-Type');
        header('Access-Control-Allow-Methods: GET,POST,OPTIONS,PUT,DELETE');
        parent::__construct();
    }

    private function send_data_helper(array $data)
    {
        $json = json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_ERROR_SYNTAX | JSON_NUMERIC_CHECK
            | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $this->output->set_content_type('application/json', 'utf-8');
        $this->output->set_output($json);
    }

    public function get_without_auth(string $what)
    {
        $this->send_data($what);
    }

    private function send_data(array $data, string $status = 'ok')
    {
        $this->send_data_helper(['status' => $status, 'data' => $data]);
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Status of Hippo API.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function status()
    {
        $this->send_data(['status' => 'alive'], 'ok');
    }

    // Helper function for process() function.
    private function process_events_requests($args)
    {
        // all dates are unix timestamp.
        $events = [];
        $status = 'ok';
        if ('date' === $args[0]) {
            $from = intval(__get__($args, 1, strtotime('today')));
            $to = intval(__get__($args, 2, strtotime('+1 day', $from)));
            $from = dbDate($from);
            $to = dbDate($to);
            $events = getAllBookingsBetweenTheseDays($from, $to);
        } elseif ('latest' === $args[0]) {
            // We'll get twice as many events. Because we fetch requests as
            // well.
            $numEvents = intval(__get__($args, 1, 100)) / 2;
            $startFrom = intval(__get__($args, 2, 0));
            $events = getNumBookings($numEvents, $startFrom);
        } elseif ('class' === $args[0]) {
            $this->send_data('ok', $dbChoices['events.class']);
        } elseif ('get' === $args[0]) {
            $gid = $args[1];
            $eid = __get__($args, 2, '');
            $events = $eid ? getEventsById($gid, $eid) : getEventsByGroupId($gid);
        } else {
            $status = 'error';
            $events['msg'] = 'Unknow request: ' . $args[0];
        }

        $this->send_data($events, $status);
    }

    // Get charts.
    public function charts()
    {
        $args = func_get_args();

        // // Only need api key
        // if(! authenticateAPI(getKey())) {
        //     $this->send_data([], "Not authenticated");
        //     return;
        // }

        if ('all' === $args[0]) {
            $charts = getCharts();
            $this->send_data($charts, 'ok');

            return;
        }
    }

    public function pub()
    {
        $args = func_get_args();

        if( 'holidays_' === $args[0] || 'holidays' === $args[0]){
            $holidays = getHolidaysOfYear(getCurrentYear(), true);
            $data = array_values($holidays);
            $footnotes = [];

            if('holidays_' === $args[0])
            {
                foreach($holidays as &$holiday) {
                    $comment = trim($holiday['comment']);
                    if($comment) {
                        if(! in_array($comment, $footnotes))
                            $footnotes[] = $comment;
                        $holiday['description'] = $holiday['description'] 
                            . '<sup>' . (count($footnotes)) . '</sup>';
                    }
                }
                $data = [ 'holidays'=>array_values($holidays)
                    , 'footnotes'=>$footnotes];
            }

            $what = __get__($args, 1, 'json');
            if ('json' === $what) {
                $this->send_data_helper($data);

                return;
            }
            if ('html' === $what) {
                $class = 'table ncbs-holiday';
                $hide = 'is_public_holiday,schedule_talk_or_aws,comment';
                if('holidays_' === $args[0]) {
                    $holidays = $data['holidays'];
                    $html = "<table class='$class'>";
                    $html .= "<tr><th>Holiday</th><th>Date</th><th>Day of the week</th></tr>";
                    foreach ($holidays as $holiday) {
                        $desc = $holiday['description'];
                        $date = date('F j, Y', strtotime($holiday['date']));
                        $day = date('D', strtotime($date));
                        $row = "<tr><td>$desc</td><td>$date</td><td>$day</td></tr>";
                        $html .= $row;
                    }
                    $html .= '<tfoot>';
                    foreach($footnotes as $i => $foot) {
                        $html .= "<tr><td colspan=4><sup>" . ($i+1) . "</sup>$foot</td></tr>";
                    }
                    $html .= '</tfoot>';
                    $html .= '</table>';
                }
                else
                {
                    $html = arraysToCombinedTableHTML($data, 'table ncbs-holiday'
                        , 'is_public_holiday,schedule_talk_or_aws');
                }
                $this->output->set_content_type('text/html', 'utf-8');
                $this->output->set_output($html);
                return;
            }
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Get various info.
     *    - /info/news/latest
     *    - /info/news
     *    - /info/venues/available/[all|venueid]
     *    - /info/bmv/bookingclasses
     *    - /info/slot/
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function info()
    {
        $args = func_get_args();

        // Only need api key
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        if ('flashcards' === $args[0]) {
            $data = getPublicEventsOnThisDay('today');
            $data = array_filter(
                $data,
                function ($ev) {
                    return strtotime($ev['end_time']) >= strtotime('now');
                }
            );
            $tom = getPublicEventsOnThisDay('tomorrow');
            $data = array_merge($tom, $data);
            $cards = [];
            foreach ($data as $item) {
                $cards[] = ['title' => $item['title'], 'date' => $item['date'], 'time' => $item['start_time'], 'venue' => venueToShortText($item['venue'], __get__($item, 'vc_url', '')),
                ];
            }

            // AWS cards.
            $awses = getUpcomingAWSOnThisMonday('this monday');
            if ($awses) {
                $faws = $awses[0];
                $title = 'AWS by ';
                foreach ($awses as $aws) {
                    $speaker = getLoginInfo($aws['speaker']);
                    $title .= arrayToName($speaker) . ', ';
                }
                $cards[] = ['title' => $title, 'date' => $faws['date'], 'time' => $faws['time'], 'venue' => venueToShortText($faws['venue'], $faws['vc_url']),
                ];
            }

            $this->send_data($cards, 'ok');

            return;
        } elseif ('venues' === $args[0]) {
            if ('availability' === $args[1]) {
                $data = getVenuesWithStatusOnThisDayAndTime(
                    $_POST['date'],
                    $_POST['start_time'], $_POST['end_time']
                );
                $this->send_data($data, 'ok');

                return;
            }
        } elseif ('externalid' === $args[0]) {
            $externalID = explode('.', $args[1]);
            if (2 != count($externalID)) {
                $this->send_data([], 'ok');

                return;
            }
            $tableName = $externalID[0];
            $id = $externalID[1];
            $info = getTableEntry(
                $tableName, 'id,status',
                ['id' => $id, 'status' => 'VALID']
            );
            $this->send_data($info, 'ok');

            return;
        } elseif ('bmv' === $args[0]) {
            if ('bookingclasses' === $args[1]) {
                $public = getTableEntry('config', 'id', ['id' => 'BOOKMYVENUE.CLASS']);
                $public = explode(',', __get__($public, 'value', ''));

                $nopublic = getTableEntry('config', 'id', ['id' => 'BOOKMYVENUE.NOPUBLIC.CLASS']);
                $nopublic = explode(',', __get__($nopublic, 'value', ''));

                $all = array_unique(array_merge($public, $nopublic));

                $this->send_data(['all' => $all, 'public' => $public, 'nonpublic' => $nopublic], 'ok');

                return;
            }
        } elseif ('aws_schedule' === $args[0]) {
            $awses = getTentativeAWSSchedule();
            $data = [];
            foreach ($awses as &$aws) {
                $info = getExtraAWSInfo($aws['speaker']);
                $aws = array_merge($aws, $info);
                $data[$aws['date']][] = $aws;
            }
            $this->send_data($data, 'ok');

            return;
        } elseif ('repeatpat' === $args[0]) {
            $pat = base64_decode($args[1]);
            $data = repeatPatToDays($pat);
            $this->send_data($data, 'ok');

            return;
        } elseif ('slot' === $args[0]) {
            $id = __get__($args, 1, 'all');
            if ('all' === $id) {
                $slot = getSlots();
                $this->send_data($slot, 'ok');
            } else {
                $slot = getSlotInfo($id);
                $this->send_data($slot, 'ok');
            }

            return;
        } elseif ('talks' === $args[0]) {
            // next 7 days.
            $numDays = __get__($args, 1, 14);
            $talks = getTalksWithEvent(dbDate('today'), dbDate("+$numDays days"));
            $this->send_data($talks, 'ok');

            return;
        } elseif ('upcomingaws' === $args[0]) {
            $awses = getUpcomingAWS('today');
            $data = [];
            foreach ($awses as &$aws) {
                $logins = findAnyoneWithLoginOrEmail($aws['speaker']);
                if ($logins) {
                    $by = arrayToName($logins[0]);
                } else {
                    $by = $aws['speaker'];
                }
                $aws['by'] = $by;
                $data[$aws['date']][] = $aws;
            }

            $dates = array_keys($data);
            for($k = strtotime($dates[0]); $k <= strtotime($dates[array_key_last($dates)]); $k += 7*86400) {
                $awsDay = dbDate($k);
                // On these dates, no AWS. possibly holiday.
                if(! in_array($awsDay, $dates))
                    $data[$awsDay] = [[]];
            }

            $this->send_data($data, 'ok');

            return;
        } elseif ('jcs' === $args[0]) {
            $jcs = getUpcomingJCPresentations();
            foreach ($jcs as &$jc) {
                $login = findAnyoneWithEmail($jc['presenter']);
                $by = arrayToName($login);
                if (!$by) {
                    $by = $jc['presenter'];
                }
                $jc['by'] = $by;
            }
            $this->send_data($jcs, 'ok');

            return;
        } elseif ('courses' === $args[0]) {
            $courses = getRunningCourses();
            foreach ($courses as &$course) {
                $course['name'] = getCourseName($course['id']);
            }
            $this->send_data($courses, 'ok');

            return;
        } elseif ('table' === $args[0]) {
            if ('fieldinfo' === $args[1]) {
                $data = getTableFieldInfo($args[2]);
                $this->send_data($data, 'ok');

                return;
            } elseif ('types' === $args[1]) {
                $ctypes = getTableColumnTypes($args[2], $args[3]);
                $this->send_data($ctypes, 'ok');

                return;
            }
        } elseif( 'holiday' === $args[0] || 'holidays' === $args[0]){
            if ('list' === $args[1]) {
                $holidays = getHolidaysOfYear();
                $this->send_data($holidays, 'ok');

                return;
            }
        }
        $this->send_data(['success'=>false
            , 'msg'=>'Unknown request /info/' . json_encode($args)]
            , 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Search API.
     *
     * @Returns Send array only. Do
     */
    /* ----------------------------------------------------------------------------*/
    public function search()
    {
        $args = func_get_args();

        // Only need api key
        $key = getKey();
        if (!$key) {
            $this->send_data(['status' => false, 'msg' => 'Not authenticated. Empty key'], 401);

            return;
        }

        if (!authenticateAPI(getKey())) {
            $this->send_data(['status' => false, msg => 'Bad key. Did you login?'], 401);

            return;
        }

        $q = urldecode($args[1]);
        if ('awsspeaker' === $args[0]) {
            $logins = searchInLogins($q, "AND eligible_for_aws='YES'");
            $this->send_data_helper($logins);

            return;
        } elseif ('speaker' === $args[0]) {
            $speakers = searchInSpeakers($q);
            foreach ($speakers as &$speaker) {
                $speaker['email'] = __get__($speaker, 'email', '');
                $speaker['html'] = speakerToHTML($speaker);
            }
            $this->send_data_helper($speakers);

            return;
        } elseif ('host' === $args[0]) {
            $speakers = searchInSpeakers($q);
            $faculty = searchInFaculty($q);
            $this->send_data_helper(array_merge($speakers, $faculty));

            return;
        } elseif ('faculty' === $args[0]) {
            $faculty = searchInFaculty($q);
            $this->send_data_helper($faculty);

            return;
        } elseif ('supervisor' === $args[0]) {
            $faculty = searchInFaculty($q);
            $supers = searchSupervisors($q);
            $this->send_data_helper(array_merge($faculty, $supers));

            return;
        } elseif ('login' === $args[0]) {
            $logins = searchInLogins($q);
            $this->send_data_helper($logins);

            return;
        } elseif ('talks' === $args[0]) {
            $talks = searchInTalks($q);
            $this->send_data_helper($talks);

            return;
        }

        $this->send_data(['Unsupported query']);
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Course related API.
     *
     *    - /courses/running|list
     *    - /courses/register/course_id/[CREDIT,AUDIT,DROP]
     *    - /courses/feedback/questions
     *       Return questions for feedback.
     *    - /courses/feedback/submit
     *    - /courses/metadata
     *    - /courses/registration
     *       Return metadata for all courses.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function courses()
    {
        // Only need api key
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $args = func_get_args();
        if (0 == count($args)) {
            $args[] = 'list';
        }

        if ('running' === $args[0] || 'list' === $args[0]) {
            $year = __get__($args, 1, getCurrentYear());
            $semester = __get__($args, 2, getCurrentSemester());
            $data = getSemesterCourses($year, $semester);

            // For convinience, let user know if he/she can register for this
            // course.
            $this->send_data($data, 'ok');

            return;
        } elseif ('register' === $args[0]) {
            $data = ['type' => strtoupper($args[2])];
            $data['student_id'] = getLogin();
            assert($args[1]);

            // We are sending base64 encoded string because course id can have
            // banned characters e.g. '&' in B&B
            $fs = splitAt(base64_decode($args[1]), '-');
            assert(3 == count($fs));

            $course = getRunningCourseByID($fs[0], $fs[2], $fs[1]);
            if (!$course) {
                $res['success'] = false;
                $res['msg'] = 'Could not find a valid course: ' + implode('-', $fs);
                $this->send_data($res, 'ok');
            }

            // Do not send email when using APP.
            $res = handleCourseRegistration($course, $data, $data['type'], getLogin(), getLogin());

            if ($res['success']) {
                $this->send_data($res, 'ok');
            } else {
                $this->send_data($res, 'error');
            }

            $this->send_data($res, 'ok');

            return;
        } elseif ('metadata' === $args[0]) {
            $cids = base64_decode(__get__($args, 1, base64_encode('all')));
            if ('all' === $cids) {
                $data = [];
                $metadata = getTableEntries('courses_metadata', 'id', "status='VALID'");
                foreach ($metadata as $m) {
                    $m['instructors'] = getCourseInstructors($m['id']);
                    $data[$m['id']] = $m;
                }
            } else {
                $data = [];
                foreach (explode(',', $cids) as $cid) {
                    $data[$cid] = getCourseById($cid);
                }
            }

            ksort($data);
            $this->send_data($data, 'ok');

            return;
        } elseif ('registration' === $args[0]) {
            $crs = explode('-', base64_decode($args[1]));
            $data = getCourseRegistrationsLight($crs[0], intval($crs[2]), $crs[1]);
            $this->send_data($data, 'ok');

            return;
        } elseif ('feedback' === $args[0]) {
            $data = [];
            $request = __get__($args, 1, '');
            if ('questions' === $request) {
                $data = getCourseFeedbackQuestions();
                $this->send_data($data, 'ok');

                return;
            } elseif ('get' === $request) {
                $fs = explode('-', base64_decode($args[2]));
                assert(3 == count($fs));
                $data = getCourseFeedback($fs[2], $fs[1], $fs[0], getLogin());
                $this->send_data($data, 'ok');

                return;
            } elseif ('getthis' === $request) {
                $year = $_POST['year'];
                $semester = $_POST['semester'];
                $cid = $_POST['course_id'];
                $instructor_email = $_POST['instructor_email'];
                $login = getLogin();
                $data = getCourseThisFeedback($year, $semester, $cid, $login, $instructor_email);
                $this->send_data($data, 'ok');

                return;
            } elseif ('submit' === $request) {
                $data = $_POST;
                $data['login'] = getLogin();
                $res = submitThisFeedback($data);
                $this->send_data($res, 'ok');

                return;
            }

            $data = ["Unsupported request: $request"];
            $this->send_data($data, 'ok');

            return;

            $data = ["Unsupported request: $request"];
            $this->send_data($data, 'ok');

            return;
        }

        $this->send_data(['Unknown request'], 'error');
    }

    // Only acadadmin can do these.
    public function course()
    {
        // Only need api key
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $login = getLogin();
        if (!in_array('ACAD_ADMIN', getRoles($login))) {
            $this->send_data([], 'Forbidden');

            return;
        }

        $args = func_get_args();
        if ('metadata' === $args[0]) {
            if ('get' === $args[1]) {
                $cid = base64_decode($args[2]);
                $data = getCourseById($cid);
                $this->send_data($data, 'ok');

                return;
            } elseif ('update' === $args[1]) {
                $res = updateCourseMetadata($_POST);
                $this->send_data(['success' => $res], 'ok');

                return;
            } elseif ('add' === $args[1]) {
                $isValid = true;
                foreach (['id', 'name'] as $k) {
                    if (!$_POST[$k]) {
                        $isValid = false;
                    }
                }

                if ($isValid) {
                    $res = insertCourseMetadata($_POST);
                    $this->send_data(['success' => $res, 'payload' => $_POST], 'ok');
                } else {
                    $this->send_data(['success' => 'Invalid entry', 'payload' => $_POST], 'ok');
                }

                return;
            } elseif ('delete' === $args[1]) {
                $cid = base64_decode($args[2]);
                $_POST['id'] = $cid;
                $_POST['status'] = 'INVALID';
                $res = updateTable('courses_metadata', 'id', 'status', $_POST);
                $this->send_data(['success' => $res], 'ok');

                return;
            } elseif ('deactivate' === $args[1]) {
                $cid = base64_decode($args[2]);
                $_POST['id'] = $cid;
                $_POST['status'] = 'DEACTIVATED';
                $res = updateTable('courses_metadata', 'id', 'status', $_POST);
                $this->send_data(['success' => $res], 'ok');

                return;
            } elseif ('activate' === $args[1]) {
                $cid = base64_decode($args[2]);
                $_POST['id'] = $cid;
                $_POST['status'] = 'VALID';
                $res = updateTable('courses_metadata', 'id', 'status', $_POST);
                $this->send_data(['success' => $res], 'ok');

                return;
            }

            $this->send_data(['Unknown request'], 'error');

            return;
        } elseif ('running' === $args[0]) {
            $endpoint = $args[1];
            if ('update' === $endpoint) {
                $res = @addOrUpdateRunningCourse($_POST, 'update');
                $this->send_data($res, 'ok');

                return;
            } elseif ('add' === $endpoint) {
                $res = @addOrUpdateRunningCourse($_POST, 'add');
                $this->send_data($res, 'ok');

                return;
            } elseif ('remove' === $endpoint) {
                $res = deleteRunningCourse($_POST);
                $this->send_data($res, 'ok');

                return;
            } elseif ('assignslotvenue' === $endpoint) {
                $res = assignSlotVenueRunningCourse($_POST);
                $this->send_data($res, 'ok');

                return;
            }

            $this->send_data(["Unknown endpoint slot/$endpoint"], 'error');

            return;
        }
        if ('slot' === $args[0]) {
            $endpoint = $args[1];
            if ('all' === $endpoint) {
                $data = getSlots();
                $this->send_data($data, 'ok');

                return;
            }

            $this->send_data(["Unknown endpoint slot/$endpoint"], 'error');

            return;
        }

        $this->send_data(['Unknown request'], 'error');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Journal club endpoint.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function jc()
    {
        // Only need api key
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $args = func_get_args();
        if ('update' === $args[0]) {
            $login = getLogin();
            $presenter = $_POST['presenter'];
            if ($login === explode('@', $presenter)[0] || isThisJCAdmin($login, $_POST['jc_id'])) {
                $res = updateTable(
                    'jc_presentations', 'id',
                    'title,description,url,presentation_url,vc_url', $_POST
                );
                $this->send_data(['success' => $res ? 'Success' : 'Failed', 'msg' => ''], 'ok');

                return;
            }
            $this->send_data(['success' => false, 'msg' => "You don't have permission to edit this JC"], 'ok');

            return;

            return;
        } elseif ('acknowledge' === $args[0]) {
            $_POST['acknowledged'] = 'YES';
            $_POST['id'] = $args[1];
            $res = updateTable('jc_presentations', 'id', 'acknowledged', $_POST);
            $this->send_data([$res ? 'Success' : 'Failed'], 'ok');

            return;
        } elseif ('info' === $args[0]) {
            $jcID = __get__($args, 1, 'all');
            if ('all' !== $jcID) {
                $data = getTableEntry(
                    'journal_clubs', 'id,status',
                    ['id' => $jcID, 'status' => 'ACTIVE']
                );
                $data['admins'] = getJCAdmins($jcID);
            } else {
                $data = getTableEntries('journal_clubs', 'id', "status!='INVALID'");
                foreach ($data as &$jc) {
                    $jc['admins'] = getJCAdmins($jc['id']);
                }
            }
            $this->send_data($data, 'ok');

            return;
        } elseif ('subscriptions' === $args[0]) {
            $jcID = $args[1];
            $data = getTableEntries(
                'jc_subscriptions', 'login',
                "jc_id='$jcID' AND status='VALID'"
            );
            $this->send_data($data, 'ok');

            return;
        }

        $this->send_data(['msg' => 'Unknown request', 'success' => false], 'ok');
    }

    public function jcadmin()
    {
        // Only need api key
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        // These requires JC ADMIN privileges.
        if (!isJCAdmin(getLogin())) {
            $this->send_data([msg => 'You are not an admin', 'success' => false], 'ok');

            return;
        }

        // JC ADMIN tasks.
        $args = func_get_args();
        if ('remove' === $args[0]) {
            $_POST['status'] = 'INVALID';
            $_POST['id'] = $args[1];
            $res = removeJCPresentation($_POST);
            $this->send_data($res, 'ok');

            return;
        } elseif ('update' === $args[0]) {
            $res = updateTable(
                'jc_presentations', 'id',
                'title,description,url,presentation_url', $_POST
            );
            $this->send_data([$res ? 'Success' : 'Failed'], 'ok');

            return;
        } elseif ('assign' === $args[0]) {
            $_POST['date'] = dbDate($_POST['date']);
            if(strtotime($_POST['date']) < strtotime('now')) {
                $this->send_data(['status' => false
                    ,  'msg' => "Can't assign JC in the past: " 
                    . $_POST['date']], 'ok');

                return;
            }
            $_POST['time'] = dbTime($_POST['time']);
            $speaker = __get__($_POST, 'presenter', '');
            if (!$speaker) {
                $this->send_data(['status' => false,  'msg' => 'Not valid presenter' . $speaker], 'ok');

                return;
            }

            $res = assignJCPresentationToLogin($speaker, $_POST);
            $this->send_data($res, 'ok');

            return;
        } elseif ('unsubscribe' === $args[0]) {
            $jcid = urldecode($args[1]);
            $login = urldecode($args[2]);
            $data = ['jc_id' => $jcid, 'login' => $login];
            $res = unsubscribeJC($data);
            $this->send_data($data, 'ok');

            return;
        } elseif ('subscribe' === $args[0]) {
            $jcid = $args[1];
            $login = $args[2];
            $res = subscribeJC(['jc_id' => $jcid,  'login' => $login]);
            $this->send_data($res, 'ok');

            return;
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Return events based on GET query.
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
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $args = func_get_args();
        if (0 == count($args)) {
            $args[] = 'latest';
        }
        $this->process_events_requests($args);
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Return AWS based on GET query.
     * Examples of endpoints:
     *     - /aws/latest/6
     *     - /aws/date/2019-03-01               // Find AWS in this week.
     *     - /aws/date/2019-03-01/2019-04-01    // Find AWS between these  dates.
     *     - /aws/vc_url/get                     // AWS remote url
     *     - /aws/vc_url/set                     // AWS remote url, set
     *     - /aws/venue/change                  // Change AWS venue.
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function aws()
    {
        // Only need api key
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $args = func_get_args();
        if (0 == count($args)) {
            $args = ['latest'];
        }

        $results = [];
        $status = 'ok';
        if ('date' === $args[0]) {
            $from = dbDate($args[1]);
            $to = dbDate(__get__($args, 2, strtotime('+14 day', strtotime($from))));
            $results = getTableEntries(
                'annual_work_seminars', 'date',
                "date >= '$from' AND date < '$to'"
            );
        }
        if ('id' === $args[0]) {
            $id = $args[1];
            $data = getTableEntry('annual_work_seminars', 'id', ['id' => $id]);
            $this->send_data($data, 'ok');

            return;
        } elseif ('upcomingid' === $args[0]) {
            $id = $args[1];
            $data = getTableEntry('upcoming_aws', 'id', ['id' => $id]);
            $this->send_data($data, 'ok');

            return;
        } elseif ('latest' === $args[0]) {
            $numEvents = __get__($args, 1, 6);
            $from = dbDate('today');
            // echo " x $from $numEvents ";
            $results = getTableEntries(
                'upcoming_aws', 'date',
                "date >= '$from'", '*', $numEvents
            );
        } elseif ('venue' === $args[0]) {
            if ('change' === $args[1]) {
                $res = updateTable('upcoming_aws', 'date', 'venue,vc_url', $_POST);
                // Also change in global config (deprecated)
                $res = insertOrUpdateTable(
                    'config', 'id,value', 'value', ['id' => 'AWS_VC_URL', 'value' => $_POST['vc_url']]
                );
                $this->send_data(['success' => $res], 'ok');

                return;
            }

            $this->send_data(['Unsupported endpoint: ' . json_encode($args)], 'ok');

            return;
        } elseif ('vc_url' === $args[0]) {
            // Deprecated. Each AWS gets its own VC_URL. This field still works
            // as a default value. Remove it in the future.
            if ('get' === $args[1]) {
                // get current AWS VC url from global config.
                $res = ['AWS_VC_URL' => getConfigValue('AWS_VC_URL')];

                return $this->send_data($res, 'ok');
            } elseif ('set' === $args[1]) {
                // set AWS VC url globally.
                $res = ['success' => insertOrUpdateTable('config', 'id,value', 'id,value', ['id' => 'AWS_VC_URL', 'value' => $_POST['AWS_VC_URL']]), 'status' => 'ok'];
                $this->send_data($res, $status);
            }
        } else {
            $status = 'warning';
        }
        $this->send_data($results, $status);
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis API related to venues. This require authentication.
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
        if (0 == count($args)) {
            $this->send_data(['Invalid URL'], 'error');

            return;
        }
        $this->process_venue_request($args);
    }

    // Show documentation of venue().
    private function process_venue_request($args)
    {
        // List of venues are available to all even without authentication.
        // Required for MAP to work.
        if ('list' === $args[0]) {
            $types = __get__($args, 1, 'all');
            // For courses.
            if ('course' === $types) {
                $types = 'LECTURE HALL,AUDITORIUM, REMOTE VC';
            } elseif ('aws' === $types) {
                $types = 'LECTURE HALL,AUDITORIUM,REMOTE VC';
            } elseif ('jc' === $types) {
                $types = 'LECTURE HALL,MEETING ROOM,AUDITORIUM,REMOTE VC';
            }
            $data = [];
            foreach (explode(',', $types) as $type) {
                // if(urlencode(urldecode($type)) !== $type)
                //     $type = urldecode($type);
                $data = array_merge($data, getVenuesByType($type));
            }
            $this->send_data($data, 'ok');

            return;
        }

        // Rest of endpoints needs authentication data.
        if (!authenticateAPI(getKey(), getLogin())) {
            $this->send_data([], 'Not authenticated');

            return;
        } elseif ('info' === $args[0]) {
            $id = __get__($args, 1, 0);
            $data = getVenueById($id);
            $this->send_data($data, 'ok');

            return;
        } elseif ('status' === $args[0]) {
            $data = [];
            // Get the status of given venus Venues id are send by csv.
            $venues = explode(',', __get__($args, 1, 'all'));
            // Select all venues.
            if (!$venues || ('all' == $venues[0])) {
                $venues = getVenuesNames();
            }

            $startDateTime = intval(__get__($args, 2, strtotime('now')));
            $endDateTime = intval(__get__($args, 3, $startDateTime + 15 * 3600));

            // Only for a day.
            $date = dbDate($startDateTime);
            $time = dbTime($startDateTime);
            $end_time = dbTime($endDateTime);

            $res = [];
            foreach ($venues as $venue) {
                $eventsAndReqs = getVenueBookingsOnDateTime($venue, $date, $time, $end_time);
                $res[] = ['id' => $venue, 'events' => $eventsAndReqs];
                $data[$venue] = $eventsAndReqs;
            }

            $data['REQ_DATE'] = $date;
            $data['REQ_START_TIME'] = $time;
            $data['REQ_END_TIME'] = $end_time;
            $data['venues'] = $res;
            $this->send_data($data, 'ok');

            return;
        } elseif ('book' === $args[0]) {
            // Book a venue. bookVenue send data back to user.
            $this->bookVenue(
                base64_decode($args[1]),
                intval(__get__($args, 2, 0)),
                intval(__get__($args, 3, 0)),
                $_POST
            );

            return;
        }

        $this->send_data(['unknown endpoint' . $args[0]], 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Helper function to book venue.
     *
     * @Param $venueid
     * @Param $startDateTime
     * @Param $endDateTime
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    private function bookVenue(string $venueId, int $startDateTime = 0, int $endDateTime = 0, array $data
    ) {
        if (0 == $startDateTime) {
            $startDateTime = __get__($data, 'start_date_time', 0);
        }
        if (0 == $endDateTime) {
            $endDateTime = __get__($data, 'end_date_time', 0);
        }

        $startDateTime = is_numeric($startDateTime) ? $startDateTime : intval($startDateTime);
        $endDateTime = is_numeric($endDateTime) ? $endDateTime : intval($endDateTime);

        if ((!$venueId) || ($startDateTime >= $endDateTime)) {
            $data = ['msg' => "Could not book for: venue=$venue, startDateTime=$startDateTime
            and endTime=$endTime", 'success' => false];
            $this->send_data($data, 'error');

            return;
        }

        $bookingDate = dbDate($startDateTime);
        if(isAWSHoliday($bookingDate)) {
            $notAllowed = ['THESIS SEMINAR', 'ANNUAL WORK SEMINAR'
                , 'PRESYNOPSIS SEMINAR'];
            $holiday = getTableEntry('holidays', 'date', ['date'=>$bookingDate]); 
            if(in_array($data['class'], $notAllowed)) {
                $data = ['msg' => $data['class'] . 
                    " is not allowed on $bookingDate." .  
                    __get__($holiday, 'description', '')
                    , 'success' => false];
                $this->send_data($data, 'error');

                return;
            }
        }

        // Who is creating.
        $data['created_by'] = getLogin();

        // Now check if 'dates' or 'repeat_pat is given.
        $request = array_merge(
            $data,
            ['venue' => $venueId, 'date' => dbDate($startDateTime), 'start_time' => dbTime($startDateTime), 'end_time' => dbTime($endDateTime)]
        );

        $ret = submitBookingRequest($request, getLogin());
        $status = $ret['success'] ? 'ok' : 'error';
        // $ret['payload'] = json_encode($request);
        $this->send_data($ret, $status);
    }

    public function authenticate()
    {
        $user = __get__($_POST, 'login', 'NA');
        $password = __get__($_POST, 'password', 'NA');
        $pass = base64_decode($password);
        $res = @authenticateUser($user, $pass);
        $token = '';
        $gmapkey = '';

        // If $res is true then return a token. User can use this token to login
        // as many time as she likes.
        if ($res) {
            $token = __get__(getUserKey($user), 'apikey', '');
            if (!$token) {
                $token = genererateNewKey($user);
            }
            $gmapkey = getConfigValue('GOOGLE_MAP_API_KEY');
        }

        $this->send_data(
            ['apikey' => $token, 'gmapapikey' => $gmapkey, 'authenticated' => $res ? true : false], $token ? 'ok' : 'erorr'
        );
    }

    public function authenticate_by_key()
    {
        $user = $_POST['user'];
        $key = $_POST['HIPPO-API-KEY'];
        if (authenticateAPI($key, $user)) {
            $this->send_data(['authenticated' => true], 'ok');
        } else {
            $this->send_data(['authenticated' => false], 'error');
        }
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
    public function config()
    {
        // Only need api key
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $args = func_get_args();
        if (in_array(strtoupper($args[0]), ['CLIENT_SECRET', 'GOOGLE_MAP_API_KEY'])) {
            $this->send_data(['Not allowed'], 'ok');

            return;
        }

        if (__get__($args, 0, '')) {
            $id = $args[0];
            $data = getTableEntry('config', 'id', ['id' => $id]);
            $this->send_data($data, 'ok');

            return;
        }

        $this->send_data(['Empty query'], 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis mybooking related actioons.
     *  - /api/mybooking/list/[date] -- return all booking by 'login' (in post)
     *  - /api/mybooking/delete/request/gid.[rid] -- delete request gid.rid
     *  - /api/mybooking/delete/event/gid.[eid] -- delete request gid.rid
     */
    /* ----------------------------------------------------------------------------*/
    public function mybooking()
    {
        // Only need api key
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }
        $args = func_get_args();

        $args = func_get_args();
        if ('list' === $args[0]) {
            $startDate = dbDate(intval(__get__($args, 1, strtotime('today'))));
            $login = getLogin();
            $requests = getRequestOfUser($login);
            $events = getEventsOfUser($login, $startDate);

            // Group them by gid.
            $requestsGrouped = [];
            $eventsGrouped = [];
            foreach ($requests as $r) {
                $requestsGrouped[$r['gid']][] = $r;
            }
            foreach ($events as $e) {
                $eventsGrouped[$e['gid']][] = $e;
            }

            $this->send_data(
                ['date' => $startDate, 'requests' => $requestsGrouped, 'events' => $eventsGrouped],
                'ok'
            );

            return;
        }

        // Delete given request of events.
        if ('delete' === $args[0]) {
            $login = getLogin();
            if ('request' === $args[1]) {
                $data = explode('.', $args[2]);
                $gid = $data[0];
                $rid = __get__($data, 1, '');
                if ($rid) {
                    $res = changeRequestStatus($gid, $rid, 'CANCELLED');
                    $this->send_data(['success' => $res
                        , 'msg' => "Request $gid.$rid is deleted"]
                        , 'ok');

                    return;
                }

                // delete the whole group.
                $res = false;
                $warning = '';
                try {
                    $res = changeStatusOfRequests($gid, 'CANCELLED');
                } catch (Exception $e) {
                    $warning .= $e->getMessage();
                }
                $this->send_data(['success' => $res, 'msg' => $warning], 'ok');

                return;
            } elseif ('event' == $args[1]) {
                $data = explode('.', $args[2]);
                $gid = $data[0];
                $login = getLogin();
                $eid = __get__($data, 1, '');
                if ($eid) {
                    $res = false;
                    $msg = "Event $gid.$eid is cancelled";
                    try {
                        $res = changeStatusOfEvent($gid, $eid, 'CANCELLED');
                    } catch (Exception $e) {
                        $msg = $e->getMessage();
                    }
                    $this->send_data(['success' => $res, 'msg' => $msg], 'ok');

                    return;
                }

                // delete the whole group.
                $res = changeStatusOfEventGroup($gid, $login, 'CANCELLED');
                $this->send_data(
                        ['msg' => "Event group $gid is cancelled.", 'success' => $res], 'ok'
                    );

                return;
            }

            $this->send_data(['success' => false, 'msg' => 'Not implemented'], 'ok');

            return;
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Return public events from a given date.
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
        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Transport details.
     *    - /api/transport/day/[from]/[to]/[vehicle]
     *    e.g.,
     *       - /api/tranport/day/MANDARA/NCBS/Buggy
     */
    /* ----------------------------------------------------------------------------*/
    public function transport()
    {
        $args = func_get_args();
        $firstArg = __get__($args, 0, 'all');

        if ('upcoming' === $firstArg) {
            $day = date('D', strtotime('today'));
            $nowTime = dbTime('now');
            $endTime = dbTime('+2 hours');
            $where = "day='$day' AND trip_start_time >= '$nowTime' AND trip_end_time<='$endTime'";
            $where .= " AND status='VALID'";
            $data = executeQuery(
                "SELECT * FROM transport WHERE $where
                GROUP BY vehicle,pickup_point,drop_point"
            );
            $this->send_data($data, 'ok');

            return;
        }

        $day = $firstArg;
        $where = "status='VALID'";
        if ('all' != $day) {
            $where .= " AND day='$day'";
        }

        $pickupPoint = __get__($args, 1, '');
        $dropPoint = __get__($args, 2, '');
        $vehicle = __get__($args, 3, '');

        if ($pickupPoint) {
            $where .= " AND pickup_point='$pickupPoint' ";
        }
        if ($dropPoint) {
            $where .= " AND drop_point='$dropPoint' ";
        }
        if ($vehicle) {
            $where .= " AND vehicle='$vehicle' ";
        }

        $data = getTableEntries('transport', 'day,pickup_point,trip_start_time', $where);
        $timetableMap = [];
        foreach ($data as $d) {
            $timetableMap[strtolower($d['day'])][strtolower($d['pickup_point'])][strtolower($d['drop_point'])][] = $d;
        }

        // Get routes.
        $routes = executeQuery(
            "SELECT DISTINCT pickup_point,drop_point,url FROM transport WHERE status='VALID'"
        );
        $lastUpdateOn = executeQuery("SELECT MAX(last_modified_on) AS
            last_modified_on FROM transport WHERE status!='INVALID'");
        if($lastUpdateOn)
            $lastUpdateOn = $lastUpdateOn[0]['last_modified_on'];

        $res = ['timetable'=>$timetableMap, 'routes'=>$routes
            , 'last_updated_on'=>$lastUpdateOn];
        $this->send_data($res, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Transport details.
     *    - /api/transportation/timetable/day/[from]/[to]/[vehicle]
     *    e.g.,
     *       - /api/tranport/schedule/day/MANDARA/NCBS/Buggy
     *    - /api/transportation/vehicle/[list|remove|add|update]
     *    - /api/transportation/routes/[list|remove|add|update]
     */
    /* ----------------------------------------------------------------------------*/
    public function transportation()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $login = getLogin();
        $roles = getRoles($login);
        if (!in_array('SERVICES_ADMIN', $roles)) {
            $this->send_data([$roles], 'Forbidden.');

            return;
        }

        $args = func_get_args();
        $endpoint = $args[0];

        if ('timetable' === $endpoint) {
            $days = strtolower(__get__($args, 1, 'all'));
            $where = "status='VALID'";
            if ('all' != $days) {
                $where .= ' AND (';
                $temp = [];
                foreach (explode(',', $days) as $day) {
                    $temp[] = " day='$day' ";
                }
                $where .= implode(' OR ', $temp);
                $where .= ' )';
            }

            $pickupPoint = __get__($args, 2, '');
            $dropPoint = __get__($args, 3, '');
            $vehicle = __get__($args, 4, '');

            if ($pickupPoint) {
                $where .= " AND pickup_point='$pickupPoint' ";
            }
            if ($dropPoint) {
                $where .= " AND drop_point='$dropPoint' ";
            }
            if ($vehicle) {
                $where .= " AND vehicle='$vehicle' ";
            }

            $data = getTableEntries('transport', 'vehicle,trip_start_time,day', $where);
            $this->send_data($data, 'ok');

            return;
        } elseif ('vehicle' === $endpoint) {
            /*
             * We don't have a dedicated table for vehicle. It is unlikely to
             * be very useful. We just need to store the name of vehicle such
             * as shuttle/Buggy/E-Rickshaw etc. We use config table to store
             * these names under the key 'transport.vehicle'.
             */
            if ('list' === $args[1]) {
                $vehicles = getVehiclesInAvailableTransport();
                $vehs = json_decode(getConfigValue('transport.vehicle'), true);
                if($vehs)
                    $vehicles = array_unique(array_merge($vehicles, $vehs));

                insertOrUpdateTable('config', 'id,value,status', 'value,status'
                    , ['id'=>'transport.vehicle', 'value'=>json_encode($vehicles)
                    , 'status'=>'VALID']);

                $this->send_data($vehicles, 'ok');
                return;
            } elseif ('add' === $args[1]) {
                $veh = $args[2];
                $vehicles = json_decode(getConfigValue('transport.vehicle'), true);
                $vehicles[] = $veh;
                $vehicles = array_unique($vehicles);
                $res = updateTable('config', 'id', 'value'
                    , ['id'=>'transport.vehicle', 'value' => json_encode($vehicles)]);
                $this->send_data(['success'=>$res, 'msg'=>''], 'ok');

                return;
            } elseif ('remove' === $args[1] || 'delete' === $args[1]) {
                // Dont remove if there is a tranport entry with this vehicle.
                $veh = $args[2];
                $available = getVehiclesInAvailableTransport();
                if(in_array($veh, $available)) {
                    $this->send_data(['success'=>false,
                        'msg' => "First delete this vehicle's entries in "
                        . "transportation schedule."
                    ]);
                    return;
                }

                $vehicles = json_decode(getConfigValue('transport.vehicle'));
                $vehicles = array_diff($vehicles, [$veh]);  // delete
                $res = updateTable('config', 'id,value'
                    , ['id'=>'transport.vehicle', 'value' => json_encode($vehicles)]);
                $this->send_data(['success'=>$res, 'msg'=>''], 'ok');

                return;
            } elseif ('update' === $args[1]) {
                $res = ["Unsupported endpoint $endpoint/" . $args[1]];
                $this->send_data($res, 'ok');

                return;
            }

            $res = ["unknown endpoint $endpoint/" . $args[1]];
            $this->send_data($res, 'ok');

            return;
        } elseif ('schedule' === $endpoint) {
            if ('list' === $args[1]) {
                $res = ["unknown endpoint $endpoint/" . $args[1]];
                $this->send_data($res, 'ok');

                return;
            } elseif ('add' === $args[1]) {
                $res = [];

                try {
                    $res = addNewTransportationSchedule($_POST);
                } catch (Exception $e) {
                    $res = ['success' => false, 'msg' => $e->getMessage()];
                }

                if (!$res['success']) {
                    $res['payload'] = $_POST;
                }
                $this->send_data($res, 'ok');

                return;
            } elseif ('remove' === $args[1] || 'delete' === $args[1]) {
                if (intval($args[2]) < 0) {
                    $this->send_data(['Invalid entry.'], 'ok');

                    return;
                }
                foreach (explode(',', $args[2]) as $id) {
                    $data = ['id' => $id];
                    $res = deleteFromTable('transport', 'id', $data);
                }
                $this->send_data(['success' => $res], 'ok');

                return;
            } elseif ('update' === $args[1]) {
                $keys = 'trip_start_time,trip_end_time,day,comment,day,';
                $keys .= 'edited_by,last_modified_on';
                $_POST['last_modified_on'] = strtotime('now');
                $_POST['edited_by'] = getLogin();
                $res = updateTable('transport', 'id', $keys, $_POST);
                $this->send_data(['success' => $res], 'ok');

                return;
            }

            $res = ["unknown endpoint $endpoint/" . $args[1]];
            $this->send_data($res, 'ok');

            return;
        } elseif ('route' === $endpoint) {
            if ('list' === $args[1]) {
                $routes = getRoutes();
                $this->send_data($routes, 'ok');
            } elseif ('add' === $args[1]) {
                $res = addRoute($_POST);
                $this->send_data($res, 'ok');

                return;
            } elseif (in_array($args[1], ['remove', 'delete'])) {
                $from = $_POST['pickup_point'];
                $to = $_POST['drop_point'];

                // If there is any entry in transportantion, refuse to delete.
                $res = getTableEntry('transport', 'pickup_point,drop_point', $_POST);
                if($res) {
                    $this->send_data(['success'=>false
                        , 'msg'=> "Please delete all transport entries for this route: $from --> $to"]
                        , "ok");
                    return;
                }
                $res = deleteRoute($_POST);
                $this->send_data($res, 'ok');

                return;
            } elseif ('update' === $args[1]) {
                $res = ["unknown endpoint $endpoint/" . $args[1]];
                $this->send_data($res, 'ok');

                return;
            } else {
                $res = ["unknown endpoint $endpoint/" . $args[1]];
                $this->send_data($res, 'ok');

                return;
            }
        } else {
            $res = ["unknown endpoint $endpoint"];
            $this->send_data($res, 'ok');

            return;
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Find a person over ldap.
     *
     * @Param $query
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function ldap($query)
    {
        // Only need api key
        if (!authenticateAPI(getKey(), getLogin())) {
            $this->send_data([], 'Not authenticated');

            return;
        }
        $res = getUserInfoFromLdapRelaxed($query);
        $data = [];
        foreach ($res as $ldap) {
            if ('true' != strtolower($ldap['is_active'])) {
                continue;
            }

            $phone = '';
            if (is_numeric(__get__($ldap, 'extension', 'NA'))) {
                $phone = '+91 80 2366 ' . $ldap['extension'];
            }
            $data[] = [
                'name' => implode(' ', [__get__($ldap, 'fname', ''),
                __get__($ldap, 'lname', ''), ]), 'email' => $ldap['email'],
                'phone' => $phone, 'group' => $ldap['laboffice'],
                'extension' => $ldap['extension'],
            ];
        }
        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis API related to user profile.
     *
     * @Param
     *   - /me/profile
     *   - /me/photo
     *   - /me/aws
     *   - /me/jc
     *   - /me/course
     *   - /me/roles
     *   - /me/supervisor/add
     *   - /me/speaker/new
     *   - /me/talk
     *             /register
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function me()
    {
        if (!authenticateAPI(getKey(), getLogin())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $user = getLogin();
        $args = func_get_args();

        if ('profile' === $args[0]) {
            $endpoint = __get__($args, 1, 'get');
            if ('get' === $endpoint) {
                // If no userid is given, use current user.
                $user = $args[2] ?? $user;
                $ldap = getUserInfo($user, true);

                $remove = ['fname', 'lname', 'mname', 'roles'];
                $data = array_diff_key($ldap, array_flip($remove));
                $this->send_data($data, 'ok');

                return;
            } elseif ('update' === $endpoint) {
                $data = ['success' => false, 'msg' => ''];
                if ($_POST['HIPPO-LOGIN'] !== $_POST['login']) {
                    $data['msg'] = 'You can not update profile of ' . $_POST['login']
                        . ' using this endpoint.';
                    $this->send_data($data, 'ok');

                    return;
                }
                $editables = array_keys(getProfileEditables());
                $_POST['login'] = getLogin();
                $res = updateTable('logins', 'login', $editables, $_POST);
                if ($res) {
                    $data['success'] = true;
                } else {
                    $data['msg'] .= 'Failed to update profile.';
                }
                $this->send_data($data, 'ok');

                return;
            } elseif ('editables' === $endpoint) {
                $this->send_data(getProfileEditables(), 'ok');

                return;
            }

            $this->send_data(["unknown request $endpoint."], 'ok');

            return;
        } elseif ('roles' === $args[0]) {
            $data = executeQuery("SELECT roles FROM logins WHERE login='$user'");
            $this->send_data($data[0], 'ok');

            return;
        } elseif ('speaker' === $args[0]) {
            return $this->__commontasks(...$args);
        } elseif ('photo' === $args[0]) {
            $login = $args[1] ?? getLogin();
            if ($login !== getLogin()) {
                $roles = getRoles($login);
                if (!(in_array('ADMIN', $roles) || in_array('ACAD_ADMIN', $roles))) {
                    $this->send_data([], 'Forbidden');

                    return;
                }
            }
            $pic = getUserPhotoB64($login);
            $this->send_data(['base64' => $pic], 'ok');

            return;
        } elseif ('request' === $args[0]) {
            $params = explode('.', $args[1]);
            $where = 'gid';
            if (intval(__get__($params, 1, -1)) >= 0) {
                $where .= ',rid';
            }
            $res = updateTable(
                'bookmyvenue_requests',
                $where,
                'title,description,is_public_event,vc_url,url,class',
                $_POST
            );
            $this->send_data(['success' => $res, 'msg' => 'Success'], 'ok');

            return;
        } elseif ('event' === $args[0]) {
            $params = explode('.', $args[1]);
            $where = 'gid';
            if (intval(__get__($params, 1, -1)) >= 0) {
                $where .= ',eid';
            }
            $res = updateTable(
                'events',
                $where,
                'title,description,is_public_event,class,vc_url,url',
                $_POST
            );
            $this->send_data(['success' => $res, 'msg' => 'Success'], 'ok');

            return;
        } elseif ('roles' === $args[0]) {
            $info = getUserInfo($user, true);
            $data = explode(',', $info['roles']);
            $this->send_data($data, 'ok');

            return;
        } elseif ('aws' === $args[0]) {
            // by default 'get'
            if ('get' === __get__($args, 1, 'get')) {
                $data = getAwsOfSpeaker($user);
                $upcoming = getUpcomingAWSOfSpeaker($user);
                if ($upcoming) {
                    $data[] = $upcoming;
                }

                $this->send_data($data, 'ok');

                return;
            } elseif ('update' === $args[1]) {
                $this->send_data(['success' => false, 'msg' => 'Only Admin can change old AWS'], 'ok');

                return;
            }
        } elseif ('upcomingaws' === $args[0]) {
            if ('update' === $args[1]) {
                $data = updateAWS($_POST, getLogin());
                $this->send_data($data, 'ok');
                return;
            }
        } elseif ('acknowledge_aws' === $args[0]) {
            $user = getLogin();
            $awsID = $args[1];
            $res = updateTable(
                'upcoming_aws', 'id,speaker', 'acknowledged',
                ['id' => $awsID, 'speaker' => $user, 'acknowledged' => 'YES']
            );
            $this->send_data(['res' => $res], 'ok');

            return;
        } elseif ('talk' === $args[0]) {
            if ('register' === $args[1] || 'add' === $args[1]) {
                $_POST['created_by'] = getLogin();
                $_POST['created_on'] = dbDateTime('now');
                $_POST['speaker'] = speakerName($_POST['speaker_id']);
                $data = addNewTalk($_POST);
                $this->send_data($data, 'ok');

                return;
            } elseif ('all' === $args[1]) {
                $data = getMyTalks(getLogin());
                $this->send_data($data, 'ok');

                return;
            } elseif ('upcoming' === $args[1] || 'unscheduled' === $args[1]) {
                $data = getMyUnscheduledTalks(getLogin());
                $this->send_data($data, 'ok');

                return;
            } elseif ('cancel' === $args[1]) {
                $data = cancelTalk($args[2]);
                $this->send_data($data, 'ok');

                return;
            }
            $this->send_data(['unknown requests'], 'ok');

            return;
        } elseif ('course' === $args[0]) {
            $data = getMyAllCourses($user);
            ksort($data);
            $this->send_data($data, 'ok');

            return;
        } elseif ('supervisor' === $args[0]) {
            if ('add' === $args[1]) {
                $update = 'first_name,middle_name,last_name,affiliation,url';
                $res = insertOrUpdateTable('supervisors', 'email,' . $update, $update, $_POST);
                $this->send_data(['success' => true, 'msg' => ''], 'ok');

                return;
            }
        } elseif ('jc' === $args[0]) {
            // endpoint me/jc
            $endpoint = __get__($args, 1, 'presentations');
            if ('presentations' === $endpoint) {
                $data = getUpcomingJCPresentations();
                $this->send_data($data, 'ok');

                return;
            } elseif ('list' === $endpoint) {
                $jcs = [];
                foreach (getUserJCs($user) as $jc) {
                    $jcs[$jc['jc_id']] = $jc;
                }
                $this->send_data($jcs, 'ok');

                return;
            } elseif ('subscribe' === $endpoint) {
                $jcid = $args[2];
                $data = ['jc_id' => $jcid, 'login' => getLogin()];
                $res = subscribeJC($data);
                $this->send_data($res, 'ok');

                return;
            } elseif ('unsubscribe' === $endpoint) {
                $jcid = $args[2];
                $data = ['jc_id' => $jcid, 'login' => getLogin()];
                $res = unsubscribeJC($data);
                $this->send_data($res, 'ok');

                return;
            }

            $this->send_data(["unknown endpoint: $endpoint."], 'ok');

            return;
        }

        $data = ['Unknown query'];
        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis API related to user accomodation.
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

        if ('list' === $args[0]) {
            $limit = __get__($args, 1, 0);
            $data = [];
            $available = getTableEntries(
                'accomodation', 'status,available_from',
                "status != 'EXPIRED' AND status != 'INVALID'",
                '*', $limit
            );

            // Add number of comments.
            foreach ($available as &$item) {
                $extID = 'accomodation.' . $item['id'];
                $item['num_comments'] = getNumberOfRowsInTable(
                    'comment',
                    "external_id='$extID' AND status='VALID'"
                );
            }

            $data['list'] = $available;
            $data['count'] = count($available);
            $data['types'] = getTableColumnTypes('accomodation', 'type');
            $data['status'] = getTableColumnTypes('accomodation', 'status');
            $data['available_for'] = getTableColumnTypes('accomodation', 'available_for');
            $this->send_data($data, 'ok');

            return;
        }

        // After this we need authentication.
        if (!authenticateAPI(getKey(), getLogin())) {
            $this->send_data(['Not authenticated'], 'error');

            return;
        }

        if ('create' === $args[0]) {
            $id = getUniqueID('accomodation');
            $_POST['id'] = $id;
            $_POST['status'] = 'AVAILABLE';
            $_POST['created_by'] = getLogin();
            $_POST['created_on'] = dbDateTime('now');

            $res = insertIntoTable(
                'accomodation',
                'id,type,available_from,available_for,open_vacancies,address,description'
                . ',status,owner_contact,rent,extra,advance,url,created_by,created_on',
                $_POST
            );

            if ($res) {
                $this->send_data(['success' => true, 'id' => $id, 'msg' => ''], 'ok');
            } else {
                $this->send_data(['success' => false, 'msg' => 'Failed'], 'error');
            }

            return;
        }
        if ('update' === $args[0]) {
            $_POST['last_modified_on'] = dbDateTime('now');
            $res = updateTable(
                'accomodation', 'id',
                'type,available_from,available_for,last_modified_on,open_vacancies,address,description'
                . ',status,owner_contact,rent,extra,advance,url,last_modified_on,created_by,created_on',
                $_POST
            );

            if ($res) {
                $this->send_data(['id' => $_POST['id']], 'ok');
            } else {
                $this->send_data(['Failed'], 'error');
            }

            return;
        } elseif ('comment' === $args[0]) {
            $data = $this->handleCommentActions(array_slice($args, 1));
            $this->send_data($data, 'ok');

            return;
        }

        $this->send_data(['Unknown query ' + $args[0]], 'ok');
    }

    private function handleCommentActions($args)
    {
        if ('list' == $args[0]) {
            $ids = __get__($args, 1, '');
            if (!$ids) {
                $ids = array_map(
                    function ($x) {
                        return $x['id'];
                    },
                    executeQuery("SELECT id FROM accomodation WHERE status!='INVALID'")
                );
            } else {
                $ids = explode(',', $ids);
            }

            // Created external ids.
            $extIds = array_map(
                function ($id) {
                    return "'accomodation.$id'";
                }, $ids
            );
            $extIds = implode(',', $extIds);
            $comments = executeQuery(
                "SELECT * FROM comment WHERE 
                external_id in ($extIds) AND status='VALID'"
            );
            $data = ['ids' => $ids, 'comments' => array_values($comments)];

            return $data;
        } elseif ('post' == $args[0]) {
            // posting comment.
            $_POST['commenter'] = getLogin();
            $_POST['external_id'] = 'accomodation.' . $_POST['id'];
            $res = User::postComment($_POST);

            return $res;
        } elseif ('delete' == $args[0]) {
            // posting comment.
            $id = __get__($args, 1, 0);
            $res = User::deleteComment($id);

            return $res;
        }

        return ['This action is not available ' . json_encode($args)];
    }

    public function comment()
    {
        // After this we need authentication.
        if (!authenticateAPI(getKey(), getLogin())) {
            $this->send_data(['Not authenticated'], 'error');

            return;
        }

        $args = func_get_args();
        if ('delete' == $args[0]) {
            $id = __get__($args, 1, 0);
            $res = User::deleteComment($id);
            $this->send_data($res, 'ok');

            return;
        } elseif ('post' == $args[0]) {
            // posting comment.
            $_POST['commenter'] = getLogin();
            $_POST['external_id'] = $_POST['external_id'];
            $res = User::postComment($_POST);
            $this->send_data($res, 'ok');

            return;
        } elseif ('get' === $args[0]) {
            // Fetching comments.
            $limit = __get__($args, 1, 20);
            $this->db->select('*')
                ->where(['external_id' => $args[1], 'status' => 'VALID'])
                ->order_by('created_on DESC')
                ->limit($limit);
            $comms = $this->db->get('comment')->result_array();
            $this->send_data($comms, 'ok');

            return;
        }

        $this->send_data(['unsupported ' + $args[0]], 'failure');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Inventory management.
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
        if (!authenticateAPI(getKey(), getLogin())) {
            $this->send_data(['Not authenticated'], 'error');

            return;
        }

        if ('list' === $args[0]) {
            $limit = intval(__get__($args, 1, 300));
            $data = [];

            // Generate list of inventories.
            $this->db->select('*')
                ->where(['status' => 'VALID'])
                ->limit($limit);

            $inventories = $this->db->get('inventory')->result_array();
            $available = [];
            foreach ($inventories as $inv) {
                // Fetch imgage is any.
                $inv['image_id'] = [];
                $invID = $inv['id'];

                $this->db->select('id')
                    ->where(['external_id' => "inventory.$invID"]);

                $imgs = $this->db->get('images')->result_array();
                foreach ($imgs as $img) {
                    $inv['image_id'][] = $img['id'];
                }

                $available[] = $inv;
            }

            $data['list'] = $available;
            $data['count'] = count($available);
            $data['item_conditions'] = getTableColumnTypes('inventory', 'item_condition');
            $this->send_data($data, 'ok');

            return;
        }

        if ('create' === $args[0]) {
            $id = getUniqueID('inventory');
            $_POST['id'] = $id;
            $_POST['status'] = 'AVAILABLE';
            $_POST['created_by'] = getLoginEmail(getLogin());
            $_POST['created_on'] = dbDateTime('now');

            $res = insertIntoTable(
                'inventory',
                'id,type,available_from,open_vacancies,address,description'
                . ',status,owner_contact,rent,extra,advance,url,created_by,created_on',
                $_POST
            );

            if ($res) {
                $this->send_data(['id' => $id], 'ok');
            } else {
                $this->send_data(['Failed'], 'error');
            }

            return;
        }
        if ('update' === $args[0]) {
            $res = updateTable(
                'inventory', 'id',
                'type,available_from,open_vacancies,address,description'
                . ',status,owner_contact,rent,extra,advance,url,created_by,created_on',
                $_POST
            );

            if ($res) {
                $this->send_data(['id' => $_POST['id']], 'ok');
            } else {
                $this->send_data(['Failed'], 'error');
            }

            return;
        }

        $this->send_data(['Unknown request ' . $args[0]], 'error');

        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis My inventory management.
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
        if (!authenticateAPI(getKey(), getLogin())) {
            $this->send_data(['Not authenticated'], 'error');

            return;
        }

        if ('list' === $args[0]) {
            $limit = intval(__get__($args, 1, 500));
            $data = [];

            $this->db->select('*')
                ->where(['status' => 'VALID', 'faculty_in_charge' => $piOrHost])
                ->limit($limit);
            $available = $this->db->get('inventory')->result_array();

            $itemsToSend = [];

            // Should have a default value.
            $item['borrowing'] = [['borrower' => '']];

            foreach ($available as &$item) {
                $id = $item['id'];
                $bres = $this->db->get_where(
                    'borrowing',
                    ['inventory_id' => $id, 'status' => 'VALID']
                )->result_array();
                $item['borrowing'] = $bres;

                // Get the thumbnail.
                $this->db->select('id, path')->where(['external_id' => "inventory.$id"]);
                $images = $this->db->get('images')->result_array();
                $thumbs = [];
                foreach ($images as $img) {
                    $path = getUploadDir() . '/' . $img['path'];
                    if (file_exists($path)) {
                        $thumb = getBase64JPEG($path, 100, 0);
                        $thumbs[] = ['id' => $img['id'], 'base64' => $thumb];
                    }
                }
                $item['thumbnails'] = $thumbs;
                $itemsToSend[] = $item;
            }

            $data['list'] = $itemsToSend;
            $data['count'] = count($available);
            $data['item_conditions'] = getTableColumnTypes('inventory', 'item_condition');
            $this->send_data($data, 'ok');

            return;
        } elseif ('create' === $args[0] || 'update' === $args[0]) {
            $id = getUniqueID('inventory');
            $_POST['id'] = $id;
            $_POST['edited_by'] = getLogin();
            $_POST['last_modified_on'] = dbDateTime('now');
            $res = User::add_inventory_item_helper($_POST);

            if ($res['status']) {
                $this->send_data(['id' => $id, 'payload' => json_encode($_POST)], 'ok');
            } else {
                $this->send_data([$res['msg']], 'error');
            }

            return;
        } elseif ('lend' === $args[0]) {
            $_POST['lender'] = getLogin();
            $_POST['inventory_id'] = $_POST['id'];
            $res = Lab::lend_inventory($_POST);
            $this->send_data([$res['msg']], $res['status'] ? 'ok' : 'error');

            return;
        } elseif ('gotback' === $args[0]) {
            $invId = __get__($args, 1, 0);
            if (!$this->db->set('status', 'RETURNED')->where('inventory_id', $invId)->update('borrowing')
            ) {
                $this->send_data($this->db->error(), 'error');

                return;
            }
            $this->send_data([], 'ok');
        } elseif ('delete' === $args[0]) {
            $id = __get__($args, 1, 0);
            $res = updateTable('inventory', 'id', 'status', ['id' => $id, 'status' => 'INVALID']);

            if ($res) {
                $this->send_data(['id' => $_POST['id']], 'ok');
            } else {
                $this->send_data(['Failed'], 'error');
            }

            return;
        } else {
            $this->send_data(['Unknown request ' . $args[0]], 'error');
        }

        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Submit geolocation data.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function geolocation()
    {
        $args = func_get_args();
        if ('submit' === $args[0]) {
            $salt = 'ZhanduBalmZhanduBalmPeedaHariBalm';
            $crypt_id = crypt(getUserIpAddr(), $salt);
            $_POST['crypt_id'] = $crypt_id;

            foreach (explode(',', 'session_num,device_id,altitude,accuracy,heading,speed') as $key) {
                $_POST[$key] = __get__($_POST, $key, '');
            }

            // 10 Km/Hr = 2.77 m/s
            // || floatVal($_POST['speed']) <= 1.0 // Enable it when
            // debugging is over.
            if (floatval($_POST['latitude']) <= 0 || floatval($_POST['longitude']) <= 0.0) {
                $this->send_data(['Invalid data.'], 'warn');

                return;
            }

            $res = insertIntoTable(
                'geolocation',
                'latitude,longitude,altitude,device_id,accuracy,heading,speed,session_num,crypt_id',
                $_POST
            );

            if ($res) {
                $this->send_data(['Success'], 'ok');
            } else {
                $this->send_data(['Failure'], 'error');
            }

            return;
        } elseif ('latest' === $args[0]) {
            $limit = intval(__get__($args, 1, 500));

            // Get last 100 points (doen't matter when)
            $res = getTableEntries('geolocation', 'timestamp DESC', '', '*', $limit);

            // crypt_id is the key. Since we don't know the route. Each crypt id
            // is a polyline.
            $data = [];
            foreach ($res as $e) {
                $data[$e['crypt_id']][] = $e;
            }

            $this->send_data($data, 'ok');

            return;
        } elseif ('get' === $args[0]) {
            $mins = intval(__get__($args, 1, 30));
            $timestamp = dbDateTime(strtotime('now') - $mins * 60);
            $res = getTableEntries('geolocation', 'crypt_id,timestamp', "timestamp > '$timestamp'");
            $data = [];
            foreach ($res as $e) {
                $data[$e['crypt_id']][] = $e;
            }

            $this->send_data($data, 'ok');

            return;
        }

        $this->send_data(['Unknown request: ' . $args[0]], 'warn');

        //// From here we need authentication.
        //// After this we need authentication.
        //if(! authenticateAPI(getKey(), getLogin()))
        //{
        //    $this->send_data(["Not authenticated"], "error");
        //    return;
        //}
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Download images.
     *
     * @Param $arg
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function images()
    {
        $args = func_get_args();
        if (0 == count($args)) {
            $args[] = 'get';
        }

        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        if ('get' === $args[0]) {
            $ids = $args[1];
            $data = ['args' => $ids];
            foreach (explode(',', $ids) as $id) {
                $images = $this->db->get_where('images', ['id' => trim($id)])->result_array();
                foreach ($images as $res) {
                    if (!__get__($res, 'path', '')) {
                        continue;
                    }

                    $filepath = getUploadDir() . '/' . $res['path'];
                    if (!file_exists($filepath)) {
                        continue;
                    }

                    try {
                        $data[$id][] = getBase64JPEG($filepath);
                    } catch (Exception $e) {
                        $data['exception'] = $e->getMessage();
                    }
                }
            }
            $this->send_data($data, 'ok');

            return;
        }
        if ('delete' === $args[0]) {
            $ids = $args[1];
            $data = ['args' => $ids, 'msg' => ''];
            foreach (explode(',', $ids) as $id) {
                $images = $this->db->get_where('images', ['id' => trim($id)])->result_array();
                foreach ($images as $res) {
                    $filepath = getUploadDir() . '/' . $res['path'];
                    if (!file_exists($filepath)) {
                        $data['msg'] .= " $filepath not found.";
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
            $this->send_data($data, 'ok');

            return;
        }

        $this->send_data([], "Unsupported command $get");

        return;

        $this->send_data([], 'error');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Upload images.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function upload()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $args = func_get_args();
        $res = [];
        if ('image' === $args[0]) {
            $endpoint = __get__($args, 1, 'inventory');
            if ('inventory' === $endpoint) {
                $invId = intval(__get__($_POST, 'inventory_id', -1));
                if ($invId < 0) {
                    $this->send_data($res, 'Inventory ID is not found.');

                    return;
                }

                if (!empty($_FILES)) {
                    $storeFolder = getUploadDir();
                    $tempFile = $_FILES['file']['tmp_name'];
                    $md5 = md5_file($tempFile);
                    $filename = $md5 . $_FILES['file']['name'];
                    $targetFile = getLoginPicturePath(getLogin());
                    $res['stored'] = move_uploaded_file($tempFile, $targetFile);
                    // Add this value to database.
                    $this->db->select_max('id', 'maxid');
                    $r = $this->db->get('images')->result_array();
                    if ($r) {
                        $id = $r[0]['maxid'];
                    } else {
                        $id = 0;
                    }

                    // Prepare data to send back to client.
                    $data = ['external_id' => 'inventory.' . $invId];
                    $data['path'] = $filename;
                    $data['uploaded_by'] = getLogin();
                    $data['id'] = intval($id) + 1;
                    $this->db->insert('images', $data);
                    $res['dbstatus'] = $this->db->error();
                    $this->send_data($res, 'ok');

                    return;
                }

                $this->send_data($res, 'No file uploaded.');

                return;
            } elseif ('profile' === $endpoint) {
                $img = $_FILES['file'];
                $ext = explode('/', $img['type'])[1];
                $tempFile = $img['tmp_name'];
                $conf = getConf();
                $targetFile = $conf['data']['user_imagedir'] . '/' . getLogin() . '.jpg';
                saveImageAsJPEG($tempFile, $ext, $targetFile);
                $res['stored'] = $targetFile;
                $this->send_data($res, 'ok');

                return;
            } elseif ('speaker' === $endpoint) {
                $id = $args[2];
                $img = $_FILES['file'];
                $ext = explode('/', $img['type'])[1];
                $tempFile = $img['tmp_name'];
                $conf = getConf();
                $targetFile = $conf['data']['user_imagedir'] . "/$id.jpg";
                saveImageAsJPEG($tempFile, $ext, $targetFile);
                $res['stored'] = $targetFile;
                $this->send_data($res, 'ok');

                return;
            }

            $this->send_data(['Unknow request: ' + $endpoint], 'ok');

            return;

            $this->send_data($res, 'error');
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Forum API.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function forum()
    {
        $args = func_get_args();
        $data = [];

        if (0 == count($args)) {
            $args[0] = 'list';
        }

        if ('list' === $args[0]) {
            $limit = 100;
            if (count($args) > 1) {
                $limit = intval($args[1]);
            }

            $this->db->select('*')
                ->where('status', 'VALID')
                ->order_by('created_on DESC')
                ->where('created_on >=', 'DATE_SUB(CURDATE(), INTERVAL 14 DAY)', false)
                ->limit($limit);

            $data = $this->db->get('forum')->result_array();

            // Convert all tags to a list and also collect number of comments.
            foreach ($data as &$e) {
                $e['tags'] = explode(',', $e['tags']);
                $eid = 'forum.' . $e['id'];
                $this->db->select('id')->where(['external_id' => $eid, 'status' => 'VALID']);
                $e['num_comments'] = $this->db->count_all_results('comment');
            }
            $this->send_data($data, 'ok');

            return;
        } elseif ('alltags' === $args[0]) {
            // fixme: This should be from database.
            $tags = explode(',', getConfigValue('ALLOWED_BOARD_TAGS'));
            sort($tags, SORT_STRING);
            $this->send_data($tags, 'ok');

            return;
        }

        // These requires authentications.
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        if ('delete' === $args[0]) {
            $id = __get__($args, 1, -1);
            $this->db->set('status', 'DELETED')->where('id', $id);
            $this->db->update('forum');

            // Remove any notifications for this id.
            $this->db->where('external_id', 'forum.' . $id)
                ->set('status', 'INVALID')
                ->update('notifications');

            $this->send_data(['deleted' => $id], 'ok');

            return;
        } elseif ('subscribe' === $args[0]) {
            $forumName = $args[1];
            $login = getLogin();
            User::subscribeToForum($this, $login, $forumName);
            $this->send_data(['Subscribed'], 'ok');

            return;
        } elseif ('unsubscribe' === $args[0]) {
            $forumName = $args[1];
            if ('emergency' == $forumName) {
                $this->send_data(["Can't unscribe from emergency"], 'ok');

                return;
            }
            $login = getLogin();
            User::unsubscribeToForum($this, $login, $forumName);
            $this->send_data(['Unsubscribed'], 'ok');

            return;
        } elseif ('subscriptions' === $args[0]) {
            $login = getLogin();
            $data = User::getBoardSubscriptions($this, $login);
            $this->send_data($data, 'ok');

            return;
        } elseif ('post' === $args[0]) {
            // Unique id for the forum post.
            $id = __get__($_POST, 'id', 0);
            $action = 'update';
            if (0 == $id) {
                $this->db->select_max('id', 'maxid');
                $r = $this->db->get('forum')->result_array();
                $id = intval($r[0]['maxid']) + 1;
                $action = 'new';
            }

            $createdBy = getLogin();
            $tags = implode(',', $_POST['tags']);

            // Commit to table.
            if ('new' === $action) {
                $this->db->insert(
                    'forum',
                    ['id' => $id, 'created_by' => $createdBy, 'tags' => $tags, 'title' => $_POST['title'], 'description' => $_POST['description'],
                    ]
                );
                $data['db_error'] = $this->db->error();

                // Post to FCM

                // Also add notifications for subscribed users.
                foreach ($_POST['tags'] as $tag) {
                    // Get the list of subscribers.
                    sendFirebaseCloudMessage($tag, $_POST['title'], $_POST['description']);

                    $subs = $this->db->select('login')
                        ->get_where(
                            'board_subscriptions',
                            ['board' => $tag, 'status' => 'VALID']
                        )->result_array();

                    // Create notifications for each subscriber.
                    foreach ($subs as $sub) {
                        $this->db->insert(
                            'notifications',
                            ['login' => $sub['login'], 'title' => $_POST['title'], 'text' => $_POST['description'], 'external_id' => 'forum.' . $id,
                            ]
                        );
                    }
                }
            } else {
                $this->db->where('id', $id)
                    ->update(
                        'forum', ['tags' => $tags, 'title' => $_POST['title'], 'description' => $_POST['description'],
                        ]
                    );
                $data['db_error'] = $this->db->error();
            }
            $this->send_data($data, 'ok');

            return;
        }

        $data['status'] = 'Invalid request ' . $args[0];
        $this->send_data($data, 'ok');
    }

    public function notifications()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $login = getLogin();
        $args = func_get_args();
        $data = [];
        if (0 == count($args)) {
            $args[] = 'get';
        }

        if ('get' === $args[0]) {
            $limit = __get__($args, 1, 10);
            $notifications = User::getNotifications($this, $login, $limit);
            if(! $notifications)
                $notifications = [];
            $this->send_data($notifications, 'ok');

            return;
        } elseif ('dismiss' === $args[0] || 'markread' == $args[0]) {
            $id = __get__($args, 1, 0);
            $this->db->where('id', $id)
                ->where('login', $login)
                ->update('notifications', ['is_read' => true]);

            $this->send_data(["Marked read: $id"], 'ok');

            return;
        } elseif ('markunread' == $args[0]) {
            $id = __get__($args, 1, 0);
            $this->db->where('id', $id)
                ->where('login', $login)
                ->update('notifications', ['is_read' => false]);

            $this->send_data(["Marked unread: $id"], 'ok');

            return;
        }

        $this->send_data($data, 'Unknown request');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Menu management.
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

        if ('list' === $args[0]) {
            $day = __get__($args, 1, date('D', strtotime('today')));
            $available = getTableEntries(
                'canteen_menu', 'canteen_name,which_meal,available_from',
                "status = 'VALID' AND day='$day'"
            );
            $data['list'] = $available;
            $data['count'] = count($available);
            $canteens = executeQuery("SELECT DISTINCT canteen_name FROM canteen_menu WHERE status='VALID'");
            $canteens = array_map(
                function ($x) {
                    return $x['canteen_name'];
                }, $canteens
            );

            $meals = executeQuery("SELECT DISTINCT which_meal FROM canteen_menu WHERE status='VALID'");
            $meals = array_map(
                function ($x) {
                    return $x['which_meal'];
                }, $meals
            );

            $data['canteens'] = $canteens;
            $data['meals'] = $meals;
            $this->send_data($data, 'ok');

            return;
        }

        // After this we need authentication.
        if (!authenticateAPI(getKey(), getLogin())) {
            $this->send_data(['Not authenticated'], 'error');

            return;
        } elseif ('create' === $args[0]) {
            $_POST['modified_by'] = getLogin();
            $id = Adminservices::addToCanteenMenu($_POST);
            $res = ['req' => json_encode($_POST), 'id' => $id];
            if ($id > 0) {
                $this->send_data($res, 'ok');
            } else {
                $this->send_data(['Failed'], 'error');
            }

            return;
        } elseif ('update' === $args[0]) {
            $res = Adminservices::updateCanteenItem($_POST);
            if ($res) {
                $this->send_data(['id' => $_POST['id']], 'ok');
            } else {
                $this->send_data(['Failed'], 'error');
            }

            return;
        } elseif ('delete' === $args[0]) {
            $id = __get__($args, 1, 0);
            $res = Adminservices::deleteCanteenItem($id);
            if ($res) {
                $this->send_data(['id' => $id], 'ok');
            } else {
                $this->send_data(['Failed'], 'error');
            }

            return;
        }

        $this->send_data(['Unknown request ' . $args[0]], 'error');

        $this->send_data($data, 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis caller should check the access permissions, this api is
     * exposed publically. But the user needs to login no matter what.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function __commontasks()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $args = func_get_args();
        if ('events' === $args[0]) {
            $data = [];
            $subtask = $args[1];
            if ('upcoming' === $subtask) {
                $from = intval(__get__($args, 2, 0));
                $to = intval(__get__($args, 3, 100));
                $limit = $to - $from; // these many groups
                $data = getEventsGID('today', 'VALID', $limit, $from);
                $this->send_data($data, 'ok');

                return;
            } elseif ('cancel' === $subtask) {
                $gid = $args[2];
                $eid = __get__($args, 3, '');  // csv of eid
                $data = cancelEvents($gid, $eid, getLogin(), __get__($_POST, 'reason', ''));
                $this->send_data($data, 'ok');

                return;
            } elseif ('gid' === $subtask) {
                $gid = $args[2];
                // All events by gid.
                $data = getEventsByGroupId($gid, 'VALID', 'today');
                $this->send_data($data, 'ok');

                return;
            }

            $data['flash'] = 'Unknown request ' . $subtask;
            $this->send_data($data, 'ok');

            return;

            $this->send_data($data, 'ok');

            return;
        } elseif ('table' === $args[0]) {
            if ('fieldinfo' === $args[1]) {
                $data = getTableFieldInfo($args[2]);
                $this->send_data($data, 'ok');

                return;
            } elseif ('types' === $args[1]) {
                $ctypes = getTableColumnTypes($args[2], $args[3]);
                $this->send_data($ctypes, 'ok');

                return;
            }
        } elseif ('event' === $args[0]) {
            $data = [];
            $subtask = __get__($args, 1, 'update');
            if ('delete' === $subtask || 'cancel' === $subtask) {
                $_POST['status'] = 'CANCELLED';
                $subtask = 'update';
            }

            if ('update' === $subtask) {
                $res = updateEvent($_POST['gid'], $_POST['eid'], $_POST);
                $data['flash'] = 'successfully updated'; // old api.
                $data['success'] = true;
                $data['msg'] = 'successfully updated';
            } else {
                $data['flash'] = 'Unknown request ' . $subtask; // old api.
                $data['msg'] = 'Unknown request ' . $subtask; 
                $data['success'] = false;
            }
            $this->send_data($data, 'ok');

            return;
        } elseif ('email' === $args[0]) {
            if ('talk' === $args[1]) {
                $tid = intval(__get__($args, 2, '-1'));
                if ($tid < 0) {
                    $this->send_data(
                        ['success' => false, 'msg' => "Invalid talk id $tid"],
                        'ok'
                    );

                    return;
                }
                $email = talkToEmail($tid);
                $this->send_data($email, 'ok');

                return;
            } elseif ('upcoming_aws' === $args[1]) {
                $monday = $args[2];
                $email = awsEmailForMonday($monday);
                $this->send_data($email, 'ok');

                return;
            } elseif ('jc' === $args[1]) {
                $presentationID = $args[2];
                $email = jcPresentationEmail($presentationID);
                $this->send_data($email, 'ok');

                return;
            } elseif ('post' === $args[1]) {
                $data = $_POST;
                $res = sendHTMLEmail(
                    $data['email_body'], $data['subject'],
                    $data['recipients'], $data['cc'], $data['attachments']
                );
                $this->send_data($res, 'ok');

                return;
            }

            $this->send_data(['msg' => 'Unknown Request', 'success' => false], 'ok');

            return;
        } elseif ('speaker' === $args[0]) {
            if ('fetch' === $args[1]) {
                // Fetch speaker.
                $speakerId = intval(__get__($args, 2, -1));
                if ($speakerId <= 0) {
                    $this->send_data([], '404 Not Found');

                    return;
                }

                $data = getSpeakerByID($speakerId);
                $picpath = getSpeakerPicturePath($speakerId);
                $photo = '';
                if (file_exists($picpath)) {
                    $photo = base64_encode(file_get_contents($picpath));
                }
                $data['photo'] = $photo;
                $data['html'] = speakerToHTML($data);
                $this->send_data($data, 'ok');

                return;
            }
            if ('update' === $args[1] || 'new' === $args[1]) {
                // Updating speaker.
                $res = addUpdateSpeaker($_POST);
                $this->send_data($res, 'ok');

                return;
            }
            if ('delete' === $args[1]) {
                if( ! hasRoles('ACAD_ADMIN,BOOKMYVENUE_ADMIN')) {
                    $this->send_data([
                        'success' => false
                        , 'msg' => "You don't have permission to delete a spaeker"
                    ], 401);

                    return;
                }

                // Delete speakers only if there is no valid talks associated
                // with it.
                $speakerID = intval(__get__($args, 2, '-1'));
                if ($speakerID < 0) {
                    $this->send_data(['success' => false, 'msg' => 'Invalid speaker ID'], 'ok');

                    return;
                }
                $talks = getTableEntries(
                    'talks', 'id',
                    "status='ACTIVE' and speaker_id='$speakerID'"
                );
                if ($talks && count($talks) > 0) {
                    $this->send_data(['success' => false, 'msg' => 'Speaker has valid talks.'], 'ok');

                    return;
                }
                $res = deleteFromTable('speakers', 'id', ['id' => $speakerID]);
                $res = deleteFromTable('talks', 'speaker_id', ['speaker_id' => $speakerID]);
                $this->send_data(['success' => $res, 'msg' => 'Successfully deleted'], 'ok');

                return;
            }

            $this->send_data(
                    [
                    'msg' => 'Unknown Request ' . json_encode($args), 'success' => false, ], 'ok'
                );

            return;
        } else {
            $this->send_data(['success' => false, 'msg' => $args], 'ok');

            return;
        }
    }

    // BMV ADMIN
    public function bmvadmin()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $login = getLogin();
        if (!in_array('BOOKMYVENUE_ADMIN', getRoles($login))) {
            $this->send_data([], 'Forbidden');

            return;
        }

        $args = func_get_args();
        if ('requests' === $args[0]) {
            $data = [];
            $subtask = __get__($args, 1, 'pending');
            if ('pending' === $subtask) {
                $data = getPendingRequestsGroupedByGID();
            } elseif ('date' === $subtask) {
                $data = getPendingRequestsOnThisDay($args[2]);
            } else {
                $data = ['flash' => 'Unknown request'];
            }
            $this->send_data($data, 'ok');

            return;
        } elseif ('request' === $args[0]) {
            $data = [];
            $subtask = __get__($args, 1, 'status');
            if ('clash' === $subtask) {
                $jcLabmeets = getLabmeetAndJC();
                $jcOrLab = clashesOnThisVenueSlot(
                    $_POST['date'], $_POST['start_time'],
                    $_POST['end_time'], $_POST['venue'],
                    $jcLabmeets
                );
                $data['clashes'] = $jcOrLab;
            } elseif ('approve' === $subtask) {

                // Mark is a public event, if admin says so.
                if ('YES' === $_POST['is_public_event']) {
                    updateTable('bookmyvenue_requests', 'gid,rid', 'is_public_event', $_POST);
                }

                $ret = actOnRequest($_POST['gid'], $_POST['rid'], 'APPROVE', true, $_POST, getLogin());
                $data['msg'] = 'APPROVED';
            } elseif ('reject' === $subtask) {
                $ret = actOnRequest($_POST['gid'], $_POST['rid'], 'REJECT', true, $_POST, getLogin());
                $data['msg'] = 'REJECTED';
            } else {
                $data = ['flash' => 'Unknown request'];
            }

            // Send final data.
            $this->send_data($data, 'ok');

            return;
        } elseif ('events' === $args[0]) {
            return $this->__commontasks(...$args);
        } elseif ('event' === $args[0]) {
            return $this->__commontasks(...$args);
        } elseif ('venue' === $args[0]) {
            if($args[1] === 'list') {
                // Its different than /venues/list (here we return all
                // entries which are not marked deleted).
                $venues = getTableEntries('venues', 'id', "status != 'DELETED'");
                $this->send_data($venues, 'ok');
                return;
            } elseif($args[1] === 'delete') {
                $id = $args[2];
                $res = false; 
                $error = '';
                try {
                    $res = updateTable('venues', 'id'
                        , 'status', ['id'=>$id, 'status'=>'DELETED']);
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                $this->send_data(['success'=>$res, 'msg'=>$error], 'ok');

                return;
            } elseif($args[1] === 'update' || $args[1] === 'add') {
                $msg = '';
                $_POST['response'] = $args[1];
                $res = false;
                $_POST['longitude'] = __get__($_POST, 'longitude', 0.0);
                $_POST['latitude'] = __get__($_POST, 'latitude', 0.0);

                try {
                    $res = admin_venue_actions($_POST, $msg);
                } catch( Exception $e) {
                    $msg .= $e->getMessage();
                }
                $this->send_data(['success'=>$res, 'msg'=>$msg], 'ok');
                return;
            }
        }
        $this->send_data(['success'=>false, 
            'msg'=>'bmvadmin: Unknown Request: '.json_encode($args)], 'ok');
    }

    // Common admin tasks.
    public function admin()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $login = getLogin();
        $roles = getRoles($login);
        if (!(in_array('ACAD_ADMIN', $roles) || in_array('BOOKMYVENUE_ADMIN', $roles) || in_array('ADMIN', $roles))) {
            $this->send_data([], 'Forbidden');

            return;
        }

        $args = func_get_args();
        $data = [];
        if ('talk' === $args[0]) {
            $endpoint = __get__($args, 1, 'list');
            if ('list' === $endpoint) {
                $limit = __get__($args, 2, 100);

                // Don't fetch any talk created 6 months ago.
                $talks = getTableEntries(
                    'talks', 'created_on DESC',
                    "status != 'INVALID' AND created_on > DATE_SUB(now(), INTERVAL 6 MONTH)",
                    '*', $limit
                );

                $data = [];
                foreach ($talks as &$talk) {
                    $event = getEventsOfTalkId($talk['id']);
                    // if event was in past then don't use this talk;
                    if ($event) {
                        if (strtotime($event['date']) < strtotime('yesterday')) {
                            continue;
                        }
                        $talk['event'] = $event;
                    }
                    $req = getBookingRequestOfTalkId($talk['id']);
                    if ($req) {
                        $talk['request'] = $req;
                    }
                    $data[] = $talk;
                }
                $this->send_data($talks, 'ok');

                return;
            } elseif ('get' === $endpoint) {
                $tid = intval(__get__($args, 2, '-1'));

                if ($tid < 0) {
                    $this->send_data(['success'=>false,
                        'msg'=>"Invalid talk id $tid."], 401);

                    return;
                }

                // Get talks only in future.
                $talk = getTableEntry(
                    'talks', 'id,status',
                    ['id' => $tid, 'status' => 'VALID']
                );
                $event = getEventsOfTalkId($talk['id']);
                if ($event) {
                    $talk['event'] = $event;
                }
                $req = getBookingRequestOfTalkId($talk['id']);
                if ($req) {
                    $talk['request'] = $req;
                }
                $this->send_data($talk, 'ok');

                return;
            } elseif ('update' === $endpoint || 'delete' === $endpoint) {
                $res = updateThisTalk($_POST);
                $this->send_data($res, 'ok');

                return;
            } elseif ('email' === $endpoint) {
                $tid = $args[2];
                $email = talkToEmail($tid);
                $this->send_data($email, 'ok');

                return;
            }

            $this->send_data(['success'=>false, 'msg'=>'Unknown request'], 'ok');

            return;
        } elseif ('event' === $args[0]) {
            return $this->__commontasks(...$args);
        } elseif ('speaker' === $args[0]) {
            return $this->__commontasks(...$args);
        } elseif ('table' === $args[0]) {
            return $this->__commontasks(...$args);
        } elseif ('templates' === $args[0]) {
            // templates.
            if ('list' === $args[1]) {
                $templates = getTableEntries('email_templates', 'id', "id>'0'");
                $this->send_data($templates, 'ok');

                return;
            } elseif ('submit' === $args[1]) {
                $toupdate = 'description,recipients,cc,when_to_send';
                $res = insertOrUpdateTable('email_templates', 'id,' . $toupdate, $toupdate, $_POST);
                $this->send_data(['success' => $res], 'ok');

                return;
            } elseif ('delete' === $args[1]) {
                $res = deleteFromTable('email_templates', 'id', $_POST);
                $this->send_data(['success' => $res], 'ok');

                return;
            }
            $this->send_data(['Unknown endpoint: ' . json_encode($args)], 401);

            return;
        } elseif ('holidays' === $args[0]) {
            if ('list' === $args[1]) {
                $this->info("holidays", 'list');

                return;
            } elseif ('submit' === $args[1]) {
                $res = insertOrUpdateTable('holidays'
                    , 'date,description,is_public_holiday,schedule_talk_or_aws,comment'
                    , 'description,is_public_holiday,schedule_talk_or_aws,comment'
                    , $_POST);
                $this->send_data(['success' => $res, 'msg' => 'success'], 'ok');

                return;
            } elseif ('delete' === $args[1]) {
                $res = deleteFromTable('holidays', 'date', $_POST);
                $this->send_data(['success' => $res, 'msg' => 'success'], 'ok');

                return;
            }

            $this->send_data(['status' => false, 'msg' => 'unknown endpoint'], 'method not allowed');

            return;
        }
        // NOTE: Usually admin can not approve requests; he can do so for some
        // requests associated with talks.
        elseif ('request' === $args[0]) {
            if ('cancel' === $args[1] || 'delete' === $args[1]) {
                $res = changeRequestStatus(
                    $request['gid'],
                    $request['rid'], $request['created_by'],
                    'CANCELLED'
                );
                $this->send_data(['status' => $res], 'ok');

                return;
            } elseif ('approve' === $args[1]) {
                // make sure that it is a PUBLIC EVENT.
                $ret = actOnRequest($_POST['gid'], $_POST['rid'], 'APPROVE', true, $_POST, getLogin());
                $this->send_data($ret, 'ok');

                return;
            }

            $data = ['flash' => 'Unknown request'];
            $this->send_data($data, 'ok');

            return;
        } elseif ('logins' === $args[0]) {
            if ('all' === $args[1]) {
                $logins = getTableEntries('logins', 'login');
                $this->send_data($logins, 'ok');

                return;
            } elseif ('status' === $args[1]) {
                $status = $args[2];
                $logins = getTableEntries('logins', 'login', "status='$status'");
                $this->send_data($logins, 'ok');

                return;
            } elseif ('title' === $args[1]) {
                $title = $args[2];
                $logins = getTableEntries('logins', 'login', "title='$title'");
                $this->send_data($logins, 'ok');

                return;
            } elseif ('login' === $args[1]) {
                $login = $args[2];
                $data = getTableEntry('logins', 'login', ['login' => $login]);
                $this->send_data($data, 'ok');

                return;
            } elseif ('update' === $args[1]) {
                $this->people('profile', 'update', $_POST['login']);

                return;
            } 
        } 
        elseif ('config' === $args[0]) {
            if('list' === $args[1]) {
                $data = executeQuery("SELECT * FROM config WHERE status!='DELETED'");
                $this->send_data($data, 'ok');
                return;
            } elseif('update' === $args[1] || 'add' === $args[1]) {
                $data = ['success'=>true, 'msg'=>''];
                try {
                    $_POST['status'] = 'VALID';
                    $res = insertOrUpdateTable("config", 'id,value,comment'
                        , "value,comment,status", $_POST);
                    $data['success'] = true;
                } catch (Exception $e) {
                    $data['success'] = false;
                    $data['msg'] .= $e->getMessage();
                }
                $this->send_data($data, 'ok');
                return;
            } elseif('delete' === $args[1] ) {
                $data = ['success'=>true, 'msg'=>''];
                try {
                    $_POST['status'] = 'DELETED';
                    $res = updateTable("config", 'id', "status",$_POST);
                    $data['success'] = true;
                } catch (Exception $e) {
                    $data['success'] = false;
                    $data['msg'] .= $e->getMessage();
                }
                $this->send_data($data, 'ok');
                return;
            }
        }
        $this->send_data(['success'=>false, 'msg'=>'Unknown request: ' . json_encode($args)], 404);
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis API endpoint for acadadmin.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function acadadmin()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $login = getLogin();
        $roles = getRoles($login);

        if (!in_array('ACAD_ADMIN', $roles)) {
            $this->send_data(['success' => false, 'msg' => [$login, $roles]], 'Forbidden');

            return;
        }

        $args = func_get_args();
        $data = [];
        if ('upcomingaws' === $args[0]) {
            if ('upcoming' === $args[1]) {
                return $this->info("upcomingaws");

                return;
            } elseif ('get' === $args[1]) {
                $awsid = $args[2];
                $data = getTableEntry('upcoming_aws', 'id', ['id' => $awsid]);
                $this->send_data($data, 'ok');

                return;
            } elseif ('assign' === $args[1]) {
                $_POST['venue'] = __get__($_POST, 'venue', getDefaultAWSVenue($_POST['date']));

                // If date is a holiday on which AWS are not suppose to be
                // assigned raise error.
                $date = $_POST['date'];
                if(isAWSHoliday($date)) 
                    $data = ['sucess'=>false, 'msg'=> "Can't assign on holiday"];
                else
                    $data = assignAWS($_POST['speaker'], $_POST['date'],
                    $_POST['venue']);
                $this->send_data($data, 'ok');

                return;
            } elseif ('cancel' === $args[1]) {
                $data = cancelAWS($_POST, getLogin());
                $this->send_data($data, 'ok');

                return;
            } elseif ('update' === $args[1]) {
                $data = updateAWS($_POST, getLogin());
                $this->send_data($data, 'ok');

                return;
            } elseif ('weekinfo' === $args[1]) {
                if ('change' === $args[2]) {
                    $res = updateAWSWeekInfo($_POST);
                    $this->send_data($res, 'ok');

                    return;
                } elseif ('removechair' === $args[2]) {
                    $date = __get__($args, 3, '');
                    if (!$date) {
                        $this->send_data(['success' => false, 'msg' => "Invalid date $date"], 'ok');

                        return;
                    }
                    $res = removeAWSChair($date);
                    $this->send_data($res, 'ok');

                    return;
                }
            } elseif ('confirmchair' === $args[1]) {
                $date = __get__($args, 2, '');
                if (!$date) {
                    $data = ['success' => false, 'msg' => "Invalid date $date"];
                    $this->send_data($data, 'ok');

                    return;
                }
                $res = updateTable('upcoming_aws', 'date', 'has_chair_confirmed', ['date' => $date, 'has_chair_confirmed' => 'YES']
                );

                $this->send_data(['success' => $res, 'msg' => ''], 'ok');

                return;
            }

            $this->send_data(['success' => false, 'msg' => 'Unknown endpoint' . json_encode($args)], 'ok');

            return;
        } elseif ('aws' === $args[0]) {
            // These aws has been delivered.
            if ('search' === $args[1]) {
                $q = $args[2];
                $data = [];
                if (strlen($q) > 2) {
                    $data = executeQuery(
                        "SELECT id,title,speaker,date,venue,supervisor_1,status
                        FROM annual_work_seminars 
                        WHERE date LIKE '%$q%' OR speaker LIKE '%$q%' OR title LIKE '%$q%'
                        ORDER BY date DESC LIMIT 20"
                    );
                }
                foreach ($data as &$e) {
                    $e['html'] = '<tt>(' . $e['status'] . ')</tt> '
                        . '<strong>' . getLoginHTML($e['speaker'])
                        . '</strong> on <strong>'
                        . humanReadableDate($e['date'])
                        . "</strong> '" . $e['title'] . "'"
                        . ' <small>(pi/host: ' . $e['supervisor_1'] . ')</small>';
                    $e['summary'] = $e['speaker']
                        . ', ' . humanReadableDate($e['date'])
                        . ", '" . $e['title'] . "'";
                }
                // Send as list. Its a query data.
                $this->send_data_helper($data);

                return;
            } elseif ('delete' === $args[1]) {
                $id = $args[2];
                if ($id && is_int($id)) {
                    $res = updateTable(
                        'annual_work_seminars', 'id', 'status',
                        ['id' => $id, 'status' => 'DELETED']
                    );
                    $this->send_data(
                        ['success' => true, 'msg' => "Deleted AWS with id $id"]
                    );

                    return;
                }
                $this->send_data(['success' => false, 'msg' => "Invalid AWS id $id"]);

                return;
            } elseif ('get' === $args[1]) {
                $data = [];
                if ('all' === $args[2]) {
                    $awses = executeQuery('SELECT date,speaker,title,status FROM annual_work_seminars');
                    foreach ($awses as $aws) {
                        $data[$aws['date']] = $aws;
                    }
                } else {
                    $id = $args[2];
                    $data = executeQuery("SELECT * FROM annual_work_seminars WHERE id='$id'", true);
                    $data = count($data) > 0 ? $data[0] : [];
                }
                $this->send_data($data, 'ok');

                return;
            } elseif ('update' === $args[1]) {
                $data = ['success' => false, 'msg' => ''];

                // Remove previous TCM and supervisors.
                // executeQueryReadonly("UPDATE upcoming_aws
                // SET supervisor_1='', supervisor_2='',
                // tcm_member_1='' , tcm_member_2='', tcm_member_3='', tcm_member_4=''
                // WHERE id='$id'");

                $res = updateTable(
                    'annual_work_seminars', 'id',
                    'title,abstract,status,is_presynopsis_seminar,chair,venue,vc_url'
                    . ',supervisor_1,supervisor_2,tcm_member_1,tcm_member_2,tcm_member_3,tcm_member_4', $_POST, false
                );

                if ($res) {
                    $data['success'] = true;
                    $data['msg'] .= 'Successfully updated.';
                }
                $this->send_data($data, 'ok');

                return;
            }

            $data = ['Unknown AWS request: ' . json_encode($args)];
            $this->send_data($data, 'ok');

            return;
        } elseif ('course' === $args[0]) {
            if ('registration' === $args[1]) {
                // We are sending base64 encoded string because course id can have
                $data = $_POST;
                $course = getRunningCourseByID(
                    $data['course_id'],
                    $data['year'], $data['semester']
                );

                assert($course) or die('No valid course is found.');
                assert(__get__($args, 2, '')) or die('Empty TYPE ');

                // Do not send email when using APP.
                $res = handleCourseRegistration(
                    $course, $data, $args[2],
                    $data['student_id'], getLogin()
                );

                $this->send_data($res, 'ok');

                return;
            }
            if ('grade' === $args[1]) {
                // We are sending base64 encoded string because course id can have
                // Do not send email when using APP.
                assert(isset($_POST['student_id'])) or die('No valid student id');
                $res = assignGrade($_POST, getLogin());
                $this->send_data($res, 'ok');

                return;
            } elseif ('feedback' === $args[1]) {
                $fs = explode('-', base64_decode($args[2]));
                assert(3 == count($fs));
                $data = getCourseFeedbackApi($fs[2], $fs[1], trim($fs[0]));
                $data['payload'] = base64_decode($args[2]);   // debug.
                $this->send_data($data, 'ok');

                return;
            }
        } elseif ('awsroster' === $args[0]) {
            if ('fetch' === $args[1]) {
                $speakers = getAWSSpeakers($sortby = 'pi_or_host');
                foreach ($speakers as &$speaker) {
                    if (!$speaker) {
                        continue;
                    }
                    $extraInfo = getExtraAWSInfo($speaker['login'], $speaker);
                    $speaker = array_merge($speaker, $extraInfo);
                }
                $this->send_data($speakers, 'ok');

                return;
            } elseif ('remove' === $args[1]) {
                $whom = urldecode($args[2]);
                assert($whom);
                $res = removeAWSSpeakerFromList($whom, __get__($_POST, 'reason', ''));
                $this->send_data($res, 'ok');

                return;
            } elseif ('add' === $args[1]) {
                $whom = urldecode($args[2]);
                $res = addAWSSpeakerToList($whom);
                $this->send_data($res, 'ok');

                return;
            } elseif ('available_dates' === $args[1]) {
                $numDates = intval(__get__($args, 2, 5)); // get next 5 slots.
                $datesAvailable = awsDatesAvailable($numDates);
                $this->send_data($datesAvailable, 'ok');

                return;
            }

            $this->send_data(['Unknown request'], 'ok');

            return;
        } elseif ('jc' === $args[0]) {
            if ('update' === $args[1]) {
                $res = updateTable('journal_clubs', 'id', 'title,day,status,time,venue,description,send_email_on_days,scheduling_method', $_POST);
                $ret = ['success' => $res, 'msg' => ''];
                $this->send_data($ret, 'ok');

                return;
            } elseif ('add' === $args[1]) {
                $res = insertIntoTable('journal_clubs', 'id,title,day,status,time,venue,description,send_email_on_days,scheduling_method', $_POST
                );
                $ret = ['success' => $res, 'msg' => ''];
                $this->send_data($ret, 'ok');

                return;
            } elseif ('removeadmin' === $args[1]) {
                $res = removeJCAdmin($_POST, getLogin());
                $this->send_data($res, 'ok');

                return;
            } elseif ('addadmin' === $args[1]) {
                $res = addJCAdmin($_POST, getLogin());
                $this->send_data($res, 'ok');

                return;
            }
        } elseif ('reschedule' === $args[0]) {
            rescheduleAWS();
            $this->send_data(['success' => true, 'msg' => 'Reschedule OK.'], 'ok');

            return;
        }
        $this->send_data(['success' => true, 'msg' => 'Unknown request: ' . json_encode($args)], 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis Handles people related queries and post.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function people()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }

        $args = func_get_args();
        if ('speaker' === $args[0]) {
            if ('add' === $args[1]) {
                $name = splitNameIntoParts($_POST['name']);
                $_POST = array_merge($_POST, $name);
                $ret = addUpdateSpeaker($_POST);
                $this->send_data($ret, 'ok');

                return;
            } elseif ('update' === $args[1]) {
                // Anyone can add/update speaker.
                $name = splitNameIntoParts($_POST['name']);
                $_POST = array_merge($_POST, $name);
                $ret = addUpdateSpeaker($_POST);
                $this->send_data($ret, 'ok');

                return;
            } elseif ('fetch' === $args[1]) {
                $this->__commontasks('speaker', 'fetch', $args[2]);

                return;
            }
        } elseif ('faculty' === $args[0]) {
            if ('list' === $args[1]) {
                $facs = getFaculty();
                $this->send_data($facs, 'ok');

                return;
            }

            // Unusual function: three tasks are handled here.
            if(in_array($args[1], ['update', 'delete', 'add'])) {
                // Only admin can update this.
                if (!hasRoles('ADMIN')) {
                    $this->send_data(['success' => false, 'msg' => "You don't have permission."], 'Forbidden');

                    return;
                }
                $res = adminFacultyTask($_POST, $args[1]);
                $this->send_data($res, 'ok');

                return;
            }
        }
       

        // These are admin functions.
        if (!hasRoles('ADMIN')) {
            $this->send_data(['success' => false, 'msg' => "You don't have permission."], 'Forbidden');
            return;
        }
       
       if ('profile' === $args[0]) {
            $endpoint = __get__($args, 1, 'get');
            if ('get' === $endpoint) {
                // If no userid is given, use current user.
                $user = $args[2];
                $ldap = getUserInfo($user, true);

                $remove = ['fname', 'lname', 'mname'];
                $data = array_diff_key($ldap, array_flip($remove));
                $this->send_data($data, 'ok');

                return;
            } elseif ('update' === $endpoint) {
                $data = ['success' => false, 'msg' => ''];
                $user = $args[2];
                $editables = array_keys(getProfileEditables(true));
                $_POST['login'] = $user;
                $res = updateTable('logins', 'login', $editables, $_POST);
                if ($res) {
                    $data['success'] = true;
                } else {
                    $data['msg'] .= 'Failed to update profile.';
                }
                $this->send_data($data, 'ok');

                return;
            } elseif ('editables' === $endpoint) {
                $this->send_data(getProfileEditables(), 'ok');
                return;
            } elseif ('photo' === $endpoint) {
                $this->me('photo', $args[2]);
                return;
            } elseif ('roles' === $endpoint) {
                $user = $args[2];
                $roles = getRoles($user);
                $allroles = getTableColumnTypes('logins', 'roles');
                $this->send_data(['roles'=>$roles, 'allroles'=>$allroles], 'ok');

                return;
            }
        }

        $this->send_data(['success' => false
            , 'msg' => 'Unknown endpoint: ' . json_encode($args)], 'ok');
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis TALK api.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public function talk()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }
        $args = func_get_args();

        if ('get' === $args[0]) {
            $talkid = $args[1];
            $data = getTalkWithBooking($talkid, getLogin());
            $this->send_data($data, 'ok');

            return;
        } elseif ('update' === $args[0]) {
            $data = updateThisTalk($_POST);
            $this->send_data($data, 'ok');

            return;
        } elseif ('remove' === $args[0] || 'cancel' == $args[0]) {
            $data = removeThisTalk($args[1], getLogin());
            $this->send_data($data, 'ok');

            return;
        }
        $this->send_data(['msg'=>'Unknown request', 'status'=>false], 'ok');
    }

    // Emails.
    public function email()
    {
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }
        $args = func_get_args();

        return $this->__commontasks('email', ...$args);
    }


    public function covid19() 
    {
        $args = func_get_args();
        if($args[0] === 'data') {
            $data = getTableEntries('covid19', 'id', "status='VALID'");
            $this->send_data($data, 'ok');
            return;
        }
    
        if($args[0] === 'update') {
            updateCovidData();
            return;
        }

        // Now we need to authenticate.
        if (!authenticateAPI(getKey())) {
            $this->send_data([], 'Not authenticated');

            return;
        }


        // alerts can also go to /me
        else if($args[0] === 'alert') {
            if($args[1] === 'add') {
                $res = insertIntoTable('covid19_alerts'
                    , "login,latitude,longitude", $_POST);
                $this->send_data(['success'=>$res, 'msg'=>''], 'ok');
                return;
            }
            if($args[1] === 'remove' || $args[1] === 'delete') {
                if($_POST['login'] === getLogin()) {
                    $res = deleteFromTable('covid19_alerts'
                        , "id,login", $_POST);
                    $this->send_data(['success'=>$res, 'msg'=>''], 'ok');
                    return;
                }
                $this->send_data(['success'=>false, 'msg'=>'Permission denied.'], 'ok');
                return;
            }
            else if($args[1] === 'mylist') {
                $login = getLogin();
                $res = getTableEntries('covid19_alerts', "login", "login='$login'");
                $this->send_data($res, 'ok');
                return;
            }
        }

        $data = ['success'=>false
            , 'msg' => 'Unknown endpoint:' . json_encode($args)];
        $this->send_data($data, 'ok');
        return;
    }
}
