<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

require_once __DIR__ . '/AWS.php';
require_once __DIR__ . '/JC.php';
require_once __DIR__ . '/Booking.php';

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  There are traits AWS, Courses etc. which this class can use;
    * since multiple inherihence is not very straightforward in php.
 */
/* ----------------------------------------------------------------------------*/
class User extends CI_Controller
{
    use AWS;
    use Booking;
    use JC;

    public function load_user_view( $view, $data = array() )
    {
        $data['controller'] = 'user';
        $this->template->set( 'header', 'header.php' );
        $this->template->load( $view, $data );
    }

    public function index( )
    {
        $this->home();
    }

    // Show user home.
    public function home()
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'user' );
    }

    // BOOKING. Rest of the functions are in Booking traits.
    public function book( $arg = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load('user_book');
    }

    public function bmv_browse( )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load('bookmyvenue_browse');
    }

    public function download( $filename )
    {
        $filepath = sys_get_temp_dir() . "/$filename";
        if( file_exists( $filepath ) )
        {
            $content = file_get_contents( $filepath );
            force_download( $filename, $content );
        }
        else
            flashMessage( "File $filename does not exist!", "warning" );

        if ($this->agent->is_referral())
            redirect( $this->agent->referrer() );
        else
            redirect( 'user/home' );
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

    // Show courses.
    public function courses( $arg = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'user_manages_courses' );
    }

    // Edit update courses.
    public function manage_course( $action = '' )
    {
        // There must be a course id.
        $cid = __get__( $_POST, 'course_id', '' );
        if( ! $cid )
        {
            flashMessage( 'No course selected!', 'warning' );
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

            redirect( 'user/courses' );
        }
        else if( $action == 'feedback' )
        {
            echo "Give feedback";
        }
        else
        {
            flashMessage( "Not implemented yet $action" );
            redirect( 'user/courses' );
        }
    }

    public function downloadaws( )
    {

    }

    public function logout( )
    {
        $this->session->sess_destroy();
        redirect( 'welcome' );
    }
}

?>
