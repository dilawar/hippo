<?php

trait AdminacadJC
{

    public function jc_action( )
    {
        $response = strtolower($_POST['response']);
        if( $response == 'add' )
        {
            if( ! __get__($_POST,'id', '') )
            {
                printErrorSevere( "Invalid id for JC.");
                redirect( "adminacad/jc");
                return;
            }

            $res = insertIntoTable( 'journal_clubs'
                , 'id,title,day,status,time,venue,description,send_email_on_days,scheduling_method'
                , $_POST
            );

            if( $res )
                flashMessage( "Added JC successfully" );
        }
        else if($response == 'update' )
        {
            $res = updateTable( 'journal_clubs'
                , 'id'
                , 'title,day,status,time,venue,description,send_email_on_days,scheduling_method'
                , $_POST
            );

            if( $res )
                flashMessage( "Updated successfully" );
        }
        else if( $response == 'delete' )
        {
            $res = deleteFromTable( 'journal_clubs' , 'id' , $_POST );
            if( $res )
                flashMessage( "Updated deleted entry" );
        }
        else
            printWarning( "$response is not implemented yet.");

        redirect( "adminacad/jc");
    }

    public function jc_admins_action()
    {
        $response = strtolower($_POST['response']);
        if( $response == 'add new admin' )
        {
            // The user may alredy be subscribed to this JC. If yes, then update the 
            // subscription_type to ADMIN.
            $res = insertOrUpdateTable( 'jc_subscriptions'
                , 'login,jc_id,subscription_type,last_modified_on'
                , 'subscription_type,last_modified_on'
                , $_POST
            );

            if( $res )
                flashMessage( "Added JC admin successfully");
        }
        else if( $response == 'remove admin' )
        {
            $_POST[ 'subscription_type' ] = 'NORMAL';
            $res = updateTable( 'jc_subscriptions'
                , 'jc_id,login', 'subscription_type', $_POST );
            if( $res )
                flashMessage("Successfully removed JC ADMIN from JC");
        }

        redirect('adminacad/jc_admins');
    }

}

?>
