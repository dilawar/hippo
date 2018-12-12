<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

// require_once __DIR__.'/AdminAcad.php';


class Admin extends CI_Controller
{

    function index()
    {
        $this->home();
    }

    public function load_admin_view( $view, $data = array() )
    {
        $data['controller'] = 'admin';
        $this->template->set( 'header', 'header.php' );
        $this->template->load( $view, $data );
    }

    // Show user home.
    public function home()
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin' );
    }


    public function addupdatedelete( )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin_updateuser' );
    }

    public function holidays()
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin_manages_holidays' );
    }

    public function updateuser( $user = '' )
    {
        $toUpdate = 'roles,honorific,title,joined_on,eligible_for_aws,laboffice' .
           ',status,valid_until,alternative_email,pi_or_host,specialization,designation';
        $res = updateTable( 'logins', 'login', $toUpdate, $_POST );
        if( $res )
            echo flashMessage("Successfully updated.");

        redirect('admin/addupdatedelete');
    }

    public function deleteuser( $md5 = '' )
    {
        $user = $_POST['login'];
        $res = deleteFromTable( 'logins', 'login', $_POST  );
        if( $res )
        {
            echo flashMessage( "Successfully deleted $user." );
            if( $this->agent->is_referral() )
                redirect( $this->agent->referrer() );
            else
                redirect( 'admin' );
        }
    }

    public function showusers( $arg = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin_showusers' );
    }

    public function emailtemplates( $arg = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin_manages_email_templates' );
    }

    public function templates_task( $arg = '' )
    {
        $response = strtolower($_POST['response']);
        $templateID = $_POST[ 'id'];
        if( $response == 'update' )
        {
            $_POST[ 'modified_on' ] = date( 'Y-m-d H:i:s', strtotime( 'now' ));
            $res = updateTable( 'email_templates'
                , 'id'
                , 'when_to_send,description,cc,recipients'
                , $_POST
            );

            if( $res )
                flashMessage( "Successfully updated email template $templateID." );
            else
                flashMessage( "I could not update email templates $templateID.", 'warning');

        }
        else if( $response == 'add' )
        {
            $_POST[ 'modified_on' ] = date( 'Y-m-d H:i:s', strtotime( 'now' ));
            $res = insertIntoTable( 
                'email_templates' , 'id,when_to_send,description,recipients,cc'
                , $_POST 
            );

            if( $res )
                flashMessage( 'Successfully added a new email template.' );
            else
                flashMessage( "I could not add new email template $templateID.", 'warning');

        }
        else if( $response == 'delete' )
        {
            $res = deleteFromTable( 'email_templates' , 'id' , $_POST );
            if( $res )
                flashMessage( "Successfully deleted email template $templateID." );
            else
                flashMessage( "I could not delete email template $templateID.", 'warning' );

        }
        else
            flashMessage( "I don't understand what you are asking me to do: $response.");

        redirect( 'admin/emailtemplates' );
    }

    /* Manage faculty */
    public function faculty( $arg = '' )
    {
        $this->template->set('header', 'header.php');
        $this->template->load( 'admin_manages_faculty.php');
    }

    public function faculty_task( $arg = '' )
    {
        $response = strtolower($_POST['response']);
        if( $response == 'update' )
        {
            $_POST[ 'modified_on' ] = date( 'Y-m-d H:i:s', strtotime( 'now' ));
            $res = updateTable( 'faculty', 'email'
                , 'first_name,middle_name,last_name,status' . 
                    ',modified_on,url,specialization,affiliation,institute'
                , $_POST 
                );

            if( $res )
                flashMessage( 'Successfully updated faculty' );
            else
                flashMessage( "I could not update faculty", 'warning' );
        }
        else if( $response == 'add' )
        {
            $_POST[ 'modified_on' ] = date( 'Y-m-d H:i:s', strtotime( 'now' ));
            $res = insertIntoTable( 'faculty'
                , 'email,first_name,middle_name,last_name,status' . 
                    ',modified_on,url,specialization,affiliation,institute'
                , $_POST 
                );

            if( $res )
                flashMessage( 'Successfully added a new faculty' );
            else
                flashMessage( "I could not edit new faculty.", 'warning' );
        }
        else if( $response == 'delete' )
        {
            $res = deleteFromTable( 'faculty', 'email', $_POST );
            if( $res )
                echo flashMessage( "Successfully deteleted faculty." );
            else
                echo flashMessage( "Failed to delete entry from table.", 'warning' );
        }
        else
            flashMessage("Not implemented yet $response.", "warning");

        redirect('admin/faculty');
    }

    // Update configuration
    public function configuration( $arg='')
    {
        if( $_POST['response'] == 'Add Configuration' )
        {
            $res = insertOrUpdateTable( 'config'
                , 'id,value,comment', 'value,comment'
                , $_POST );

            if( $res )
                flashMessage( 'Successfully added new config' );
        }
        redirect( 'admin');
    }

    public function add_holiday( )
    {
        if( $_POST['date'] && $_POST['description'] )
        {
            $res = insertIntoTable( "holidays"
                , "date,description,schedule_talk_or_aws"
                , $_POST );
            if( $res )
                flashMessage( "Added holiday successfully" );
            else
                flashMessage( "Could not add holiday to database" );
        }
        else
            flashMessage("Either 'date' or 'description' of holiday was incomplete", "ERROR");
        redirect( 'admin/holidays' );
    }

    public function delete_holiday( )
    {
        $res = deleteFromTable( 'holidays', 'date,description', $_POST );
        if( $res )
        {
            echo flashMessage( "Successfully deleted entry from holiday list" );
        }
        else
        {
            flashMessage("Could not delete holiday from the list", "Warn" );
        }
        redirect( "admin/holidays" );
    }
}

?>
