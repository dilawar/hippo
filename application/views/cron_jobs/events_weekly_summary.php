<?php

require_once 'cron_jobs/helper.php';

if( trueOnGivenDayAndTime( 'this sunday', '7:00 pm' ) )
{
    echo printInfo( "Today is Sunday 7pm. Send out emails for week events." );
    $thisMonday = dbDate( strtotime( 'this monday' ) );
    $subject = 'This week ( ' . humanReadableDate( $thisMonday) . ' ) events ';

    $cclist = '';
    $to = 'academic@lists.ncbs.res.in';

    $html = "<p>Greetings!</p>";

    $html .= printInfo( "List of events for the week starting "
        . humanReadableDate( $thisMonday )
    );

    $events = getEventsBeteen( $from = 'today', $duration = '+6 day' );

    if( count( $events ) > 0 )
    {
        foreach( $events as $event )
        {
            if( $event[ 'is_public_event' ] == 'NO' )
                continue;

            $externalId = $event[ 'external_id'];
            if( ! $externalId )
                continue;

            $id = explode( '.', $externalId);
            $id = $id[1];
            if( intval( $id ) < 0 )
                continue;

            $talk = getTableEntry( 'talks', 'id', array( 'id' => $id ) );

            // We just need the summary of every event here.
            $html .= eventSummaryHTML( $event, $talk );
            $html .= "<br>";
        }

        $html .= "<br><br>";

        // Generate email
        // getEmailTemplates
        $templ = emailFromTemplate( 'this_week_events'
            , array( "EMAIL_BODY" => $html )
        );

        sendHTMLEmail( $templ[ 'email_body'], $subject, $to, $cclist );
    }
    else
    {
        $html .= "<p> I could not find any event in my database! </p>";
        $html .=  "<p> -- Hippo </p>";
        sendHTMLEmail( $html, $subject, $to, $cclist );
    }
}

?>
