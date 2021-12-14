<?php

require_once __DIR__ . '/methods.php';
require_once BASEPATH . '/extra/courses.php';

/*
 * Assign AWS
 */
function assignAWS(string $speaker, string $date, string $venue = ''): array
{
    // Remove whitespaces. It happens sometimes that input field contains
    // whitespace.
    $speaker = trim($speaker);

    $res = ['success' => false, 'msg' => ''];

    if (!$venue) {
        $venue = getDefaultAWSVenue($date);
    }

    if ($speaker && getLoginInfo($speaker) && strtotime($date) > strtotime('-7 day')) {
        $aws = getUpcomingAWSOfSpeaker($speaker);
        if ($aws) {
            $res['msg'] = "$speaker already has AWS scheduled. Doing nothing.";

            return $res;
        }

        $r1 = acceptScheduleOfAWS($speaker, $date, $venue);
        $awsID = $r1['awsid'];
        if ($awsID > 0) {
            $res['success'] = true;
            $res['msg'] = 'Successfully assigned. ';

            // Create a booking.
            $nAWS = getTotalUpcomingAWSOnThisMonday($date);

            $aws = getTableEntry('upcoming_aws', 'id', ['id' => $awsID]);
            $aws['time'] = dbTime(strtotime($aws['time']) + ($nAWS - 1) * 30 * 60);

            $r2 = bookAVenueForThisAWS($aws, true);
            $res['msg'] .= p($r2['msg']);

            // Don't rescheduleAWS. It will change the rest of the entries for the week.
            // rescheduleAWS( );

            // Send email to user.
            $st = notifyUserAboutUpcomingAWS($speaker, $date, $awsID);
            if (!$st['success']) {
                $res['msg'] .= $st['msg'];
            }

            return $res;
        }

        $res['success'] = false;
        $res['msg'] .= $r1['msg'] . $r2['msg'];

        return $res;
    }
    $res['success'] = false;
    $res['msg'] = "Invalid speaker '$speaker' or date '$date' is in past."
        . ' Could not assign AWS.';

    return $res;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Cancel a given AWS.
 *
 * @Param $data array with required data.
 * @Param $bywhom Who has removed it.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function cancelAWS(array $data, string $bywhom = 'HIPPO'): array
{
    // If ID is given then fetch the aws.
    $aws = [];
    if (__get__($data, 'id', '')) {
        $aws = getTableEntry('upcoming_aws', 'id', $data);
    } else {
        $aws = getTableEntry('upcoming_aws', 'speaker,date', $data);
    }

    $speaker = $data['speaker'];
    $date = $data['date'];
    $reason = __get__($data, 'reason', 'None given. So rude!');

    $res = clearUpcomingAWS($aws['speaker'], $aws['date']);
    $piOrHost = getPIOrHost($aws['speaker']);
    $final = ['msg' => $res['msg'], 'status' => false];

    if ($res) {
        $final['msg'] .= " Successfully cleared upcoming AWS of $speaker on $date.";

        // Notify the hippo list.
        $admin = whoAmI();
        $msg = '<p>Hello ' . loginToHTML($aws['speaker']) . '</p>';
        $msg .= "<p>
            Your upcoming AWS schedule has been removed by Hippo admin ($bywhom).
            If this is a  mistake, please write to acadoffice@ncbs.res.in
            immediately.";

        $msg .= p('Following reason was given by admin.');
        $msg .= p($reason);
        $msg .= p('The AWS schedule which is removed is the following:');
        $msg .= arrayToVerticalTableHTML($aws, 'info');
        $cclist = 'acadoffice@ncbs.res.in,hippo@lists.ncbs.res.in';

        if (filter_var($piOrHost, FILTER_VALIDATE_EMAIL) ) {
            $cclist .= ",$piOrHost";
        }

        sendHTMLEmail(
            $msg,
            "Your ($speaker) upcoming AWS has been removed.",
            $to = getLoginEmail($data['speaker']),
            $cclist
        );
        $final['status'] = true;

        return $final;
    }

    return $final;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Update a given AWS.
 *
 * @Param $data
 * @Param $by
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function updateAWS(array $data, string $by = 'HIPPO'): array
{
    $id = $data['id'];
    executeQueryReadonly("UPDATE upcoming_aws 
        SET supervisor_1='', supervisor_2='', 
        tcm_member_1='' , tcm_member_2='', tcm_member_3='', tcm_member_4=''
        WHERE id='$id'");

    $res = updateTable(
        'upcoming_aws', 'id',
        'abstract,title,is_presynopsis_seminar,supervisor_1,supervisor_2'
            . ',tcm_member_1,tcm_member_2,tcm_member_3,tcm_member_4'
        , $data
    );

    if ($res) {
        return ['success' => true, 'msg' => 'Successfully updated upcoming AWS entry'];
    }

    return ['msg' => 'I could not update the entry.', 'success' => false];
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Update this registration.
 *
 * @Param array
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function updateRegistration(arrray $data): array
{
    $res = updateTable(
        'course_registration', 'student_id,year,semester,course_id',
        'type,status,grade',
        $data
    );
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Handle registration requests.
 *
 * @Param $course
 *   Array representing course (from table running courses)
 * @Param $data
 * @Param $what
 *   AUDIT, CREDIT or DROP
 * @Param $student
 *   Login id student. If it an email, get login id.
 * @Param $bywhom
 *   Login of admin.
 * @Param $sendEmail
 *   Whether to send email to student.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function handleCourseRegistration(array $course, array $data, string $what, string $student, string $bywhom, $sendEmail = true
): array {
    $what = strtoupper($what);

    // if student is not a valid email id, then check that student is registered
    // on Hippo.
    $login = [];
    if (!__substr__('@', $student)) {
        $login = getLoginInfo($student, true, true);
        if (!$login) {
            return ['success' => false, 'msg' => "Unknown user $student"];
        }
    } else {
        $to = $student;
    }

    $ret = ['msg' => '', 'success' => true];
    $data['last_modified_on'] = dbDateTime('now');

    // If new registration, get the current timestamp else use old one.
    $data['registered_on'] = __get__($data, 'registered_on', dbDateTime('now'));

    if ('DROP' === $what) {
        // a course may not allow AUDIT
        // $data['type'] = $course['is_audit_allowed'] === 'YES' ? 'AUDIT' : 'CREDIT';
        $data['status'] = 'DROPPED';
    } elseif ('CREDIT' === $what) {
        $data['type'] = 'CREDIT';
        $data['status'] = 'VALID';
    } elseif ('AUDIT' === $what) {
        $data['type'] = 'AUDIT';
        $data['status'] = 'VALID';
    } else {
        return ['success' => false, 'msg' => "Unknown course registration task $what"];
    }

    $data['year'] = $course['year'];
    $data['semester'] = $course['semester'];
    $data['course_id'] = $course['course_id'];
    assert($data['course_id']) or die('Invalid course id ' . $course['course_id']);
    $cid = $course['course_id'];

    // If user has asked for AUDIT but course does not allow auditing,
    // do not register and raise and error.
    if ('NO' === $course['is_audit_allowed'] && 
        'AUDIT' === __get__($data, 'type', 'CREDIT')) {
        $ret['success'] = false;
        $ret['msg'] = " The course $cid does not allow <tt>AUDIT</tt>.";

        return $ret;
    }

    // If number of students are over the number of allowed students
    // then add student to waiting list and raise a flag.
    if (intval($course['max_registration']) > 0 && $what !== 'DROP') {
        // FIXME: This can be improved by just getting the counts.
        $numEnrollments = count(getCourseRegistrations($cid, $course['year'], $course['semester']));
        if (intval($numEnrollments) >= intval($course['max_registration'])) {
            $data['status'] = 'WAITLIST';
            $ret['success'] = true;
            $ret['msg'] .= p("
                Number of registrations have reached its limit. I've added you to 
                <tt>WAITLIST</tt>. Please contact academic office or your instructor about 
                the policy on <tt>WAITLIST</tt>. By default, <tt>WAITLIST</tt> means 
                <tt>NO REGISTRATION</tt>."
            );
        }
    }

    $data['student_id'] = $student;

    // ON AUDIT AND CREDIT
    $wherekeys = 'student_id,semester,year,type,course_id,registered_on,last_modified_on';
    $updatekeys = 'type,last_modified_on,status';
    if ('DROP' !== $what) {
        // Check if any course is colliding with existing registration.
        $myRegistrations = getMyCourses($data['semester'], $data['year'], $student);
        $collision = collisionWithMyRegistrations($course, $myRegistrations);
        if ($collision['collision']) {
            $ret['msg'] .= ' Collision with the course: '
                . getCourseName($collision['with']['course_id']);
            $ret['success'] = false;

            return $ret;
        }
    }
    else {
        // ON drop, we don't care about the type.
        $wherekeys = 'student_id,semester,year,type,course_id,registered_on';
        $upadtekeys = 'last_modified_on,status';
    }

    $r = false;
    $warning = '';
    try {
        $r = insertOrUpdateTable('course_registration', $wherekeys, $updatekeys, $data);
    } catch (Exception $e) {
        $warning .= $e->getMessage();
    }

    if (!$r) {
        $ret['msg'] .= p("Failed to $what the course " . $data['course_id']) . p($warning);
        $ret['msg'] .= json_encode($data);
        $ret['success'] = false;

        return $ret;
    }

    // Update waiting lists.
    if ('DROPPED' === strtoupper($data['status'])) {
        updateCourseWaitlist($data['course_id'], $data['year'], $data['semester']);
        // Drop all feedback as well.
        withdrawCourseFeedback($data['student_id'], $data['course_id'], $data['semester'], $data['year']);
    }

    $ret['success'] = true;
    $ret['msg'] .= "Successfully $what course " . $data['course_id'];

    if ($sendEmail) {
        // Send email to user.
        if ($login) {
            $msg = p('Dear ' . arrayToName($login, true));
            $to = $login['email'];
        } else {
            $msg = p("Dear $to");
        }

        if ($login !== $bywhom) {
            $msg .= p("Acad admin ($bywhom) has successfully updated your courses.");
        } else {
            $msg .= p('You have successfully updated your courses.');
        }

        $sem = getCurrentSemester();
        $year = getCurrentYear();

        // User courses and slots.
        $myCourses = getMyCourses($sem, $year, $user = $data['student_id']);
        $msg .= p('Your courses this semester are following.');
        if (count($myCourses) > 0) {
            foreach ($myCourses as $c) {
                $msg .= arrayToVerticalTableHTML($c, 'info', '', 'grade,grade_is_given_on');
            }
        } else {
            $msg .= p('No course is found.');
        }

        sendHTMLEmail($msg, 'Successfully ' . $what . " ed the course $cid", $to);
        $ret['msg'] .= " Email is sent to the student.";
    }

    return $ret;
}

function handleCourseRegistrationExtrnal(array $course, array $data, string $what, string $bywhom, $sendEmail = true
): array {

    // create a dummy login.
    $data['login'] = $data['email'];
    $data = array_merge($data, splitName($data['name']));
    $data['type'] = 'VISITOR';
    $keys = 'email,first_name,midlde_name,last_name,institute,title';
    $r = insertOrUpdateTable('logins', "login,$keys", $keys, $data);
    if(! $r) 
        return ['success'=>false, 'msg'=> "Failed to create account." . json_encode($r)];
    return handleCourseRegistration($course, $data, $what, $data['login'], $bywhom, true);
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Admin acad.
 *
 * @Param $data
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function assignGrade(array $data, string $by = 'HIPPO'): array
{
    $login = $data['student_id'];
    $grade = $data['grade'];

    $data['grade_is_given_on'] = dbDateTime('now');

    $ret = ['success' => true, 'msg' => ''];
    $st = updateTable(
        'course_registration',
        'student_id,semester,year,course_id',
        'grade,grade_is_given_on',
        $data
    );

    $ret['success'] = $st;

    if ($st) {
        $ret['msg'] .= "Successfully assigned $grade for $login.";

        // Send email.
        $subject = 'Grade has been assigned to one of your course';
        $body = p('Dear ' . loginToText($data['student_id']));
        $body .= p("Academic admin ($by) has just assigned grade to one of your courses.");

        $infoData = ['course' => getCourseName($data['course_id']), 'year/semester' => $data['year'] . '/' . $data['semester'], 'grade' => $data['grade'], 'assigned_on' => $data['grade_is_given_on'], 'assigned_by' => $by,
        ];
        $body .= arrayToVerticalTableHTML($infoData, 'info');

        $body .= p(
            "Note that a grade 'X' means that previous grade has 
            been removed. Academic admin may have made an error."
        );

        $body .= p(
            'If there is any mistake, please contact academic office.
            <strong>Note that grades on HIPPO are not final official record.
            Your final grade is kept by Academic office. Hippo is a platform 
            to inform you about your grade. In case of discrepency, the record 
            at Academic Office shall be deemed correct.
            </strong>'
        );
        sendHTMLEmail($body, $subject, getLoginEmail($login));
    } else {
        $ret['msg'] .= "Could not assign grade for $login. <br /> ";
    }

    return $ret;
}

function getExtraAWSInfo(string $login, array $speaker = []): array
{
    $upcomingAWS = getUpcomingAWSOfSpeaker($login);
    $pastAWSes = getAwsOfSpeaker($login);

    // Get PI/HOST and speaker specialization.
    $pi = getPIOrHost($login);
    $specialization = 'UNKNOWN';
    if ($pi) {
        $specialization = getSpecialization($login, $pi);
    }

    // This user may have not given any AWS in the past. We consider their
    // joining date as last AWS date.
    if (count($pastAWSes) > 0) {
        $lastAws = $pastAWSes[0];
        $lastAwsDate = $lastAws['date'];
    } else {
        if (!__get__($speaker, 'joined_on', '')) {
            $speaker = getLoginInfo($login);
        }
        $lastAwsDate = $speaker['joined_on'];
    }

    return ['specialization' => $specialization, 'pi_or_host' => $pi, 'last_aws_date' => $lastAwsDate, 'days_since_last_aws' => (strtotime('today') - strtotime($lastAwsDate)) / 3600 / 24, 'upcoming_aws' => __get__($upcomingAWS, 'date', ''), 'num_aws' => count($pastAWSes)];
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Retrun available AWS dates.
 *
 * @Param $numSlots Maximum number of dates to return. If -1 is given, 20
 * weeks are returned.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function awsDatesAvailable(int $numSlots): array
{
    if ($numSlots < 0) {
        $numSlots = 20;
    }

    // Find next $numSlots available slots.
    $dates = [];
    // From next week + 3 weeks.
    $startDate = strtotime('next monday') + 3 * 7 * 86400;
    for ($i = 0; $i < 10 * $numSlots; ++$i) {
        $date = dbDate($startDate + $i * 7 * 86400);
        // Check if a slot is available.
        $nAWS = getTotalUpcomingAWSOnThisMonday($date);
        if ($nAWS < maxAWSAllowed()) {
            $dates[] = [$nAWS, $date];
        }
        if (count($dates) >= $numSlots) {
            break;
        }
    }

    return $dates;
}

function insertCourseMetadata(array $data)
{
    $data['status'] = 'VALID';
    $res = insertOrUpdateTable(
        'courses_metadata',
        'id,name,credits,description,status'
        . ',instructor_1,instructor_2,instructor_3'
        . ',instructor_4,instructor_5,instructor_6,instructor_extras,comment',
        'status',
        $data
    );

    return $res;
}

function updateCourseMetadata(array $data)
{
    $res = updateTable(
        'courses_metadata',
        'id',
        'name,credits,description'
        . ',instructor_1,instructor_2,instructor_3'
        . ',instructor_4,instructor_5,instructor_6,instructor_extras,comment',
        $data
    );

    return $res;
}

function getSlots(): array
{
    $tiles = getTableEntries('slots', 'groupid');
    $result = [];
    foreach ($tiles as &$tile) {
        $result[$tile['groupid']][] = $tile;
    }

    foreach ($result as $slotGroup => &$vals) {
        $html = [];
        foreach ($vals as $val) {
            $html[] = $val['day'];
        }
        $html = implode('-', $html) . '; ' . $vals[0]['start_time']
            . ' to ' . $vals[0]['end_time'];
        $result[$slotGroup]['html'] = $html;
    }

    return $result;
}

function deleteRunningCourse(array $data): array
{
    $ret = ['success' => false, 'msg' => ''];
    if (strlen($data['id']) > 0) {
        $msg = '';
        $res = deleteFromTable('courses', 'id', $data);
        if ($res) {
            deleteBookings($data['id']);
            $msg .= 'Successfully deleted entry';
            // Remove all enrollments.
            $year = getCurrentYear();
            $sem = getCurrentSemester();
            $res = deleteFromTable(
                'course_registration', 'semester,year,course_id',
                ['year' => $year, 'semester' => $sem, 'course_id' => $data['course_id']]
            );

            if ($res) {
                $msg .= 'Successfully removed  all enrollments. I have not notifiied the students.';
                $ret['success'] = true;
                $ret['msg'] = $msg;

                return $ret;
            }
        }
        $ret['success'] = false;
        $ret['msg'] = $msg;
    } else {
        $ret['msg'] = 'Invalid course ID.';
    }

    return $ret;
}

function addRunningCourse(array $data, string $msg = ''): array
{
    $msg = '';
    $updatable = 'semester,year,start_date,end_date,max_registration,'
        . 'allow_deregistration_until,is_audit_allowed,slot,venue,note'
        . ',url,ignore_tiles';
    if (strlen($data['course_id']) > 0) {
        $id = getCourseInstanceId($data['course_id'], $data['semester'], $data['year']);
        $data['id'] = $id;
        $res = insertIntoTable('courses', "id,course_id,$updatable", $data);
        if (!$res) {
            $msg .= 'Could not add course to list.';
        } else {
            $res = addCourseBookings($data['id']);
            $msg .= 'Successfully added course. Blocked venue as well.';
        }
    } else {
        $msg .= 'Could ID can not be empty';
    }

    return ['success' => true, 'msg' => $msg];
}

function updateRunningCourse(array $data, string $msg = ''): array
{
    $updatable = 'semester,year,start_date,end_date,max_registration,'
        . 'allow_deregistration_until,is_audit_allowed,slot,venue,note'
        . ',url,ignore_tiles';
    $res = updateTable('courses', 'id', $updatable, $data);
    if ($res) {
        $res = updateBookings($data['id']);
        $msg .= 'Updated running course ' . $data['course_id'] . '.';
    }

    return ['success' => true, 'msg' => $msg];
}

function addOrUpdateRunningCourse(array $data, string $response): array
{
    // NOTE: the start date of course determines the semester and year
    // of a course. The end date can spill into next semester.
    // Especially in AUTUMN semester. What can I do, this is NCBS!
    $data['semester'] = getSemester($data['start_date']);
    $data['year'] = getYear($data['start_date']);

    // Check if any other course is running on this venue/slot between given
    // dates.
    $startDate = $data['start_date'];
    $endDate = $data['end_date'];

    $coursesAtThisVenue = getCoursesAtThisVenueSlotBetweenDates(
        $data['venue'], $data['slot'], $startDate, $endDate
    );

    $collisionCourses = array_filter(
        $coursesAtThisVenue,
        function ($c) use ($data) {
            return $c['course_id'] != $data['course_id'];
        }
    );

    $msg = '';
    if (count($collisionCourses) > 0) {
        foreach ($collisionCourses as $cc) {
            $msg .= 'Following course is already assigned at this slot/venue';
            $msg .= arrayToVerticalTableHTML($cc, 'info');
            $msg .= '<br>';
        }

        return ['success' => false, 'msg' => $msg];
    }

    // hack: null, '', and 0 are messed up in the app.
    $data['note'] = $data['note'] ? $data['note']: ' ';

    // No collision. Add or update now.
    if ('add' === $response) {
        return addRunningCourse($data, $msg);
    } elseif ('update' === $response) {
        return updateRunningCourse($data, $msg);
    } else {
        $msg .= "Unknown task '$response'";

        return ['success' => true, 'msg' => $msg];
    }
}

function assignAWSChair($chair, $date)
{
    $ret = ['success'=>true, 'msg'=>''];

    $res = updateTable(
        'upcoming_aws', 'date', 'chair', 
        ['date'=>$date, 'chair'=>$chair]
    );

    if(! $res ) {
        $ret['success'] = false;
        $ret['msg'] = p("Failed to assign chair on $date");
        return $ret;
    }

    $login = findAnyoneWithEmail($chair);
    $query = insertClickableQuery(
        $chair, 'upcoming_aws.-1',
        "UPDATE upcoming_aws SET has_chair_confirmed='YES' WHERE date='$date'"
    );

    $macros = ['CHAIR' => arrayToName($login), 'AWS_DATE' => $date
        , 'CONFIRMATION_LINK' => queryHashToClickableURL($query['hash'])];

    $temp = emailFromTemplate('NOTIFY_AWS_CHAIR', $macros);
    $res =  sendHTMLEmail(
        $temp['email_body'],
        "You have been assigned the AWS chair on $date",
        $chair, $temp['cc']
    );

    if(! $res['success']) {
        $ret['msg'] .= p("Failed to notify chair") . $res['msg'];
    }

    return $ret;
}

function updateAWSWeekInfo($data): array
{
    $res = ['success' => true, 'msg' => ''];

    $date = __get__($data, 'date', '');
    if (!$date)
        return ['success' => false, 'msg' => 'Invalid date'];

    $newchair = false;
    $awsRepr = getTableEntry('upcoming_aws', 'date', ['date' => $date]);

    if ($awsRepr['chair'] !== $data['chair']) {
        $data['has_chair_confirmed'] = 'NO';
        $newchair = true;
        $res['msg'] .= p('New chair ' . $data['chair']);
    } else {
        $data['has_chair_confirmed'] = $awsRepr['has_chair_confirmed'];
    }

    // If new chair then send him/her an email.
    if ($data['chair'] and $newchair) {
        $res = assignAWSChair($data['chair'], $data['date']);
    }

    // update rest of the entries for the week
    $r1 = updateTable('upcoming_aws', 'date'
        , 'venue,chair,has_chair_confirmed,vc_url,vc_extra'
        , $data);

    if(! $r1) {
        $res['success'] = false;
        $res['msg'] .= p("Failed to update upcoming aws information.");
    }

    return $res;
}

function removeAWSChair($date) : array 
{
    $ret = ['success'=>false, 'msg'=>''];
    $aws = getTableEntry('upcoming_aws', 'date', ['date'=>$date]);

    $oldchair = $aws['chair'];
    if($oldchair) {
        $mail = p("Academic Admin has removed you as the AWS chair on $date.");
        $mail .= p("If this is a mistake, please let the Academic Office know.");
        $res = sendHTMLEmail("You are no longer the  AWS chair on $date", $mail, $oldchair);
        $ret['msg'] .= $res['msg'];
    }

    $r = updateTable(
        'upcoming_aws', 'date', 'chair,has_chair_confirmed',
        ["date"=>$date, 'chair'=>'', "has_chair_confirmed"=>"NO"]
    );
    $ret['success'] = $r;
    return $ret;
}

function removeJCAdmin($data, $who) 
{
    $ret = ['success'=>true, 'msg'=>''];
    $data['subscription_type'] = 'NORMAL';
    $r = updateTable('jc_subscriptions', 'jc_id,login', 'subscription_type', $data);
    if($r) {
        $email = getLoginEmail($data['login']);
        $body = p("Hi " . getLoginHTML($data['login']));
        $body .= p(
            "You have been made admin by $who. If this is a mistake 
            please write to Adademic Office."
        );

        $r1 = sendHTMLEmail("You have been made admin of " . $data['jc_id'], $body, $email);
        if(! $r1) {
            $ret['msg'] .= p("Failed to send email");
        }
    }
    $ret['success'] = $r;
    return $ret;
}

function addJCAdmin($data, $who) 
{
    $ret = ['success'=>true, 'msg'=>''];
    $data['subscription_type'] = 'ADMIN';
    $r = updateTable('jc_subscriptions', 'jc_id,login', 'subscription_type', $data);
    if($r) {
        $email = getLoginEmail($data['login']);
        $body = p("Hi " . getLoginHTML($data['login']));
        $body .= p(
            "You are no longer admin of " . $data['jc_id'] . ". If this
            is a mistake please write to Adademic Office."
        );
        $r1 = sendHTMLEmail("You are no longer admin of " . $data['jc_id'], $body, $email);
        if(! $r1) {
            $ret['msg'] .= p("Failed to send email");
        }
    }
    $ret['success'] = $r;
    return $ret;
}

?>
