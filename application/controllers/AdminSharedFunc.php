<?php
require_once BASEPATH.'autoload.php';

function admin_update_talk( $data )
{
    $res = updateTable( 'talks', 'id'
                , 'class,host,coordinator,title,description'
                , $data 
            );

    if( $res )
    {
        // TODO: Update the request or event associated with this entry as well.
        $externalId = getTalkExternalId( $data );

        $talk = getTableEntry( 'talks', 'id', $data );
        assert( $talk );

        $success = true;

        $event = getEventsOfTalkId( $data[ 'id' ] ); 
        $request = getBookingRequestOfTalkId( $data[ 'id' ] );

        if( $event )
        {
            echo printInfo( "Updating event related to this talk" );
            $event[ 'title' ] = talkToEventTitle( $talk );
            $event[ 'description' ] = $talk[ 'description' ];
            $res = updateTable( 'events', 'gid,eid', 'title,description', $event );
            if( $res )
                echo printInfo( "... Updated successfully" );
            else
                $success = false;
        }
        else if( $request )
        {
            echo printInfo( "Updating booking request related to this talk" );
            $request[ 'title' ] = talkToEventTitle( $talk );
            $request[ 'description' ] = $talk[ 'description' ];
            $res = updateTable( 'bookmyvenue_requests', 'gid,rid', 'title,description', $request );
        }
    }

    if(! $res)
    {
        printErrorSevere( "Failed to update talk" );
        return true;
    }
    else
    {
        flashMessage( 'Successfully updated entry' );
        return true;
    }
}

function admin_send_email( array $data ) : array
{
    $res = [ 'error' => '', 'message' => ''];

    $to = $data[ 'recipients' ];
    $msg = $data[ 'email_body' ];
    $cclist = $data[ 'cc' ];
    $subject = $data[ 'subject' ];

    $res['message'] =  "<h2>Email content are following</h2>";
    $mdfile = html2Markdown( $msg, true );
    $md = file_get_contents( trim($mdfile) );

    if( $md )
    {
        $res['message'] .= printInfo( "Sending email to $to ($cclist ) with subject $subject" );

        $ret = sendHTMLEmail( $msg, $subject, $to, $cclist );
        if(!$ret)
            $res['error'] = p("Failed to send email.");
    }
    else
        $res['error'] = p("Could not find email text.");

    return $res;
}

?>
