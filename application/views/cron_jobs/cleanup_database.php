<?php

require_once 'cron_jobs/helper.php';

/* Every monday, check students who are not eligible for AWS anymore */
if( trueOnGivenDayAndTime( 'this monday', '17:00' ) )
{
    echo printInfo( 'Monday, removing students who have given PRE_SYNOPSIS SEMINAR and thesis SEMINAR' );

    // In last two weeks.
    $cutoff = dbDate( strtotime( 'today' ) - 14 * 24 * 3600 );

    $presynAWS = getTableEntries( 'annual_work_seminars', 'date'
        , "IS_PRESYNOPSIS_SEMINAR='YES' AND date > '$cutoff'" );

    foreach( $presynAWS as $aws )
    {
        $speaker = $aws[ 'speaker' ];
        if( isEligibleForAWS( $speaker ) )
            removeAWSSpeakerFromList( $speaker );
    }

    /* Now removing students with THESIS SEMINAR */
    echo printInfo( "Removing students who have given thesis seminar" );
    $thesisSeminars = getTableEntries( 'talks', 'id' , "class='THESIS SEMINAR'" );
    foreach( $thesisSeminars as $talk )
    {
        $speaker = getSpeakerByID( $talk['speaker_id'] ) or getSpeakerByName( $talk[ 'speaker' ] );
        $login = findAnyoneWithEmail( __get__( $speaker, 'email', '' ) );
        if( $login )
        {
            $login = $login[ 'login'];
            if( isEligibleForAWS( $login ) )
                removeAWSSpeakerFromList( $login );
        }
    }

    // Also cleanup the AWS preferences.
    $today = dbDate( 'today' );
    $prefs = getTableEntries( 'aws_scheduling_request'
        , 'id'
        , "first_preference < '$today' AND 'second_preference' < '$today' AND status='APPROVED'"
    );

    foreach( $prefs as $p )
    {
        echo printInfo( "Removing preferences " . $p['id'] );

        // Since we don't have expired field.
        $p[ 'status' ] = 'CANCELLED';
        updateTable( 'aws_scheduling_request', 'id', 'status', $p );
    }
}

/* Every monday, check students who are not eligible for AWS anymore */
if( trueOnGivenDayAndTime( 'this wednesday', '15:45' ) )
{
    echo printInfo( "Cleanup login" );
    $badLogins = getTableEntries( 'logins', 'login'
        , "(first_name IS NULL OR first_name='') OR first_name=last_name AND status='ACTIVE'" 
    );

    // foreach( $badLogins as $l )
    // {
        // var_dump( $l );
        // echo " <br />";
    // }

    echo printInfo( "Total " . count( $badLogins) . " are found" );
    foreach( $badLogins as $l )
    {
        $login = __get__($l, 'login', '' );
        if( ! $login )
            continue;

        echo printWarning( "Login $login is bad" );
        $ldap = getUserInfoFromLdap( $login );
        if( $ldap )
        {
            $ldap[ 'login' ] = $login;
            $res = updateTable( 'logins', 'login', 'first_name,last_name,email', $ldap );
            if( $res )
            {
                var_dump( $ldap );
                echo printInfo( " ... $login is fixed" );
            }
        }
    }
}

if( trueOnGivenDayAndTime( 'this sunday', '17:00' ) )
{
    echo printInfo( "Removing all MSc students with at-least 1 aws" );
    $logins = getTableEntries( 'logins', 'login', "title='MSC' AND eligible_for_aws='YES'" );
    foreach( $logins as $i => $msc )
    {
        $speaker = $msc['login'];
        $aws = getAWSSpeakers( $msc[ 'login' ] );
        if( count( $aws ) > 0 )
        {
            echo printInfo( "MSc student $speaker has given AWS. Remove him/her" );
            removeAWSSpeakerFromList( $speaker );
        }
    }
}

if( trueOnGivenDayAndTime( 'today', '10:15' ) )
{
    echo "Cleaning up orphaned events";
    $today = dbDate( 'today' );
    $events = getTableEntries( 'events', 'date', "date>'$date' AND status='VALID' AND external_id !='SELF.-1'" );
    foreach( $events as $ev )
    {
        $talkID = explode( '.', $ev[ 'external_id' ])[1];
        $talk = getTableEntries( 'talks', 'id', "id='$talkID' AND status='VALID'" );
        if( ! $talk )
        {
            echo printWarning( "Associated talk with this event has been cancelled. 
                Cancelling this event as well." );
            echo printInfo( $ev['title'] );

            $ev['status'] = 'INVALID';
            $res = updateTable( 'events', 'gid,eid', "status", $ev );
        }
    }
}

?>
