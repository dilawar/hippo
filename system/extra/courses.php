<?php

$OPTIONS = ['Strongly Agree'=>2, 'Agree'=>1, 'Neutral'=>0, 'Disagree'=>-1.5, 'Strongly Disagree'=>-3];

function mean($arr) 
{
    if(! $arr)
        return 0.0;
    return array_sum($arr)/count($arr);
}

function getCourseName(string $cexpr): string
{
    $cid = explode('-', $cexpr)[0];
    $cid = urldecode($cid);

    $c = getTableEntry('courses_metadata', 'id', ['id' => $cid]);
    if (!$c) {
        flashMessage("No course information found for '$cexpr': CID='$cid'");

        return '';
    }

    return $c['name'];
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get running courses on this day.
 *
 * @Param $date  At a given day.
 *
 * @Returns List of courses.
 */
/* ----------------------------------------------------------------------------*/
function getRunningCoursesAtThisDay($date)
{
    $date = dbDate($date);
    $hippoDB = initDB();
    $thisDay = strtolower(date('D', strtotime($date)));

    $courses = [];
    $slots = getTableEntries('slots', 'day', "LOWER(day)='$thisDay'");
    foreach ($slots as $slot) {
        foreach (getRunningCoursesOnTheseSlotTiles($date, $slot['id']) as $c) {
            $name = getCourseName($c['id']);
            $c['name'] = $name;
            $courses[] = $c;
        }
    }

    return $courses;
}

function getSemesterCourses($year, $sem)
{
    $sDate = dbDate(strtotime("$year-01-01"));
    $eDate = dbDate(strtotime("$year-08-31"));
    $sem = strtoupper($sem);

    if ('AUTUMN' === $sem) {
        // This can spill over to next year but start date must have current
        // year.
        $nextYear = intval($year) + 1;
        $sDate = dbDate(strtotime("$year-07-01"));
        $eDate = dbDate(strtotime("$nextYear-01-31"));
    }

    $courses = executeQuery("SELECT * FROM courses WHERE
        start_date>='$sDate' AND end_date<='$eDate' AND YEAR(start_date)=$year");

    foreach ($courses as &$course) {
        $cid = $course['course_id'];
        $semester = $course['semester'];
        $year = $course['year'];

        $nf = executeQuery("SELECT COUNT(DISTINCT login) AS total FROM course_feedback_responses WHERE
            course_id='$cid' AND year='$year' AND semester='$semester' AND status='VALID'", true);

        if (count($nf) > 0) {
            $nf = $nf[0];
        }

        $course['name'] = getCourseName($course['course_id']);
        $course['instructors'] = getCourseInstructorsList($course['course_id']);
        $course['numfeedback'] = __get__($nf, 'total', -1);
    }

    return $courses;
}

/**
 * @brief Get all the courses running this semester.
 *
 * @return
 */
function getRunningCourses()
{
    $year = getCurrentYear();
    $sem = getCurrentSemester();

    return getSemesterCourses($year, $sem);
}

function deleteBookings($course)
{
    $bookedby = $course;

    // Make them invalid.
    $res = updateTable(
        'events', 'created_by', 'status',
        ['created_by' => $course, 'status' => 'INVALID']
    );

    return $res;
}

function getCourseOfThisRegistration(array $reg)
{
    $course = getTableEntry('courses', 'course_id,year,semester', $reg);

    return $course;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Check if there is no collision with currently subscribed
 * courses.
 *
 * @Param $thisCourses
 * @Param $myCourses
 *
 * @Returns An array with 'success' and 'collision_with' field.
 */
/* ----------------------------------------------------------------------------*/
function collisionWithMyRegistrations($thisCourse, $myRegistrations): array
{
    $ret = ['collision' => false, 'with' => null];
    $cname = getCourseName($thisCourse['course_id']);
    foreach ($myRegistrations as $mReg) {
        if (!$mReg) {
            continue;
        }
        $c = getCourseOfThisRegistration($mReg);

        // Same course.
        if ($c['id'] === $thisCourse['id']) {
            $ret['collision'] = true;
            $ret['with'] = $c;

            return $ret;
        }

        //  Slots are the same. But dates may not overlap.
        if ($c['slot'] === $thisCourse['slot']) {
            if (strtotime($c['start_date']) > strtotime($thisCourse['end_date'])
                || strtotime($c['end_date']) < strtotime($thisCourse['start_date'])
            ) {
                // clear. allow registration even if slot are colliding.
                continue;
            }

            $ret['collision'] = true;
            $ret['with'] = $c;

            return $ret;
        }
    }

    return $ret;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get all the registrations beloning to course.
 *
 * @Param $cid Course ID.
 *
 * @Returns Array containing registrations.
 */
/* ----------------------------------------------------------------------------*/
function getCourseRegistrations(string $cid, int $year, string $semester): array
{
    $registrations = getTableEntries(
        'course_registration',
        'student_id',
        "status!='INVALID' AND course_id='$cid' AND year='$year' AND semester='$semester'"
    );
    foreach ($registrations as &$reg) {
        $reg['login'] = getLoginInfo($reg['student_id'], true);
        $reg['name'] = arrayToName($reg['login']);
    }

    return $registrations;
}

function getCourseRegistrationsLight(string $cid, int $year, string $semester): array
{
    $registrations = getTableEntries(
        'course_registration',
        'status,type,student_id',
        "status!='INVALID' AND course_id='$cid' AND year='$year' AND semester='$semester'"
    );

    foreach ($registrations as &$reg) {
        $login = getLoginInfo($reg['student_id'], true);
        $reg['name'] = arrayToName($login);
        $reg['email'] = $login['email'];
    }

    return $registrations;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Return total numner of registration of a course.
 *
 * @Param $cid  course id.
 * @Param $year Year.
 * @Param int  Semester.
 *
 * @Returns Number of registration.
 */
/* ----------------------------------------------------------------------------*/
function getNumCourseRegistration(string $cid, string $year, string $semester): int
{
    $num = executeQuery(
        "SELECT COUNT(1) AS total FROM course_registration
        WHERE status='VALID' AND type != 'DROPPED' AND course_id='$cid' 
        AND year='$year' AND semester='$semester'",
        true
    );

    return intval($num[0]['total']);
}

function withdrawCourseFeedback($login, $course_id, $semester, $year): array
{
    $res = ['status' => false, 'msg' => ''];
    $res['status'] = updateTable(
        'course_feedback_responses',
        'login,course_id,semester,year', 'status',
        ['login' => $login, 'course_id' => $course_id, 'semester' => $semester, 'year' => $year, 'status' => 'WITHDRAWN']
    );

    return $res;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis API function. No formatting of data.
 *
 * @Param $year
 * @Param $semester
 * @Param $cid
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getCourseFeedbackApi(string $year='', string $semester='', string $cid='', $login = ''): array
{
    global $OPTIONS;
    $where = "status='VALID'";
    if($year)
        $where .= " AND year='$year'";
    if($semester)
       $where .= " AND semester='$semester'";
    if(strlen($cid) > 0)
        $where .= " AND course_id='$cid'";
    if ($login)
        $where .= " AND login='$login' ";

    $ques = getTableEntries('course_feedback_questions', 'id', "status='VALID'"
        , 'id,type,question,choices');

    $data = [];
    $questions = [];
    $responses = [];

    $points = [];
    $summary = [];

    foreach ($ques as &$q) {
        $qid = $q['id'];
        if(trim($q['choices']))
            $q['choices'] = explode(',', trim($q['choices']));
        else
            $q['choices'] = [];

        $questions[$q['id']] = $q;

        $w = $where . " AND question_id='$qid'";
        $response = [];

        // choices
        foreach($q['choices'] as $ch) 
            $response['choice'][$ch] = 0;

        // Get responses for this questions.
        $fields = 'question_id,course_id,year,semester,instructor_email,response';
        $entries = getTableEntries('course_feedback_responses', 'question_id', $w, $fields);
        foreach($entries as &$en) {
            $en['question'] = strip_tags($questions[$qid]['question']);
            $data[] = $en;
            if(in_array($en['response'], $q['choices'])) {
                $r = $en['response'];
                $response['choice'][$r] += 1;
                $points[$en['course_id']][] = __get__($OPTIONS, $r, 0);
                $summary[$r] = __get__($summary, $r, 0) + 1;
            }
            else
                $response['text'][] = $en['response'];
        }
        $responses[$qid] = $response;
    }

    $score = [];
    foreach($points as $c => $pts)
        $score[$c] = 5/2*mean($pts) + 5;

    return ['responses' => $responses
        , 'questions' => $questions
        , 'summary' => $summary
        , 'points' => $points
        , 'data' => $data
        , 'score' => $score
    ];
}

function getCourseFeedback2Api(string $year='', string $semester='', string $cid='', $login = ''): array
{
    global $OPTIONS;
    $where = "status='VALID'";
    if($year)
        $where .= " AND year='$year'";
    if($semester)
       $where .= " AND semester='$semester'";
    if(strlen($cid) > 0)
        $where .= " AND course_id='$cid'";
    if ($login)
        $where .= " AND login='$login' ";

    $ques = getTableEntries('course_feedback_questions', 'id', "status='VALID'"
        , 'id,type,question,choices');

    $data = [];
    $questions = [];
    $responses = [];

    $points = [];
    $summary = [];

    foreach ($ques as &$q) {
        $qid = $q['id'];
        if(trim($q['choices']))
            $q['choices'] = explode(',', trim($q['choices']));
        else
            $q['choices'] = [];

        $questions[$q['id']] = $q;

        $w = $where . " AND question_id='$qid'";
        $response = [];

        // Get responses for this questions.
        $fields = 'question_id,course_id,year,semester,instructor_email,response';
        $entries = getTableEntries('course_feedback_responses', 'question_id', $w, $fields);
        foreach($entries as &$en) {
            $en['question'] = strip_tags($questions[$qid]['question']);
            $data[] = $en;

            $who = __get__($en, 'instructor_email', $en['course_id']);
            if(null === __get__($response, $who, null))
                $response[$who] = [];

            if(in_array($en['response'], $q['choices'])) {
                $r = $en['response'];

                if(null === __get__($response[$who], $r, null))
                    $response[$who]['choice'][$r] = 0;

                $response[$who]['choice'][$r] += 1;

                $points[$en['course_id']][] = __get__($OPTIONS, $r, 0);
                $summary[$r] = __get__($summary, $r, 0) + 1;
            }
            else
                $response[$who]['text'][] = $en['response'];
        }
        $responses[$qid] = $response;
    }

    $score = [];
    foreach($points as $c => $pts)
        $score[$c] = 5/2*mean($pts) + 5;

    return ['responses' => $responses
        , 'questions' => $questions
        , 'summary' => $summary
        , 'points' => $points
        , 'data' => $data
        , 'score' => $score
    ];
}
