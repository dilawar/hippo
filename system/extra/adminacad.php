<?php
require_once  __DIR__.'/methods.php';

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

    $ret = ['success'=>true, 'msg' => ''];
    $st = updateTable( 'course_registration'
        , 'student_id,semester,year,course_id'
        , 'grade,grade_is_given_on'
        , $data
    );

    $ret['success'] = $ret;
    if($st)
    {
        $ret['msg'] .= "Successfully assigned $grade for $login. <br /> ";
        // Send email.
        $subject = "Grade has been assigned to one of your course";
        $body = p("Dear " . loginToText($data['student_id']));
        $body .= p("Admin Acad ($by) has assigned grade to one of your course.");

        $infoData = [ 'course' => getCourseName($data['course_id'])
            , 'year/semester' => $data['year'].'/'.$data['semester']
            , 'grade' => $data['grade']
            , 'assigned_on' => $data['grade_assigned_on']
            , 'assigned_by' => $by
        ]; 
        $body .= arrayToVerticalTableHTML($infoData, 'info');
        $body .= p("If there is any mistake, please contact academic office.
            <strong>Also note that grades on HIPPO is not final official record.
            Final Grade is kept by Academic office. Hippo is a platform to inform 
            you about your grade. 
            </strong>");
        sendHTMLEmail($body, $subject, getLoginEmail($login));
    }
    else
        $ret['msg'] .= "Could not assign grade for $login. <br /> ";


    return $ret;
}
