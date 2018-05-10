<?php

require_once BASEPATH. 'autoload.php';

trait JC 
{
    public function jc( string $arg = '', string $arg2 = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'user_jc' );
    }

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
}

?>
