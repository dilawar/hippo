<?php
require_once BASEPATH.'autoload.php';
require_once BASEPATH.'extra/jc.php';

trait JCAdmin
{
    // Views.
    public function jc_admin_edit_upcoming_presentation( )
    {
        $this->load_user_view( 'user_jc_admin_edit_upcoming_presentation' );
    }

    public function jc_admin( $arg )
    {
        $task = $_POST['response'];
        $this->jc_admin_task( $task );
    }

    public function jc_admin_task( $task )
    {
        if( $task == 'transfer_admin')
        {
            $newAdmin = $_POST['new_admin'];
            $jcID = $_POST[ 'jc_id'];

            if(! findAnyoneWithLoginOrEmail($newAdmin) )
            {
                printWarning( "No one was assigned");
                redirect( 'user/jcadmin');
                return;
            }

            $login = explode( '@', $newAdmin )[0];

            $_POST['subscription_type'] = "ADMIN";
            $_POST["status"] = "VALID";
            $_POST['login'] = $login;

            //  Remove my privileges.
            $res = updateTable( 'jc_subscriptions', 'login,jc_id'
                    , 'subscription_type'
                    , array( 'jc_id' => $_POST['jc_id'], 'login' => whoAmI(), 'subcription_type' => 'NORMAL')
                    );

            // Assign admin privileges to new user.
            $res = updateTable( 'jc_subscriptions', 'login,jc_id', 'subscription_type,status', $_POST );
            if( $res )
            {

                $subject = "You have been made ADMIN of $jcID by " . loginToText( whoAmI() );
                $msg = "<p>Dear " . loginToText( $newAdmin ) . "</p>";
                $msg .= "<p>You have been given admin rights to $jcID. In case this is
                    a mistake, " . loginToText( whoAmI( ) ) . ' is to blame!</p>';

                $cclist = 'hippo@lists.ncbs.res.in';
                $to = getLoginEmail( $newAdmin );
                $res = sendHTMLEmail( $msg, $subject, $to, $cclist );
                flashMessage( "Successfully transfrered admin rights to $login. $login has been notified.");
            }
            redirect( "user/jc" );
        }
    }


}

?>
