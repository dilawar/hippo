<?php

require_once __DIR__ . '/methods.php';

/*
 * Assign AWS
 */
function assignAWS(string $speaker, string $date, string $venue=""): array
{
    $res = [ 'success'=>false, 'msg'=>''];
    if(! $venue)
        $venue = getDefaultAWSVenue($date);

    if(  $speaker && getLoginInfo( $speaker ) && strtotime( $date ) > strtotime( '-7 day' ) )
    {
        $aws = getUpcomingAWSOfSpeaker( $speaker );
        if( $aws )
        {
            $res['msg'] = "$speaker already has AWS scheduled. Doing nothing.";
            return $res;
        }

        $awsID = acceptScheduleOfAWS( $speaker, $date, $venue );
        if( $awsID > 0 )
        {
            $res['success'] = true;
            $res['msg'] = "Successfully assigned. ";

            // Don't rescheduleAWS. It will change the rest of the 
            // entries for the week.
            // rescheduleAWS( );

            // Send email to user.
            $st = notifyUserAboutUpcomingAWS( $speaker, $date, $awsID );
            if(! $st)
                $res['msg'] .= "Failed to send email to user. ";

            return $res;
        }
        else
        {
            $res['success'] = false;
            $res['msg'] .= "Invalid entry. Probably date ('$date') is in past. ";
            return $res;
        }
    }
    $res['msg'] = "Invalid speaker '$speaker' or date '$date' is in past."
        . " Could not assign AWS.";
    return $res;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Cancel a given AWS.
    *
    * @Param $data array with required data.
    * @Param $bywhom Who has removed it.
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function cancelAWS(array $data, string $bywhom='HIPPO') : array
{
    $speaker = $data['speaker'];
    $date = $data['date'];
    $reason = __get__($data, 'reason', 'None given. So rude!');
    $res = clearUpcomingAWS( $speaker, $date );
    $piOrHost = getPIOrHost( $speaker );
    $final = ['msg'=>'', 'status'=>false];

    if( $res )
    {
        $final['msg'] = "Successfully cleared upcoming AWS of $speaker on $date.";

        $admin = whoAmI();
        // Notify the hippo list.
        $msg = "<p>Hello " . loginToHTML( $data[ 'speaker' ] ) . "</p>";
        $msg .= "<p>
            Your upcoming AWS schedule has been removed by Hippo admin ($bywhom).
            If this is a  mistake, please write to acadoffice@ncbs.res.in
            immediately.
            </p>
            <p> The AWS schedule which is removed is the following </p>
";

        $msg .= p( "Following reason was given by admin." );
        $msg .= p( $reason );

        $msg .= arrayToVerticalTableHTML( $data, 'info' );
        $cclist = "acadoffice@ncbs.res.in,hippo@lists.ncbs.res.in";
        if($piOrHost)
            $cclist .= ",$piOrHost";

        sendHTMLEmail( $msg
            , "Your ($speaker) AWS schedule has been removed from upcoming AWSs"
            , $to = getLoginEmail( $data[ 'speaker' ] )
            , $cclist 
        );
        $final['status'] = true;
        return $final;
    }
    return $final;
}

function updateAWS(array $data, string $by='HIPPO'): array
{
    $res = updateTable( 'upcoming_aws', 'id'
        , 'abstract,title,is_presynopsis_seminar,supervisor_1', $_POST );

    if( $res )
        return ['success'=>true
        , 'msg'=>"Successfully updated abstract of upcoming AWS entry"];

    return ['msg'=>"I could not update title/abstract."
        , 'success'=>false];

}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Update this registration.
    *
    * @Param array
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function updateRegistration(arrray $data): array
{
    $res = updateTable('course_registration', 'student_id,year,semester,course_id'
        , 'type,status,grade'
        , $data);

}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Handle registration requests.
    *
    * @Param $course
    *   Array representing course (from table running courses)
    * @Param $reg
    *   Array representing registration (from table course_registrations)
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
function handleCourseRegistration(array $course, array $reg
    , string $what
    , string $student, string $bywhom, $sendEmail=true) : array
{
    $what = strtoupper($what);

    // if student is not a valid email id, then check that student is registered
    // on Hippo.
    $login = [];
    if(! __substr__('@', $student))
    {
        $login = getLoginInfo($student, true, true);
        if(! $login)
            return ["success"=>false, "msg"=> "Unknown user $student"];
    }
    else
        $to = $student;


    $ret = ['msg'=>'', 'success'=>true];
    $data = $reg;
    $data['last_modified_on'] = dbDateTime('now');

    // If new registration, get the current timestamp else use old one.
    $data['registered_on'] = __get__($reg, "registered_on", dbDateTime('now'));

    if($what === 'DROP')
        $data['status'] = 'DROPPED';
    else if($what === 'CREDIT')
    {
        $data["type"] = 'CREDIT';
        $data["status"] = 'VALID';
    }
    else if($what === 'AUDIT')
    {
        $data["type"] = 'AUDIT';
        $data["status"] = 'VALID';
    }
    else
        return ["success"=>false, "msg"=> "Unknown course registration task $what"];


    // Update the registration table.

    $data['year'] = $course['year'];
    $data['semester'] = $course['semester'];
    $data['course_id'] = $course['course_id'];
    assert($data['course_id']) or die("Invalid course id ".$course['course_id']);
    $cid = $course['course_id'];

    // If user has asked for AUDIT but course does not allow auditing,
    // do not register and raise and error.
    if( $course['is_audit_allowed'] === 'NO' && $data['type'] === 'AUDIT' )
    {
        $ret['msg'] = "Sorry but course $cid does not allow <tt>AUDIT</tt>.";
        return $ret;
    }

    // If number of students are over the number of allowed students
    // then add student to waiting list and raise a flag.
    if( $course['max_registration'] > 0)
    {
        $numEnrollments = count(getCourseRegistrations( $cid, $course['year'], $course['semester'] ));
        if( intval($numEnrollments) >= intval($course['max_registration']) )
        {
            $data['status'] = 'WAITLIST';
            $ret['msg'] .= p( "<i class=\"fa fa-flag fa-2x\"></i>
                Number of registrations have reached the limit. I've added you to 
                <tt>WAITLIST</tt>. Please contact academic office or your instructor about 
                the policy on <tt>WAITLIST</tt>. By default, <tt>WAITLIST</tt> means 
                <tt>NO REGISTRATION</tt>.");
        }
    }

    // If already registered then update the type else register new.
    $data['student_id'] = $student;
    $r = insertOrUpdateTable( 'course_registration'
        , 'student_id,semester,year,type,course_id,registered_on,last_modified_on'
        , 'type,last_modified_on,status'
        , $data 
    );

    if(! $r)
    {
        $ret['msg'] .= p( "Failed to $what the course " . $data['course_id'] );
        $ret['success'] = false;
        return $ret;
    }

    // Update waiting lists.
    if(strtoupper($data['status']) === 'DROPPED')
        updateCourseWaitlist($data['course_id'], $data['year'], $data['semester']);

    $ret['msg'] .= "Successfully $what course ".$data['course_id'];

    if($sendEmail)
    {
        // Send email to user.
        if($login)
        {
            $msg = p( "Dear " . arrayToName($login, true));
            $to = $login['email'];
        }
        else
            $msg = p( "Dear $to");

        if($login !== $bywhom)
            $msg .= p("Acad admin ($bywhom) has successfully updated your courses.");
        else
            $msg .= p("You have successfully updated your courses.");

        $sem = getCurrentSemester( );
        $year = getCurrentYear( );

        // User courses and slots.
        $myCourses = getMyCourses($sem, $year, $user=$data['student_id']);
        $msg .= p("Your courses this semester are following.");
        if(count($myCourses)>0)
        {
            foreach( $myCourses as $c )
                $msg .= arrayToVerticalTableHTML($c, 'info','','grade,grade_is_given_on');
        }
        else
            $msg .= p("No course is found.");

        sendHTMLEmail($msg, "Successfully ".$what." ed the course $cid", $to);
    }
    return $ret;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Admin acad.
    *
    * @Param $data
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function assignGrade(array $data, string $by = 'HIPPO') : array
{
    $login = $data['student_id'];
    $grade = $data['grade'];

    $data['grade_is_given_on'] = dbDateTime('now');

    $ret = ['success'=>true, 'msg' => ''];
    $st = updateTable( 'course_registration'
        , 'student_id,semester,year,course_id'
        , 'grade,grade_is_given_on'
        , $data
    );

    $ret['success'] = $st;

    if($st)
    {
        $ret['msg'] .= "Successfully assigned $grade for $login.";

        // Send email.
        $subject = "Grade has been assigned to one of your course";
        $body = p("Dear " . loginToText($data['student_id']));
        $body .= p("Academic admin ($by) has just assigned grade to one of your courses.");

        $infoData = [ 'course' => getCourseName($data['course_id'])
            , 'year/semester' => $data['year'].'/'.$data['semester']
            , 'grade' => $data['grade']
            , 'assigned_on' => $data['grade_is_given_on']
            , 'assigned_by' => $by
        ]; 
        $body .= arrayToVerticalTableHTML($infoData, 'info');

        $body .= p("Note that a grade 'X' means that previous grade has 
            been removed. Academic admin may have made an error.");

        $body .= p("If there is any mistake, please contact academic office.
            <strong>Note that grades on HIPPO are not final official record.
            Your final grade is kept by Academic office. Hippo is a platform 
            to inform you about your grade. In case of discrepency, the record 
            at Academic Office shall be deemed correct.
            </strong>");
        sendHTMLEmail($body, $subject, getLoginEmail($login));
    }
    else
        $ret['msg'] .= "Could not assign grade for $login. <br /> ";


    return $ret;
}

function getExtraAWSInfo(string $login, array $speaker=[]) : array
{
    $upcomingAWS = getUpcomingAWS($login);
    $pastAWSes = getAwsOfSpeaker($login);

    // Get PI/HOST and speaker specialization.
    $pi = getPIOrHost($login);
    $specialization = getSpecialization($login, $pi);

    // This user may have not given any AWS in the past. We consider their
    // joining date as last AWS date.
    if(count($pastAWSes) > 0)
    {
        $lastAws = $pastAWSes[0];
        $lastAwsDate = $lastAws['date'];
    }
    else
    {
        if(! $speaker)
            $speaker = getLoginInfo($login);
        $lastAwsDate = $speaker['joined_on'];
    }
    return ["specialization"=>$specialization
        , "pi_or_host"=>$pi
        , 'last_aws_date'=>$lastAwsDate
        , 'days_since_last_aws'=>(strtotime('today')-strtotime($lastAwsDate))/3600/24
        , 'num_aws' => count($pastAWSes)];
}


?>