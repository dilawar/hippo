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
    $qid = insertClickableQuery( $login, "jc_presentation.$id", $clickableQ );

    if( $qid )
        $msg .= printInfo( "Successfully inserted clickable query" );

    $clickableURL = queryToClickableURL( $qid, 'Click Here To Acknowledge' );
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

?>
