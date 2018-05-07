<?php
include_once 'header.php';
include_once 'database.php';
include_once 'mail.php';
include_once 'tohtml.php';

/* ALL EVENTS GENERATED FROM THIS INTERFACE ARE SUITABLE FOR GOOGLE CALENDAR. */

// Here I get both speaker and talk details. I need a function which can either 
// insert of update the speaker table. Other to create a entry in talks table.
// Sanity check 
if( ! ( $_POST['first_name']  && $_POST[ 'institute' ] && $_POST[ 'title' ] 
    && $_POST[ 'description' ] ) )
{
    echo alertUser( 
        'Incomplete entry. Required fields: First name, last name, 
        institute, title and description of talk. '
        );
    echo arrayToVerticalTableHTML( $_POST, 'info' );
    echo goBackToPageLink( 'user_register_talk.php', 'Go back' );
    exit;
}

// Insert the speaker into table. if it already exists, just update.
$speaker = addOrUpdateSpeaker( $_POST );

$filepath = getSpeakerPicturePathById( $speaker[ 'id' ] );

if( $_FILES[ 'picture' ] )
{
    echo "Uploading image to $filepath ... ";
    uploadImage( $_FILES['picture'], $filepath );
}


if( $speaker )  // Sepeaker is successfully updated. Move on.
{
    // This entry may be used on public calendar. Putting email anywhere on 
    // public domain is allowed.
    $speakerText = loginToText( $speaker, $withEmail = false, $autofix = false );
    $_POST[ 'speaker' ] = $speakerText;
    $_POST[ 'speaker_id' ] = $speaker[ 'id' ];

    $res2 = addNewTalk( $_POST );

    if( $res2 )
    {
        $talkId = $res2[ 'id'];
        echo printInfo( "Successfully registered your talk with id $talkId" );
        $startTime = $_POST[ 'start_time' ];
        $endTime = $_POST[ 'end_time' ];

        if( isBookingRequestValid( $_POST ) )
        {
            $date = $_POST[ 'end_time' ];
            $venue = $_POST[ 'venue' ];

            if( $venue && $startTime && $endTime && $date )
            {
                /* Check if there is a conflict between required slot and already 
                 * booked events or requests. If no then book else redirect user to 
                 * a page where he can make better decisions.
                 */

                $reqs = getRequestsOnThisVenueBetweenTime( $venue, $date
                    , $startTime, $endTime );
                $events = getEventsOnThisVenueBetweenTime( $venue, $date
                    , $startTime, $endTime );
                if( $reqs || $events )
                {
                    echo printInfo( "There is already an events on $venue on $date
                        between $startTime and $endTime. 
                        <br />
                        I am redirecting you to page where you can browse all venues 
                       and create suitable booking request."
                    );
                    echo alertUser( "You can book a slot for this talk later" 
                        . " by visiting 'Manage my talks' link in your homepage. "
                        );
                }
                else 
                {
                    // Else create a request with external_id as talkId.
                    $external_id = getTalkExternalId( $res2 );
                    $_POST[ 'external_id' ] = $external_id;
                    $_POST[ 'is_public_event' ] = 'YES';

                    // Modify talk title for calendar.
                    $_POST[ 'title' ] = __ucwords__( $_POST['class'] ) . " by " . 
                        $_POST[ 'speaker' ] . ' on \'' . $_POST[ 'title' ] . "'";

                    $res = submitRequest( $_POST );
                    if( $res )
                        echo printInfo( "Successfully created booking request" );
                    else
                        echo printWarning( "Oh Snap! Failed to create booking request" );
                }
            }
            else
                echo printInfo( "The booking request is invalid." );
        }
    }
    else
        echo printWarning( "Oh Snap! Failed to add your talk to database." );
}
else
    echo printWarning( "Oh Snap! Failed to add speaker to database" );

echo goBackToPageLink( "user.php", "Go back" );

?>
