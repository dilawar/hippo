<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

require_once __DIR__ . '/AWS.php';
require_once __DIR__ . '/JC.php';
require_once __DIR__ . '/JCAdmin.php';
require_once __DIR__ . '/Booking.php';
require_once __DIR__ . '/Lab.php';

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
    use JCAdmin;
    use Lab;

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

    public function redirect( $to = null) 
    {
        if( $to )
            redirect( $to );

        if ($this->agent->is_referral())
            redirect( $this->agent->referrer() );
        else
            redirect( 'user/home' );

    }


    public function update_supervisors( )
    {
        $this->load_user_view( "user_update_supervisors" );
    }

    public function execute( $id )
    {
        $data = array( 'id' => $id );
        $this->load_user_view( "execute", $data );
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

    public function givefeedback($course_id, $semester, $year)
    {
        if( $course_id &&  $semester && $year )
        {
            $this->load_user_view( 'user_give_feedback.php'
                , array('course_id'=>$course_id, 'semester'=>$semester, 'year'=>$year) 
            );
        }
        else
        {
            $msg = "Invalid values: course_id=$course_id, semester=$semester, year=$year";
            printWarning( $msg );
            redirect('user/home');
        }
    }

    public function bmv_browse( )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load('bookmyvenue_browse');
    }

 
    public function download( $filename, $redirect = true )
    {
        if( "/" == $filename[0] )
            $filepath = $filename;
        else
            $filepath = sys_get_temp_dir() . "/$filename";

        if( file_exists( $filepath ) )
        {
            $content = file_get_contents( $filepath );
            force_download( $filename, $content );
        }
        else
            flashMessage( "File $filepath does not exist!", "warning" );

        if( $redirect )
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
            $where = "valid_until,honorific,first_name,last_name,title,pi_or_host,specialization" . 
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

    public function update_supervisor_submit( )
    {
        if( trim( $_POST[ 'email'] ) && trim( $_POST[ 'first_name' ] ) )
        {
            $res = insertOrUpdateTable( 'supervisors'
                , 'email,first_name,middle_name,last_name,affiliation,url'
                , 'first_name,middle_name,last_name,affiliation,url'
                , $_POST 
            );

            if( $res )
                flashMessage( "Successfully added/updated supervisor to list." );
            else
                printWarning( "Could not add or update supervisor." );
        }
        redirect( "user/home" );
    }

    // submit poll.
    public function submitpoll( )
    {
        $course_id = $_POST['course_id'];
        $semester = $_POST['semester'];
        $year = $_POST['year'];

        if(!($year && $semester && $year))
        {
            $msg = "Either semester, year or course_id was invalid.";
            $msg .= json_encode( $_POST );
            printWarning( $msg );
            redirect( 'user/home' );
            return;
        }

        $external_id = "$year.$semester.$course_id";

        // Keep data in array to table updating.
        $entries = array();
        foreach( $_POST as $key => $val )
        {
            preg_match( '/qid\=(?P<qid>\d+)/', $key, $m );
            if($m)
            {
                $entry = array('external_id' => $external_id, 'question_id' => $m['qid']
                        , 'login' => whoAmI() , 'response' => $val
                        );
                $entries[] = $entry;
            }
        }

        // Update poll_response table now.
        $msg = '';
        $error = false;
        foreach( $entries as $entry )
        {
            // $msg .= json_encode($entry);
            $res = insertOrUpdateTable('poll_response', 'login,question_id,external_id,response', 'response', $entry);
            if(!$res)
            {
                $msg .= 'Faieled to record response for question id ' . json_encode($entry);
                $error = true;
            }
        }

        if($error)
            printWarning( $error );

        // flashMessage( $msg );

        redirect("user/givefeedback/$course_id/$semester/$year");
    }


    public function downloadaws( $date, $speaker = '')
    {
        $pdffile = pdfFileOfAWS( $date, $speaker );
        $this->download( $pdffile, false );

        echo '<script type="text/javascript" charset="utf-8">
                window.onload = function() {
                    window.close();
                };
            </script>';
    }

    public function downloadtalk( $date, $id )
    {
        $pdffile = generatePdfForTalk( $date, $id );
        $this->download( $pdffile, false );

        echo '<script type="text/javascript" charset="utf-8">
                window.onload = function() {
                    window.close();
                };
            </script>';
    }

    public function logout( )
    {
        $this->session->sess_destroy();
        redirect( 'welcome' );
    }

    public function upload_to_db( $tablename, $unique_key, $redirect = 'home')
    {
        $filename = $_FILES['spreadsheet']['tmp_name']; $data =
        $data = read_spreadsheet( $filename );
        $header = $data[0];
        $data = array_slice( $data, 1);

        $query = '';
        foreach( $data as $row )
        {
            if( ! $row or count($row) != count($header) )
                continue;

            $toupdate = array();
            $allkeys = array();
            $keyval = array();
            foreach( $header as $i => $key )
            {
                if(!$key )
                    continue;

                $val = $row[$i];
                if( !$val or $val == 'NULL' )
                    continue;

                $allkeys[] = $key;
                if($key != $unique_key)
                {
                    $toupdate[] = $key;
                }

                $keyval[$key] = $val;
                $query .= "$key='$val' ";
            }

            $query .= ';';
            if( getTableEntry( $tablename, $unique_key, $keyval ) )
                $res = updateTable( $tablename, $unique_key, $toupdate, $keyval );
            else
                $res = insertIntoTable( $tablename, $allkeys, $keyval ); 
        }

        // flashMessage( $query );
        redirect( "user/$redirect" );
    }

    public function execute_submit( )
    {
        $login = $_POST[ 'login' ];
        $pass = $_POST[ 'password' ];
        $id = $_POST[ 'id' ];
        $auth = authenticate( $login, $pass );
        if( ! $auth )
        {
            printWarning( "Authentication failed. Try again." );
            redirect( "user/execute/$id" );
            return;
        }

        $query = getTableEntry( 'queries', 'id', $_POST );
        $res = executeURlQueries( $query['query'] );
        if( $res )
        {
            $_POST[ 'status' ] = 'EXECUTED';
            $res = updateTable( 'queries', 'id', 'status', $_POST );
            if( $res )
                flashMessage( "Success! " );
        }
        redirect( "user/welcome" );
    }
}

?>
