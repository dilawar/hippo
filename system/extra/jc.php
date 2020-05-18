<?php

require_once BASEPATH . 'autoload.php';

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Assign user to a JC presentation and send an email.
    *
    * @Param $data (array of data. Usually as $_POST )
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function fixJCSchedule(string $loginOrEmail, array $data):array
{
    $login = explode( '@', $loginOrEmail)[0];
    $data[ 'status' ] = 'VALID';
    $data[ 'presenter' ] = $loginOrEmail;
    $msg = json_encode( $data );

    if( getTableEntry( 'jc_presentations', 'presenter,jc_id,date' , $data ) )
    {
        $res = updateTable( 'jc_presentations', 'presenter,jc_id,date,time,venue', 'status', $data );
    }
    else
    {
        $data[ 'id' ] = getUniqueID( 'jc_presentations' );
        $data[ 'title' ] = 'Not yet available';
        $res = insertIntoTable( 'jc_presentations'
            , 'id,presenter,jc_id,date,time,venue,title,status', $data );
    }

    if( ! $res  )
    {
        $date = $data[ 'date'] ;
        $msg .= p( "Failed to assign <tt>$loginOrEmail</tt> on $date. " );
        return array( 'success' => false, 'message' => $msg );
    }

    $msg .= p( 'Assigned user ' . $loginOrEmail .  
            ' to present a paper on ' . dbDate( $data['date' ] ));

    $macros = array(
        'PRESENTER' => arrayToName( getLoginInfo( $login ) )
        , 'THIS_JC' => $data[ 'jc_id' ]
        , 'JC_ADMIN' => arrayToName( getLoginInfo( whoAmI( ) ) )
        , 'DATE' => humanReadableDate( $data[ 'date' ] )
    );

    // Now create a clickable link.
    $jcPresentation = getJCPresentation( $data['jc_id'],  $data[ 'presenter' ], $data[ 'date' ] );
    $id = $jcPresentation[ 'id' ];
    $clickableQ = "update jc_presentations SET acknowledged='YES' WHERE id='$id'";
    $q = insertClickableQuery( $login, "jc_presentation.$id", $clickableQ );

    if( $q['id'] )
        $msg .= printInfo( "Successfully inserted clickable query" );

    $clickableURL = queryToClickableURL( $q['id'], 'Click Here To Acknowledge' );
    $mail = emailFromTemplate( 'NOTIFY_PRESENTER_JC_ASSIGNMENT', $macros );

    $to = getLoginEmail( $loginOrEmail );

    if( $to )
    {
        $cclist = $mail['cc'];
        $subject = $data[ 'jc_id' ] . ' | Your presentation date has been fixed';

        // Add clickableQuery to outgoing mail.
        $body = $mail[ 'email_body' ];
        $body = addClickabelURLToMail( $body, $clickableURL );
        $res1 = sendHTMLEmail( $body, $subject, $to, $cclist );
        if( $res1 )
            $msg .= p("Succesfully sent email." );
        else
            $msg .= p("Could not sent email." );
    }

    return array( 'success' => true, 'message' => $msg);
}

function assignJCPresentationToLogin( string $loginOrEmail, array $data ) : array
{
    // Make sure the only valid login is used and not the email id.
    return fixJCSchedule( $loginOrEmail, $data );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Remove presentation.
    *
    * @Param array
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function removeJCPresentation(array $data): array
{
    $_POST[ 'status' ] = 'INVALID';
    $final = [ 'msg' => 'Nothing', 'success' => false ];
    $res = updateTable( 'jc_presentations', 'id', 'status', $_POST );
    if($res)
    {
        $data = getTableEntry('jc_presentations', 'id', $_POST);
        $to = getLoginEmail($data['presenter']);
        if(! $to)
        {
            $final['msg'] = "No valid email found for presenter " . $data['presenter'];
            return $final;
        }

        $cclist = 'jccoords@ncbs.res.in';
        $subject = $data[ 'jc_id' ] . ' | Your presentation date has been removed';
        $msg = p(' Your presentation scheduled on ' . humanReadableDate( $data['date'] )
            . ' has been removed by JC coordinator ' . whoAmI() );

        $msg .= p('If it is a mistake, please contant your JC coordinator.');
        $res = sendHTMLEmail($msg, $subject, $to, $cclist);
        if( $res )
        {
            $final['msg'] = "Successfully invalidated entry. Email sent.";
            $final['success'] = true;
            return $final;
        }
        else
        {
            $final['msg'] = "Successfully invalidated entry. Email could not be sent.";
            $final['success'] = true;
            return $final;
        }
    }
    return $final;
}

function unsubscribeJC(array $data): array
{
    $data[ 'status' ] = 'UNSUBSCRIBED';
    $res = updateTable( 'jc_subscriptions', 'login,jc_id', 'status', $data);
    return [ 'msg' => 'OK', 'success' => $res];
}

function subscribeJC(array $data): array
{
    $final = ['msg'=>'', 'success'=>false];
    $data[ 'status' ] = 'VALID';
    $emailOrLogin = $data['login'];
    $login = explode( '@', $emailOrLogin )[0];

    $info = getLoginInfo($login, true, true);
    if(! __get__($info, 'email', '') )
    {
        $final['msg']= "'$login' is not found as a valid person.  Can't subscribe. ";
        return $final;
    }

    $data[ 'status' ] = 'VALID';
    $data[ 'login' ] = $login;
    $res = insertOrUpdateTable( 'jc_subscriptions', 'jc_id,login', 'status', $data);
    return [ 'msg' => "Successfully subscribed '$login'. No email was sent." , 'success' => $res];
}

function jcPresentationEmail($presentationID) : array
{
    $presentation = getTableEntry('jc_presentations', 'id', ['id'=>$presentationID]);
    if(! $presentation) {
        return ['subject' => 'Error', 'email_body' => "No presentation found $presentationID"];
    }

    $jcID = $presentation['jc_id'];

    $listOfAdmins = array_values(getAllAdminsOfJC($jcID));
    $presenters = getJCPresenters($presentation);
    $tableOfJCCoords = arraysToTable($listOfAdmins);

    $jcInfo = getJCInfo($jcID);
    $title = getPresentationTitle($presentation);

    $macro = [
        'BODY' => jcToHTML($presentation), 'TABLE_OF_JC_COORDINATORS' => $tableOfJCCoords,
    ];

    $mail = emailFromTemplate('NOTIFY_ACADEMIC_UPCOMING_JC', $macro);
    $subject = "$jcID (Today) | '$title' by $presenters";
    $mail['subject'] = $subject;
    return $mail;
}

?>
