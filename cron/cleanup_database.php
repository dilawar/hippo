<?php

function cleanup_database_cron( )
{
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
        $thesisSeminars = getTableEntries( 'talks', 'id'
                , "class='THESIS SEMINAR' OR class='PRESYNOPSIS THESIS SEMINAR'" 
            );

        foreach( $thesisSeminars as $talk )
        {
            $speaker = getSpeakerByID( $talk['speaker_id'] ) or getSpeakerByName( $talk[ 'speaker' ] );
            $login = findAnyoneWithEmail( __get__( $speaker, 'email', '' ) );
            if( __get__($login, 'login', '') )
            {
                $login = $login[ 'login'];
                if( isEligibleForAWS( $login ) )
                    removeAWSSpeakerFromList( $login );
            }
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

    if( trueOnGivenDayAndTime( 'today', '9:45' ) )
    {
        echo printInfo( "Cleaning up orphaned events" );
        $today = dbDate( 'today' );
        $events = getTableEntries( 'events', 'date', "date>'$today' AND status='VALID' AND external_id !='SELF.-1'" );
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

    // Remove those users who are not active on LDAP.
    if( trueOnGivenDayAndTime( 'this monday', '17:15' ) )
    {
        echo printInfo( "Removing inactive accounts" );
        $logins = getTableEntries( 'logins', 'login', "status='ACTIVE'");
        $toInactivate = [];
        foreach( $logins as $login )
        {
            // login can be an email.
            $id = explode('@', $login['login'])[0];

            if(!$id)
                continue;
            $ldap = getUserInfoFromLdap( $id );
            if( ! $ldap )
                $toInactivate[] = $id;

            $isActive = strtolower($ldap['is_active']);
            if( $isActive === 'false' )
                $toInactivate[] = $id;
        }
        inactiveAccounts( $toInactivate);
    }
}


?>
