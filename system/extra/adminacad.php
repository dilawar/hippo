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
    $data['grade_is_given_on'] = dbDateTime('now');

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
        $body .= p("Academic admin ($by) has just assigned grade to one of your courses.");

        $infoData = [ 'course' => getCourseName($data['course_id'])
            , 'year/semester' => $data['year'].'/'.$data['semester']
            , 'grade' => $data['grade']
            , 'assigned_on' => $data['grade_assigned_on']
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
