<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

class User extends CI_Controller
{
    // Show user home.
    public function home()
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'user' );
    }

    // BOOKING
    public function book( $arg = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load('quickbook');
    }

    // USER EDITING PROFILE INFO
    public function info( $arg = '' )
    {
        // Update the page here.
        if( $arg == 'action' && $_POST )
        {
            // Not all login can be queried from ldap. Let user edit everything.
            $where = "valid_until,first_name,last_name,title,pi_or_host,specialization" . 
                        ",institute,laboffice,joined_on,alternative_email";

            $_POST['login'] = whoAmI();
            $res = updateTable("logins", "login", $where, $_POST);
            if( $res )
            {
                echo msg_fade_out( "User details have been updated sucessfully" );

                // Now send an email to user.
                $info = getUserInfo( whoAmI( ) );
                if( isset( $info['email'] ) )
                {
                    sendHTMLEmail( arrayToVerticalTableHTML( $info, "details" )
                        , "Your details have been updated successfully."
                        , $info[ 'email' ]);
                }
            }
            else
                echo printWarning( "Could not update user details " );
        }
        else if( $arg == 'upload_picture' && $_POST )
        {
            $conf = getConf( );
            $picPath = $conf['data']['user_imagedir'] . '/' . $_SESSION[ 'user' ] . '.jpg';
            if( $_POST[ 'Response' ] == 'upload' )
            {
                $img = $_FILES[ 'picture' ];
                if( $img[ 'error' ] != UPLOAD_ERR_OK )
                {
                    $errCode = $img[ 'error' ];
                    echo minionEmbarrassed( "This file could not be uploaded", $img['error'] );
                }

                $ext = explode( "/", $img['type'] )[1];
                $tmppath = $img[ 'tmp_name' ];

                if( $img['size'] > 1024 * 1024 )
                    echo printWarning( "Picture is too big. Maximum size allowed is 1MB" );
                else
                {
                    // Convert to png file and tave to $picPath
                    try {
                        $res = saveImageAsJPEG( $tmppath, $ext, $picPath );
                        if( ! $res )
                            echo minionEmbarrassed( 
                                "I could not upload your image (allowed formats: png, jpg, bmp)!" 
                            );
                        else
                            echo printInfo( "File is uploaded sucessfully" );
                    } catch (Exception $e ) {
                        echo minionEmbarrassed( 
                            "I could not upload your image. Error was "
                            , $e->getMessage( ) );
                    }
                }
            }
        }

        $this->template->set( 'header', 'header.php' );
        $this->template->load('user_info' );
    }

    public function booking_request( $args = null )
    {
        log_message( 'info', 'Creating booking requests' );
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'user_submit_booking_request' );
    }

    // Show courses.
    public function courses( $arg = '' )
    {
        $this->session->set_flashdata( 'info', "With argument $arg." );
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'user_manages_courses' );
        
    }

    // Edit update courses.
    public function manage_course( $action = '' )
    {
        // There must be a course id.
        if( ! __get__( $_POST, 'course_id', '' ) )
        {
            $this->session->set_flashdata( 'error', 'No course selected!' );
            redirect( 'user/courses' );
        }

        if( $action == 'register' )
        {
            $_POST[ 'last_modified_on' ] = dbDateTime( 'now' );
            $_POST[ 'registered_on' ] = dbDateTime( 'now' );

            $res = insertIntoTable( 'course_registration'
                , 'student_id,semester,year,type,course_id,registered_on,last_modified_on'
                , $_POST 
            );

            if( ! $res )
                $this->session->set_flashdata( 'error', "Could not register" );

        }
    }


    public function logout( )
    {
        $this->session->sess_destroy();
        redirect( 'welcome' );
    }
}

?>
