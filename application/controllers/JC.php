<?php
require_once BASEPATH.'autoload.php';
require_once BASEPATH.'extra/jc.php';

trait JC 
{
    // VIEWS
    public function jc( string $arg = '', string $arg2 = '' )
    {
        $this->load_user_view( 'user_jc' );
    }


    public function jc_request( )
    {
        $this->load_user_view("user_manages_jc_presentation_requests");
    }

    // ACTION.
    public function jc_action( string $action  )
    {
        if( $action == 'Unsubscribe' )
        {
            $_POST[ 'status' ] = 'UNSUBSCRIBED';
            $res = updateTable(
                'jc_subscriptions'
                , 'login,jc_id', 'status', $_POST
            );
            if( $res )
            {
                // Send email to jc-admins.
                $jcAdmins = getJCAdmins( $_POST[ 'jc_id' ] );
                $tos = implode( ","
                    , array_map(
                        function( $x ) { return getLoginEmail( $x['login'] ); }, $jcAdmins )
                    );
                $user = whoAmI( );
                $subject = $_POST[ 'jc_id' ] . " | $user has unsubscribed ";
                $body = "<p> Above user has unsubscribed from your JC. </p>";
                sendHTMLEmail( $body, $subject, $tos, 'jccoords@ncbs.res.in' );
                flashMessage( 'Successfully unsubscribed from ' . $_POST['jc_id'] );
            }
            else
                flashMessage( "Failed to unsubscribe from JC." );
        }
        else if( $action == 'Subscribe' )
        {
            $_POST[ 'status' ] = 'VALID';
            $res = insertOrUpdateTable('jc_subscriptions', 'login,jc_id', 'status',  $_POST);
            if( $res )
                flashMessage( 'Successfully subscribed to ' . $_POST['jc_id'] );
        }
        else
            flashMessage( "unknown action $action." );

        redirect( 'user/jc' );
    }

    public function jc_admin_submit( )
    {
        if( __get__( $_POST, 'response', '' ) == 'Add' )
        {
            // Add new members
            $logins = $_POST[ 'logins'];
            $logins = preg_replace( '/\s+/', ',', $logins );

            $logins = explode( ',', $logins );

            $anyWarning = false;
            foreach( $logins as $emailOrLogin )
            {
                $login = explode( '@', $emailOrLogin )[0];

                if( ! getLoginInfo( $login ) )
                {
                    echo printWarning( "$login is not a valid Hippo id. Searching for others... " );
                    $anyWarning = true;
                    continue;
                }

                $_POST[ 'status' ] = 'VALID';
                $_POST[ 'login' ] = $login;
                $res = insertOrUpdateTable( 'jc_subscriptions'
                    , 'jc_id,login', 'status', $_POST );

                if( ! $res )
                    $anyWarning = true;
                else
                {
                    flashMessage( "$login is successfully added to JC" );
                }
            }

            if( ! $anyWarning )
            {
                redirect( "user/jcadmin");
                return;
            }
        }
        else if( $_POST['response'] == 'DO_NOTHING' )
        {
            flashMessage( "User cancelled operation." );
            redirect( "user/jcadmin" );
            return;
        }
        else if( $_POST['response'] == 'delete' )
        {
            $_POST[ 'status' ] = 'UNSUBSCRIBED';
            $res = updateTable( 'jc_subscriptions', 'login,jc_id', 'status', $_POST );
            if( $res )
            {
                flashMessage( ' ... successfully removed ' . $_POST[ 'login' ] );
                redirect( 'user/jcadmin' );
                return;
            }
        }
        else if( $_POST['response'] == 'Assign Presentation' )
        {
            $anyError = false;
            if( strtotime( $_POST[ 'date' ]) < strtotime( 'today' ) )
            {
                echo printWarning( "You cannot assign JC presentation in past." );
                echo printInfo( " Assignment date: " . humanReadableDate( $_POST[ 'date' ] ) );
            }
            else
            {
                $login = getLoginID( $_POST[ 'presenter' ] );
                $loginInfo = getLoginInfo( $login );

                if( ! $loginInfo )
                {
                    echo printWarning( "I could not find $login in database. Searching for speaker database." );

                    // Probably a special speaker.
                    $presenter = $_POST[ 'presenter' ];
                    $someone = findAnyoneWithEmail( $presenter );
                    if( ! $someone )
                        $anyError = true;
                }

                if( ! $anyError )
                {
                    $res = assignJCPresentationToLogin( $_POST['presenter'],  $_POST );
                    if( $res )
                    {
                        flashMessage( 'Assigned user ' . $_POST[ 'presenter' ] .
                            ' to present a paper on ' . dbDate( $_POST['date' ] )
                        );
                    }
                }
                else
                {
                    echo printWarning("Failed to assign anyone.");
                }
            }

            redirect( 'user/jcadmin' );
            return;
        }
        else if( $_POST[ 'response' ] == 'Remove Presentation' )
        {
            $_POST[ 'status' ] = 'INVALID';
            $res = updateTable( 'jc_presentations', 'id', 'status', $_POST );

            if( $res )
            {
                $data = getTableEntry( 'jc_presentations', 'id', $_POST );
                $to = getLoginEmail( $data[ 'presenter' ] );
                $cclist = 'hippo@ncbs.res.in,jccoords@ncbs.res.in';

                $subject = $data[ 'jc_id' ] . ' | Your presentation date has been removed';
                $msg = '<p>
                    Your presentation scheduled on ' . humanReadableDate( $data['date'] )
                    . ' has been removed by JC coordinator ' . $_SESSION[ 'user' ]
                    . '</p>';

                $msg .= '<p> If it is a mistake, please contant your JC coordinator. </p>';
                $res = sendHTMLEmail( $msg, $subject, $to, $cclist );
                if( $res )
                {
                    flashMessage( "Successfully invalidated entry." );
                    redirect( 'user/jcadmin' );
                    return;
                }
            }
        }
        else if( $_POST[ 'response' ] == 'Remove Incomplete Presentation' )
        {
            $res = deleteFromTable( 'jc_presentations', 'id', $_POST );
            if( $res )
            {
                flashMessage( "Successfully deleted entry!" );
                redirect( 'user/jcadmin' );
                return;
            }
        }
        else
        {
            echo alertUser( "Response " . $_POST[ 'response' ] . ' is not known or not
                supported yet' );
        }
        redirect( 'user/jcadmin' );
    }
}

?>
