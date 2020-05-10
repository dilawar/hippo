<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once BASEPATH . 'autoload.php';
require_once BASEPATH . 'calendar/methods.php';

require_once BASEPATH . 'extra/talk.php';

include_once __DIR__ . '/AdminSharedFunc.php';

class Adminbmv extends CI_Controller
{
    // here we must check that user has permission to access this page.
    public function __construct()
    {
        parent::__construct();

        $roles = getRoles($this->session->userdata('WHOAMI'));

        if (!in_array('BOOKMYVENUE_ADMIN', $roles)) {
            flashMessage("You don't have permission to access this page." . json_encode($roles));
            redirect('user/home');

            return;
        }
    }

    // PURE VIEWS
    public function index()
    {
        $this->home();
    }

    public function loadview($view, $data = [])
    {
        // Make sure each view knows from which controller it has been called.
        $data['controller'] = 'adminbmv';
        $this->template->set('header', 'header.php');
        $this->template->load($view, $data);
    }

    // Show user home.
    public function home()
    {
        $this->loadview('bookmyvenue_admin');
    }

    // BOOKING. Rest of the functions are in Booking traits.
    public function book($arg = '')
    {
        $this->manages_talks($arg);
    }

    public function review()
    {
        $this->loadview('bookmyvenue_admin_request_review');
    }

    public function venues()
    {
        $data = [];
        $this->loadview('bookmyvenue_admin_manages_venues.php', $data);
    }

    public function email_and_docs($arg = '')
    {
        $this->loadview('admin_acad_email_and_docs.php');
    }

    public function manages_speakers()
    {
        $this->loadview('admin_acad_manages_speakers');
    }

    public function bookingrequest($arg = '')
    {
        $this->loadview('user_booking_request', $_POST);
    }

    public function block_venues($arg = '')
    {
        $this->loadview('bookmyvenue_admin_block_venues');
    }

    public function browse_talks($arg = '')
    {
        $this->loadview('bookmyvenue_admin_browse_events');
    }

    public function send_email()
    {
        $this->loadview('admin_acad_send_email');
    }

    public function edittalk($id)
    {
        $data = ['talkid' => $id];
        $this->loadview('admin_manages_talk_update', $data);
    }

    public function edit()
    {
        $this->loadview('bookmyvenue_admin_edit', $_POST);
    }

    // VIEWS WITH ACTION.
    public function edit_action()
    {
        // If is_public_event is set to NO then purge calendar id and event id.
        if ('NO' == $_POST['is_public_event']) {
            if (strlen($_POST['calendar_event_id']) > 1) {
                $_POST['calendar_id'] = '';
                $_POST['calendar_event_id'] = '';
            }
        }

        $where = 'gid,eid';
        if ('Yes' == $_POST['update_all']) {
            $where = 'gid';
        }

        $res = updateTable('events', $where, ['is_public_event', 'class', 'title', 'description', 'status'], $_POST
        );

        if ($res) {
            $gid = $_POST['gid'];
            $eid = $_POST['eid'];
            flashMessage("Succesfully update event(s) - $gid $eid.");
            // TODO: may be we can call calendar API here. currently we are relying
            // on synchronize google calendar feature.
            redirect('adminbmv/home');

            return;
        }

        printWarning('Above events were not updated');

        redirect('adminbmv/home');
    }

    public function block_venue_submit($arg = '')
    {
        $venues = __get__($_POST, 'venue');
        $startDate = __get__($_POST, 'start_date', '');
        $msg = '';

        if (!$startDate) {
            flashMessage('No date selected.');
            redirect('adminbmv/block_venues');

            return;
        }

        $endDate = __get__($_POST, 'end_date', $startDate);
        $msg .= p("User specified range : $startDate to $endDate.");

        $nDays = (strtotime($endDate) - strtotime($startDate)) / 24 / 3600;

        // Both inclusive else a single date wont work.
        $dates = [];
        for ($i = 0; $i <= $nDays; ++$i) {
            $dates[] = dbDate(strtotime($startDate) + $i * 24 * 3600);
        }

        $startTime = __get__($_POST, 'start_time');
        $endTime = __get__($_POST, 'end_time');
        $gid = intval(getUniqueFieldValue('bookmyvenue_requests', 'gid')) + 1;
        $rid = 0;

        foreach ($venues as $venue) {
            $blockedDates = '';
            foreach ($dates as $date) {
                $title = __get__($_POST, 'reason', 'BLOCKED BY ' . whoAmI());
                $class = __get__($_POST, 'class', 'UNKNOWN');

                if (strlen($title) < 8) {
                    flashMessage("Reason for blocking '$title' is too small.
                        At least 8 chars are required. Ignoring ...", 'warning'
                        );

                    continue;
                }

                // We create a request and immediately approve it.
                $user = whoAmI();
                $data = [
                    'gid' => $gid, 'rid' => $rid, 'date' => dbDate($date), 'start_time' => $startTime, 'end_time' => $endTime, 'venue' => $venue, 'title' => $title, 'class' => $class, 'description' => 'AUTO BOOKED BY Hippo', 'created_by' => whoAmI(), 'last_modified_on' => dbDateTime('now'),
                ];

                $res = insertIntoTable('bookmyvenue_requests', array_keys($data), $data);
                $res = approveRequest($gid, $rid);
                if ($res) {
                    flashMessage("Request $gid.$rid is approved and venue has been blocked.");
                }
                ++$rid;

                $blockedDates .= " $date ";
            }

            $msg .= p("$venue is blockd for $blockedDates. ");
        }
        flashMessage($msg);
        redirect('adminbmv/block_venues');
    }

    // Set the controller which called it. Since this view can be called by acad
    // admin as well.
    public function manages_talks($arg = '')
    {
        $this->loadview('admin_manages_talks.php');
    }

    public function update_requests($arg = '')
    {
        $response = strtolower($_POST['response']);
        if ('edit' === $response) {
            flashMessage('You can not modify this request. You must 
                ask its owner to update the booking request.
                ');
            $this->loadview('admin_manages_talks');
        } elseif ('delete' === $response) {
            flashMessage('Deleting request is not implemented yet.');
            $this->loadview('admin_manages_talks');
        } elseif ('do_nothing' === $response) {
            flashMessage('User cancelled the last operation.');
            $this->loadview('admin_manages_talks');
        } else {
            flashMessage("$response is not implemented yet.");
            $this->loadview('admin_manages_talks');
        }
    }

    // Delete this booking.
    public function delete_booking()
    {
        $_POST['status'] = 'INVALID';
        $gid = __get__($_POST, 'gid', 0);
        $eid = __get__($_POST, 'eid', 0);
        $res = updateTable('events', 'eid,gid', 'status', $_POST);
        if ($res) {
            flashMessage('Successfully invalidated booking.');
        }
        $this->loadview('admin_manages_talks');
    }

    // ACTIONS
    public function synchronize_calendar()
    {
        $res = synchronize_google_calendar();
        redirect('adminbmv/home');
    }

    public function venues_action($arg = '')
    {
        $msg = '';
        $res = admin_venue_actions($_POST, $msg);
        flashMessage($msg);
        if ($res) {
            redirect('adminbmv/venues');
        }
    }

    // Views with action.
    public function request_review()
    {
        $whatToDo = $_POST['response'];
        $isPublic = $_POST['isPublic'];
        $warningMsg = '';

        // If admin is rejecting and have not given any confirmation, ask for it.
        if ('REJECT' == $whatToDo) {
            // If no valid response is given, rejection of request is not possible.
            if (strlen($_POST['reason']) < 5) {
                flashMessage('Before you can reject a request, you must provide
                    a valid reason (more than 5 characters long)');
                redirect('adminbmv/home');

                return;
            }
        }

        $events = $_POST['events'];
        $userEmail = '';
        $eventGroupTitle = '';

        if (count($events) < 1) {
            flashMessage('I could not find an event.', 'warning');
            redirect('adminbmv/home');

            return;
        }

        // Else start prepare email.
        $msg = p("Your booking request has been acted upon by '" . whoAmI() . "'.");
        $msg .= '<table border="0">';
        $group = [];
        $err = '';
        foreach ($events as $event) {
            $event = explode('.', $event);
            $gid = $event[0];
            $rid = $event[1];

            // Get event info from gid and rid of event as passed to $_POST.
            $eventInfo = getRequestById($gid, $rid);
            if (!$eventInfo) {
                $warningMsg .= p("No booking request found for gid $gid and rid $rid.");

                continue;
            }

            $userEmail = getLoginEmail($eventInfo['created_by']);
            $eventText = eventToText($eventInfo);

            $group[] = $eventInfo;
            $eventGroupTitle = $eventInfo['title'];

            if ('APPROVE' == $whatToDo) {
                $status = 'APPROVED';
            } else {
                $status = $whatToDo . 'ED';
            }

            $ret = actOnRequest($gid, $rid, $whatToDo);
            if (!$ret['success']) {
                $warningMsg .= p('Failed to act on request.');
                $warningMsg .= p($ret['msg']);

                continue;
            }

            // Check if the status request is changed. If not there is some
            // error.
            $req = getRequestById($gid, $rid);
            if ($req['status'] != $status) {
                $warningMsg .= p("Failed to $status of request $gid.$rid.", true);

                continue;
            }

            $msg .= "<tr><td> $eventText </td><td>" . $status . '</td></tr>';
            changeIfEventIsPublic($gid, $rid, $isPublic);
        }

        $msg .= '</table>';

        // Append user email to front.
        $email = p('Dear ' . loginToText($group[0]['created_by'], true)) . $msg;

        if ($warningMsg) {
            $email .= p('Also note the following glitch. It is probably an important imformation.');
            $email .= $warningMsg;
        }

        // Name of the admin to append to the email.
        $admin = getLoginEmail(whoAmI());

        if ('REJECT' == $whatToDo && strlen($_POST['reason']) > 5) {
            $email .= p("Following reason was given by $admin");
            $email .= $_POST['reason'];
        }

        if ($warningMsg) {
            printWarning($warningMsg);
        } else {
            flashMessage("Successfuly reviewed '$eventGroupTitle'.");
            $res = sendHTMLEmail($email, "Your booking request '$eventGroupTitle' has been $status", $userEmail, 'hippo@lists.ncbs.res.in'
            );
        }
        redirect('adminbmv/home');
    }

    // MANAGES TALK
    public function deletetalk($id)
    {
        $response = $_POST['response'];
        if ('DO_NOTHING' == $response) {
            flashMessage('User cancelled.');
            redirect('adminbmv/manages_talks');
        }

        $res = removeThisTalk($id, whoAmI());
        flashMessage(__get__($res, 'html', ''));
        redirect('adminbmv/manages_talks');
    }

    public function updatetalk($id)
    {
        echo printInfo('Here you can only change the host, class, title and description
            of the talk.');

        $data = ['id' => $id];
        $talk = getTableEntry('talks', 'id', $data);

        echo '<form method="post" action="admin_acad_manages_talks_action_update.php">';
        echo dbTableToHTMLTable('talks', $talk, 'class,coordinator,host,host_extra,title,description', 'submit');
        echo '</form>';
    }

    public function scheduletalk($id)
    {
        // We are sending this to quickbook.php as GET request. Only external_id is
        // sent to page.

        $external_id = getTalkExternalId($id);
        $query = '&external_id=' . $external_id;

        $data = [
            'external_id' => $external_id, 'controller', 'adminbmv',
            ];
        $this->loadview('user_book', $data);
    }

    public function approve()
    {
        $gid = $_POST['gid'];
        $rid = $_POST['rid'];
        $ret = actOnRequest($gid, $rid, 'APPROVE', true);
        if ($ret['success']) {
            flashMessage("Request $gid.$rid is approved and venue has been blocked.");
        } else {
            printErrorSevere("Could not approve request $gid.$rid. Because " . $ret['msg']);
        }

        redirect('adminbmv/home');
    }

    public function manages_speakers_action()
    {
        $res = admin_update_speaker($_POST);
        if ($res['error']) {
            printWarning($res['error']);
        } else {
            flashMessage($res['message']);
        }
        redirect('adminbmv/manages_speakers');
    }

    public function send_email_action()
    {
        $res = admin_send_email($_POST);
        if ($res['error']) {
            printWarning($res['error']);
        } else {
            flashMessage('Sucessfully sent email. ' . $res['message']);
        }

        redirect('adminbmv/manages_talks');
    }

    public function update_talk_action()
    {
        $msg = admin_update_talk($_POST);
        redirect('adminbmv/manages_talks');
    }
}
