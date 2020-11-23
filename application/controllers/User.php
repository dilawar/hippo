<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once BASEPATH . 'autoload.php';

require_once __DIR__ . '/AWS.php';
require_once __DIR__ . '/JC.php';
require_once __DIR__ . '/JCAdmin.php';
require_once __DIR__ . '/Booking.php';
require_once __DIR__ . '/Lab.php';

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis  There are traits AWS, Courses etc. which this class can use;
 * since multiple inherihence is not very straightforward in php.
 */
/* ----------------------------------------------------------------------------*/
class User extends CI_Controller
{
    use AWS;
    use Booking;
    use JC;
    use JCAdmin;
    use Lab;

    public function load_user_view(string $view, array $data = [])
    {
        $data['controller'] = 'user';
        $this->template->set('header', 'header.php');

        // Fill data before sending to view.
        // Only show this section if user is eligible for AWS.
        $data['cUserInfo'] = getLoginInfo(whoAmI(), true, true);
        $this->template->load($view, $data);
    }

    public function index()
    {
        $this->home();
    }

    public function redirect($to = null)
    {
        if ($to) {
            redirect($to);
        }

        if ($this->agent->is_referral()) {
            redirect($this->agent->referrer());
        } else {
            redirect('user/home');
        }
    }

    public function update_supervisors()
    {
        $this->load_user_view('user_update_supervisors');
    }

    public function execute($id)
    {
        $data = ['id' => $id];
        $this->load_user_view('execute', $data);
    }

    // Show user home.
    public function home()
    {
        $this->load_user_view('user');
    }

    // BOOKING. Rest of the functions are in Booking traits.
    public function book($arg = '')
    {
        $this->template->set('header', 'header.php');
        $this->template->load('user_book');
    }

    public function seefeedback($course_id, $semester, $year)
    {
        $this->load_user_view('user_see_feedback', ['course_id' => $course_id, 'semester' => $semester, 'year' => $year]
        );
    }

    public function givefeedback($course_id, $semester, $year)
    {
        if ($course_id && $semester && $year) {
            $this->load_user_view('user_give_feedback', ['course_id' => $course_id, 'semester' => $semester, 'year' => $year]
            );
        } else {
            $msg = "Invalid values: course_id=$course_id, semester=$semester, year=$year";
            printWarning($msg);
            redirect('user/home');
        }
    }

    public function bmv_browse()
    {
        $this->template->set('header', 'header.php');
        $this->template->load('bookmyvenue_browse');
    }

    public function download($filename, $redirect = true)
    {
        if ('/' == $filename[0]) {
            $filepath = $filename;
        } else {
            $filepath = sys_get_temp_dir() . "/$filename";
        }

        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            force_download($filename, $content);
        } else {
            flashMessage("File $filepath does not exist!", 'warning');
        }

        if ($redirect) {
            if ($this->agent->is_referral()) {
                redirect($this->agent->referrer());
            } else {
                redirect('user/home');
            }
        }
    }

    // USER EDITING PROFILE INFO
    public function info($arg = '')
    {
        // Update the page here.
        if ('action' == $arg && $_POST) {
            // Not all login can be queried from ldap. Let user edit everything.
            $where = 'valid_until,honorific,first_name,last_name,title,pi_or_host,specialization' .
                        ',institute,laboffice,joined_on,alternative_email';

            $_POST['login'] = whoAmI();
            $res = updateTable('logins', 'login', $where, $_POST);

            if ($res) {
                echo msg_fade_out('User details have been updated sucessfully');

                // Now send an email to user.
                $info = getUserInfo(whoAmI());
                if (isset($info['email'])) {
                    sendHTMLEmail(arrayToVerticalTableHTML($info, 'details'), 'Your details have been updated successfully.', $info['email']);
                }
            } else {
                echo printWarning('Could not update user details ');
            }
        } elseif ('upload_picture' == $arg && $_POST) {
            $conf = getConf();
            $picPath = $conf['data']['user_imagedir'] . '/' . whoAmI() . '.jpg';
            if ('upload' == $_POST['Response']) {
                $img = $_FILES['picture'];
                if (UPLOAD_ERR_OK != $img['error']) {
                    $errCode = $img['error'];
                    echo minionEmbarrassed('This file could not be uploaded', $img['error']);
                }

                $tmppath = $img['tmp_name'];

                if ($img['size'] > 1024 * 1024) {
                    echo printWarning('Picture is too big. Maximum size allowed is 1MB');
                } else {
                    // Convert to png file and tave to $picPath
                    try {
                        $res = saveImageAsJPEG($tmppath, $picPath);
                        if (!$res) {
                            echo minionEmbarrassed(
                                'I could not upload your image (allowed formats: png, jpg, bmp)!'
                            );
                        } else {
                            echo printInfo('File is uploaded sucessfully');
                        }
                    } catch (Exception $e) {
                        echo minionEmbarrassed(
                            'I could not upload your image. Error was ', $e->getMessage());
                    }
                }
            }
        }

        $this->template->set('header', 'header.php');
        $this->template->load('user_info');
    }

    // Show courses.
    public function courses($arg = '')
    {
        $this->template->set('header', 'header.php');
        $this->template->load('user_manages_courses');
    }

    // Edit update courses.
    public function manage_course($action = '')
    {
        // There must be a course id.
        $cid = __get__($_POST, 'course_id', '');
        assert($cid);

        $action = strtolower($action);
        if (!$cid) {
            flashMessage('No course selected!', 'warning');
            redirect('user/courses');
        }

        $course = getRunningCourseByID($cid);
        if ('register' == $action) {
            $_POST['student_id'] = whoAmI();
            $res = registerForCourse($course, $_POST);

            // If user has asked for AUDIT but course does not allow auditing,
            // do not register and raise and error.
            if (!$res['success']) {
                flashMessage($res['msg']);
                redirect('user/courses');
            }
            echo flashMessage($res['msg']);
            redirect('user/courses');
        } elseif ('feedback' === $action) {
            echo 'Give feedback';
        } elseif ('drop' === $action) {
            // Using the same function to AUDIT/CREDIT/DROP courses. Function
            // registerForCourse is also called by App API.
            $_POST['status'] = 'DROPPED';
            $this->manage_course('register');
        } else {
            flashMessage("Not implemented yet $action");
            redirect('user/courses');
        }

        redirect('user/courses');
    }

    public function update_supervisor_submit()
    {
        if (trim($_POST['email']) && trim($_POST['first_name'])) {
            $res = insertOrUpdateTable('supervisors', 'email,first_name,middle_name,last_name,affiliation,url', 'first_name,middle_name,last_name,affiliation,url', $_POST
            );

            if ($res) {
                flashMessage('Successfully added/updated supervisor to list.');
            } else {
                printWarning('Could not add or update supervisor.');
            }
        }
        redirect('user/home');
    }

    // submit poll.
    public function submitpoll()
    {
        $course_id = $_POST['course_id'];
        $semester = $_POST['semester'];
        $year = $_POST['year'];

        if (!($year && $semester && $year)) {
            $msg = 'Either semester, year or course_id was invalid.';
            $msg .= json_encode($_POST);
            printWarning($msg);
            redirect('user/home');

            return;
        }

        $external_id = "$year.$semester.$course_id";

        // Keep data in array to table updating.
        $entries = [];
        foreach ($_POST as $key => $val) {
            preg_match('/qid\=(?P<qid>\d+)/', $key, $m);
            if ($m) {
                $entry = ['external_id' => $external_id, 'question_id' => $m['qid'], 'login' => whoAmI(), 'response' => $val,
                        ];
                $entries[] = $entry;
            }
        }

        // Update poll_response table now.
        $msg = '';
        $error = false;
        foreach ($entries as $entry) {
            // $msg .= json_encode($entry);
            $res = insertOrUpdateTable('poll_response', 'login,question_id,external_id,response', 'response', $entry);
            if (!$res) {
                $msg .= 'Faieled to record response for question id ' . json_encode($entry);
                $error = true;
            }
        }

        if ($error) {
            printWarning($error);
        }

        // flashMessage( $msg );

        redirect('user/courses');
    }

    // Submit feedback.
    public function submitfeedback()
    {
        $course_id = $_POST['course_id'];
        $semester = $_POST['semester'];
        $year = $_POST['year'];

        if (!($year && $semester && $course_id)) {
            $msg = 'Either semester, year or course_id was invalid.';
            $msg .= json_encode($_POST);
            printWarning($msg);
            redirect('user/courses');

            return;
        }

        // Keep data in array for table updating.
        $entries = [];
        foreach ($_POST as $key => $val) {
            // Check if we get instructor id as well. If not its empty.
            preg_match('/qid\=(?P<qid>\d+)(\&instructor=(?P<instructor>\S+?@\S+))?/', $key, $m);
            if ($m) {
                $entry = ['year' => $year, 'semester' => $semester, 'course_id' => $course_id, 'question_id' => $m['qid'], 'login' => whoAmI(), 'response' => $val
                    // Instructor is optional. Not all questions are instructor
                    // specific. We are allowed to entry empty value in
                    // 'instructor' field.
                    , 'instructor_email' => str_replace('+dot+', '.', __get__($m, 'instructor', '')),
                ];
                $entries[] = $entry;
            }
        }

        // Update poll_response table now.
        $msg = '';
        $error = false;
        foreach ($entries as $entry) {
            $msg .= json_encode($entry);
            $res = insertOrUpdateTable('course_feedback_responses', 'login,question_id,year,semester,course_id,instructor_email,response', 'year,semester,response', $entry
            );

            if (!$res) {
                $msg .= 'Faieled to record response for question id ' . json_encode($entry);
                $error = true;
            }
        }

        if ($error) {
            flashMessage($error);
        } else {
            flashMessage('Successfully recorded your response.');
        }
        redirect('user/courses');
    }

    public function downloadaws($date, $speaker = '')
    {
        $ret = pdfFileOfAWS($date, $speaker);
        $pdffile = $ret['pdf'];
        if (file_exists($pdffile)) {
            $this->download($pdffile, false);
            echo '<script type="text/javascript" charset="utf-8">
                window.onload = function() { window.close(); }; 
            </script>';
        } else {
            echo flashMessage($ret['error'] . '<br />' . __get__($ret, 'stdout', ''));
        }
    }

    public function downloadtalk($date, $id)
    {
        $pdffile = generatePdfForTalk($date, $id);
        $this->download($pdffile, false);

        echo '<script type="text/javascript" charset="utf-8">
                window.onload = function() {
                    window.close();
                };
            </script>';
    }

    public function downloadtalkical($date, $id)
    {
        return;
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect('welcome');
    }

    public function execute_submit()
    {
        $login = $_POST['login'];
        $pass = $_POST['password'];
        $id = $_POST['id'];
        $auth = authenticate($login, $pass);
        if (!$auth['success']) {
            echo flashMessage('Authentication failed. Try again.');
            $this->load_user_view('execute', $_POST);

            return;
        }
        $query = getTableEntry('queries', 'id', $_POST);
        $res = executeURlQueries($query['query']);
        if ($res) {
            $_POST['status'] = 'EXECUTED';
            $res = updateTable('queries', 'id', 'status', $_POST);
            if ($res) {
                echo flashMessage('Success! ');
            }
        }
        $this->load_user_view('user');
    }

    public function upload_to_db($tablename, $unique_key, $redirect = 'home')
    {
        $filename = $_FILES['spreadsheet']['tmp_name'];
        $data =
        $data = read_spreadsheet($filename);
        $header = $data[0];
        $data = array_slice($data, 1);

        $query = '';
        foreach ($data as $row) {
            if (!$row or count($row) != count($header)) {
                continue;
            }

            $toupdate = [];
            $allkeys = [];
            $keyval = [];
            foreach ($header as $i => $key) {
                if (!$key) {
                    continue;
                }

                $val = $row[$i];
                if (!$val or 'NULL' == $val) {
                    continue;
                }

                $allkeys[] = $key;
                if ($key != $unique_key) {
                    $toupdate[] = $key;
                }

                $keyval[$key] = $val;
                $query .= "$key='$val' ";
            }

            $query .= ';';
            if (getTableEntry($tablename, $unique_key, $keyval)) {
                $res = updateTable($tablename, $unique_key, $toupdate, $keyval);
            } else {
                $res = insertIntoTable($tablename, $allkeys, $keyval);
            }
        }

        // flashMessage( $query );
        redirect("user/$redirect");
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis  Post a comment.
     *
     * @Param $data
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public static function postComment($data)
    {
        $data['id'] = getUniqueID('comment');
        $data['last_modified_on'] = dbDateTime('now');
        $data['created_on'] = dbDateTime('now');
        $data['status'] = 'VALID';

        $updatable = 'commenter,status,external_id,comment,last_modified_on';
        $res = insertOrUpdateTable('comment', 'id,created_on,' . $updatable, $updatable, $data);

        if ($res) {
            return ['success'];
        }

        return ['failure'];
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis  Get notification for given user.
     *
     * @Param $ci  An object of CI_Controller
     * @Param $user Username.
     * @Param $limit Limit.
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    public static function getNotifications($ci, string $user, int $limit = 10): array
    {
        $data = getTableEntries('notifications', 'created_on', "login='$user' AND status='VALID' AND 
                    created_on > DATE_SUB(NOW(), INTERVAL 2 MONTH)"
        );

        return $data;
    }

    public static function subscribeToForum($ci, $login, $board)
    {
        $ci->db->replace('board_subscriptions', ['login' => $login, 'board' => $board]);
        $ci->db->replace('board_subscriptions', ['login' => $login, 'board' => 'emergency']);

        return true;
    }

    public static function unsubscribeToForum($ci, $login, $board)
    {
        $ci->db->where('login', $login)
           ->where('board', $board)
           ->delete('board_subscriptions');

        return true;
    }

    public static function getBoardSubscriptions($ci, string $login): array
    {
        $q = $ci->db->select('board')
                ->get_where('board_subscriptions', ['login' => $login, 'status' => 'VALID']
            );
        $data = array_map(function ($x) { return $x['board']; }, $q->result_array());

        return $data;
    }

    public static function deleteComment($id)
    {
        $res = updateTable('comment', 'id', 'status', ['id' => $id, 'status' => 'DELETED']);
        if ($res) {
            return ['success'];
        }

        return ['failure'];
    }

    /* --------------------------------------------------------------------------
     *   API keys related.
     * ----------------------------------------------------------------------------*/
    public function generate_key()
    {
        $user = whoAmI();
        $res = genererateNewKey($user);
        if ($res) {
            flashMessage('Successfully added new key');
        }
        redirect('user/info');
    }

    public function revoke_key($keyid)
    {
        $res = deleteFromTable('apikeys', 'id', ['id' => $keyid]);
        if ($res) {
            flashMessage('Successfully revoked.');
        }
        redirect('user/info');
    }
}
