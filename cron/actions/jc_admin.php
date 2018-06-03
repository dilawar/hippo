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
function fixJCSchedule( $loginOrEmail, $data )
{
    $login = explode( '@', $loginOrEmail)[0];
    $newId = getUniqueID( 'jc_presentations' );
    $data[ 'title' ] = '';
    $data[ 'status' ] = 'VALID';
    $data[ 'id' ] = getUniqueID( 'jc_presentations' );
    $data[ 'presenter' ] = $loginOrEmail;
    $data[ 'title' ] = 'Not yet available';

    $entry = insertOrUpdateTable( 'jc_presentations'
        , 'id,presenter,jc_id,date,title', 'status'
        , $data );

    if( ! $entry  )
    {
        $date = $data[ 'date'] ;
        echo printInfo( "Failed to assign $presenter on $date. " );
        return array( );
    }

    echo printInfo( 'Assigned user ' . $loginOrEmail .
        ' to present a paper on ' . dbDate( $data['date' ] )
        );

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
    {
        echo printInfo( "Successfully inserted clickable query" );
    }

    $clickableURL = queryToClickableURL( $qid, 'Click Here To Acknowledge' );
    $mail = emailFromTemplate( 'NOTIFY_PRESENTER_JC_ASSIGNMENT', $macros );

    $to = getLoginEmail( $loginOrEmail );

    $res = true;
    if( $to )
    {
        $cclist = $mail['cc'];
        $subject = $data[ 'jc_id' ] . ' | Your presentation date has been fixed';

        // Add clickableQuery to outgoing mail.
        $body = $mail[ 'email_body' ];
        $body = addClickabelURLToMail( $body, $clickableURL );
        $res = sendHTMLEmail( $body, $subject, $to, $cclist );
    }
    return $res;
}

function assignJCPresentationToLogin( $loginOrEmail, $data )
{
    // Make sure the only valid login is used and not the email id.
    return fixJCSchedule( $loginOrEmail, $data );
}

?>
