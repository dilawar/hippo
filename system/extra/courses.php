<?php

function getCourseName( string $cexpr ) : string
{
    $cid = explode('-', $cexpr)[0];
    $cid = urldecode($cid);

    $c =  getTableEntry('courses_metadata', 'id', array( 'id' => $cid ));
    if(! $c ) {
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
    foreach($slots as $slot) {
        foreach(getRunningCoursesOnTheseSlotTiles($date, $slot['id']) as $c) {
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

    if($sem === 'AUTUMN') {
        // This can spill over to next year but start date must have current
        // year.
        $nextYear = intval($year)+1;
        $sDate = dbDate(strtotime("$year-07-01"));
        $eDate = dbDate(strtotime("$nextYear-01-31"));
    }

    $hippoDB = initDB();;
    $res = $hippoDB->query(
        "SELECT * FROM courses WHERE
        start_date>='$sDate' AND end_date<='$eDate' AND YEAR(start_date)=$year
        "
    );

    $courses = fetchEntries($res);
    foreach($courses as &$course) {
        $course['name'] = getCourseName($course['course_id']);
        $course['instructors'] = getCourseInstructorsList($course['course_id']);
    }
    return $courses;
}

/**
 * @brief Get all the courses running this semester.
 *
 * @return
 */
function getRunningCourses( )
{
    $year = getCurrentYear();
    $sem = getCurrentSemester();
    return getSemesterCourses($year, $sem);
}

function deleteBookings( $course )
{
    $bookedby = $course;

    // Make them invalid.
    $res = updateTable(
        'events', 'created_by', 'status',
        array( 'created_by' => $course, 'status' => 'INVALID' )
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
function collisionWithMyRegistrations($thisCourse, $myRegistrations) : array
{
    $ret = ['collision'=>false, 'with'=>null];
    $cname = getCourseName($thisCourse['course_id']);
    foreach($myRegistrations as $mReg) {
        if(! $mReg) {
            continue;
        }
        $c = getCourseOfThisRegistration($mReg);

        // Same course.
        if($c['id'] === $thisCourse['id']) {
            $ret['collision'] = true;
            $ret['with'] = $c;
            return $ret;
        }

        //  Slots are the same. But dates may not overlap.
        if($c['slot'] === $thisCourse['slot']) {
            if(strtotime($c['start_date']) > strtotime($thisCourse['end_date'])
                || strtotime($c['end_date']) < strtotime($thisCourse['start_date'])
            ) {
                // clear. allow registration even if slot are colliding.
                continue;
            }
            else
            {
                $ret['collision'] = true;
                $ret['with'] = $c;
                return $ret;
            }
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
function getCourseRegistrations( string $cid, int $year, string $semester ) : array
{
    $registrations = getTableEntries(
        'course_registration',
        'student_id',
        "status='VALID' AND type != 'DROPPED' AND course_id='$cid' AND year='$year' AND semester='$semester'"
    );
    foreach($registrations as &$reg) {
        $reg['login'] = getLoginInfo($reg['student_id'], true);
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
    $res = ['status'=>false, 'msg' => ''];
    $res['status'] = updateTable(
        'course_feedback_responses',
        'login,course_id,semester,year', 'status',
        ['login'=>$login, 'course_id'=>$course_id, 'semester'=>$semester
            , 'year'=> $year, 'status'=>'WITHDRAWN']
    ); 
    return $res;
}

?>
