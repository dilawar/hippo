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
        sendHTMLEmail( $msg, $subject, $to, $cclist );
    }
    else
        $res['error'] = p("Could not find email text.");

    return $res;
}

function admin_update_speaker( array $data ) : array
{
    $final = [ 'message' => '', 'error' => '' ];

    if( $data['response'] == 'DO_NOTHING' )
    {
        $final['error'] = "User said do nothing.";
        return $final;
    }

    if( $data['response'] == 'delete' )
    {
        // We may or may not get email here. Email will be null if autocomplete was
        // used in previous page. In most cases, user is likely to use autocomplete
        // feature.
        if( strlen($data[ 'id' ]) > 0 )
            $res = deleteFromTable( 'speakers', 'id', $data );
        else
            $res = deleteFromTable( 'speakers', 'first_name,last_name,institute', $data );

        if( $res )
             $final['message'] = "Successfully deleted entry";
        else
            $final['error'] = minionEmbarrassed( "Failed to delete speaker from database" );

        return $final;
    }

    if( $data['response'] == 'submit' )
    {
        // If there is not speaker id, then  create a new speaker.
        $sid = __get__( $data, 'id', -1 );
        $res = null;
        $warning = '';

        if( $sid < 0 )  // Insert a new enetry.
        {
            // Insert a new entry.
            $speakerId = getUniqueFieldValue( 'speakers', 'id' );
            $data[ 'id' ] = intval( $speakerId ) + 1;
            $sid = $data[ 'id' ];
            $res = insertIntoTable( 'speakers'
                        , 'id,honorific,email,first_name,middle_name,last_name,' .
                            'designation,department,homepage,institute'
                        , $data
                        );
        }
        else // Update the speaker.
        {
            if( __get__( $data, 'id', 0 ) > 0 )
                $whereKey = 'id';
            else
                $whereKey = 'first_name,middle_name,last_name';

            $speaker = getTableEntry( 'speakers', $whereKey, $data );
            if( $speaker )
            {
                // Update the entry
                $res = updateTable( 'speakers', $whereKey
                    , 'honorific,email,first_name,middle_name,last_name,' .
                    'designation,department,homepage,institute'
                    , $data
                );

                // Update all talks related to  this speaker..
                try 
                {
                    $sname =  speakerName( $sid );
                    $res = updateTable( 'talks', 'speaker_id', 'speaker'
                        , array( 'speaker_id' => $sid, 'speaker' => $sname )
                    );
                        
                } catch (Exception $e) 
                {
                    $warning .= printWarning( "Failed to update some talks by this speaker " .
                        $e->getMessage() );
                }

                if( $res )
                    $final['message'] .= printInfo( " .. updated related talks as well " );
            }
        }

        // After inserting new speaker, upload his/her image.
        if( array_key_exists( 'picture', $_FILES ) && $_FILES[ 'picture' ]['name'] )
        {
            $imgpath = getSpeakerPicturePath( $sid );
            $final['message'] .= printInfo( "Uploading speaker image to $imgpath .. " );
            $res = uploadImage( $_FILES[ 'picture' ], $imgpath );
            if( ! $res )
                $final['error'] .= minionEmbarrassed( "Could not upload speaker image to $imgpath" );
        }

        if( $res )
            $final['message'] .= 'Updated/Inserted speaker. <br />' . $warning;
        else
            $final['error'] .= printInfo( "Failed to update/insert speaker" );

        return $final;
    }

    $final['error'] .= alertUser( "Unknown/unsupported operation " . $data[ 'response' ] );
    return $final;
}

?>
