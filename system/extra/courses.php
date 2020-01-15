<?php

function getCourseOfThisRegistration(array $reg)
{
    $course = getTableEntry('courses', 'course_id,year,semester', $reg);
    return $course;
}

/* --------------------------------------------------------------------------*/
/**
* @Synopsis  Check if there is no collision with currently subscribed
* courses.
*
* @Param $thisCourses
* @Param $myCourses
*
* @Returns  An array with 'success' and 'collision_with' field.
 */
/* ----------------------------------------------------------------------------*/
function collisionWithMyRegistrations($thisCourse, $myRegistrations) : array
{
    $ret = ['collision'=>false, 'with'=>null];
    $cname = getCourseName($thisCourse['course_id']);
    foreach($myRegistrations as $mReg) {
        if(! $mReg)
            continue;
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
                || strtotime($c['end_date']) < strtotime($thisCourse['start_date']))
            {
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


?>
