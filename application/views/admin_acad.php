<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

$ref = "adminacad";
if(isset($controller))
    $ref = $controller;

$logins = getLoginIds( );
$pendingRequests = getPendingAWSRequests( );
$pendingScheduleRequest = getTableEntries( 'aws_scheduling_request', 'status', "status='PENDING'" );

?>

<!-- Script to autocomplete user -->
<script>
$(function() {
    var logins = <?php echo json_encode( $logins ); ?>;
    //console.log( logins );
    $( "#autocomplete_user" ).autocomplete( { source : logins });
    $( "#autocomplete_user1" ).autocomplete( { source : logins });
});
</script>

<?php

$symbUpdate = '<i class="fa fa-check"></i>';

echo '<table class="admin"><tr>';
echo '<td><a class="clickable" href="'.site_url('adminacad/upcoming_aws').'">Manage Upcoming AWSes</a></td>';
echo '<td><a class="clickable" href="'.site_url('adminacad/scheduling_request'). '">
        Manage ' . count( $pendingScheduleRequest) . ' Pending Scheduling Requests</a></td>';
echo '</tr></table>';
echo ' <br /> ';

echo '<table class="admin">
    <tr>
        <td> Update AWS speaker<br />
            <form method="post" action="' . site_url("$ref/update_aws_speaker") . '">
                <input id="autocomplete_user" name="login" placeholder="student login" >
                <button title="Add or remove speakers from AWS list"
                    name="response" value="edit">' . $symbUpdate . '</button>
            </form>
        </td>
        <td>
         <form method="post" action="">
            Enter a login and optionally AWS date and you can delete that AWS entry
            from my database.
            <input id="autocomplete_user1" name="login" placeholder="AWS Speaker" type="text" />
            <input class="datepicker" name="date" placeholder="date(optional)" value="" >
            <button name="response" value="Select"> <i class="fa fa-check fa-1x"></i>
             </button>
            </form>
        </td>
    </tr>
    </table>';

echo '<br />';
echo '<table class="admin">
    <tr>
        <td> <a class="clickable_small"
            href="'.site_url('adminacad/requests').'">Manage ' . count( $pendingRequests) .
            ' pending requests</a>
        </td>
        <td> <a class="clickable_small" href="'.site_url('adminacad/email_and_docs').'">Emails and Documents</td>
    </tr>
    <tr>
        <td> <a class="clickable_small" href="'.site_url('adminacad/add_aws_entry').'">Add Missing AWS entry</td>
        <td></td>
    </tr>
    </table>';

$login = null;
$date = null;
if( isset( $_POST[ 'response' ] ))
{
    if( $_POST[ 'response' ] == 'Select' )
    {
        $login = $_POST[ 'login' ];
        $date = $_POST[ 'date' ];
    }

    else if( $_POST[ 'response' ] == 'DO_NOTHING' )
    {

    }
    else if( $_POST[ 'response' ] == 'delete' )
    {
        echo "Deleting this AWS entry.";
        $res = deleteAWSEntry( $_POST['speaker'], $_POST['date' ] );
        if( $res )
        {
            flashMessage( "Successfully deleted" );
            redirect( 'adminacad' );
        }
    }

    $awss = array( );
    if( $login and $date )
        $awss = array( getMyAwsOn( $login, $date ) );
    else if( $login )
        $awss = getAwsOfSpeaker( $login );

    /* These AWS are upcoming */
    foreach( $awss as $aws )
    {
        if( ! $aws )
            continue;

        $speaker = $aws[ 'speaker' ];
        $date = $aws['date'];
        echo "<a>Entry for $speaker (" . loginToText( $speaker ) . ") on " .
            date( 'D M d, Y', strtotime( $date ) ) . "</a>";

        echo arrayToVerticalTableHTML( $aws, 'annual_work_seminars' );
        echo '<br>';

        /* This forms remain on this page only */
        echo '<form method="post" action="">
            <input type="hidden" name="speaker" value="' . $speaker . '">
            <input type="hidden" name="date" value="' . $date . '" >
            <button onclick="AreYouSure(this)"
                style=\"float:right\" name="response" >'. $symbDelete . '</button>
            </form>
            ';
    }
}


/*
 * Course work,
 */
echo '<h1>COURSES</h1>';
echo '
  <table class="admin">
    <tr>
        <td>
            <a class="clickable" href="'.site_url('adminacad/enrollments').'">Manage Enrollments</a>
            <p>Add/Remove student enrollments from  courses and assign grades.</p>
        </td>
        <td>
            <a class="clickable" href="'.site_url('adminacad/grades').'">Manage Grades</a>
            <p>Add/Remove student enrollments from  courses and assign grades.</p>
        </td>
    </tr>
    <tr>
        <td>
            <i class="fa fa-cog fa-spin fa-2x fa-fw"></i>
            <a class="clickable"
                 href="'.site_url('adminacad/current_courses').'">Manage this semester courses</a>
        </td>
        <td>
            <a class="clickable"
                href="'.site_url('adminacad/schedule_upcoming_courses').'">Schedule Upcoming Courses</a>
            <p> Compute the best possible schedule for course (NOT: Not complete).  </p>
        </td>
    </tr>
    <tr>
        <td> <a class="clickable"
             href="'.site_url('adminacad/slots').'">Manage Slots</a> <br />
            Add/Delete or update slot.
        </td>
        <td> <a class="clickable" href="'.site_url('adminacad/courses').'">Manage all courses</a>  <br />
        Add new courses, or update course description.</td>
    </tr>
  </table>
  ';

// Journal clubs.
echo '<h1>Journal Clubs</h1>';
echo '
  <table class="admin">
    <tr>
        <td><a class="clickable" href="'.site_url('adminacad/jc').'">Add/Update Journal Clubs</a> </td>
        <td><a class="clickable" href="'.site_url('adminacad/jc_admins').'">Manage Journal Club Admins</a></td>
    </tr>
  </table>
  ';



echo '<h1>EXTRA</h1>';
echo "<h2>Automatic Housekeeping</h2>";

$badEntries = doAWSHouseKeeping( );
if( count( $badEntries ) == 0 )
    echo printInfo( "AWS House is in order" );
else
{
    // can't make two forms on same page with same action. They will merge.
    echo alertUser( "Following entries could not be moved to main AWS list. Most
        likely these entries have no data. You need to fix them. ", false
    );

    echo '<form action="adminacad/update_upcoming_aws" method="post">';
    foreach( $badEntries as $aws )
    {
        echo alertUser( "This AWS is incomplete.", false );
        echo arrayToVerticalTableHTML( $aws, 'info', '', 'status,comment' );
        echo '<input type="hidden" name="response" value="update" />';
        echo '<button name="id" value="' . $aws[ 'id' ] . '">Fix</button>';
    }
    echo '</form>';
}


echo "<h2>Information</h2>";
echo '
  <table border="0" class="admin">
    <tr>
        <td>AWS summary <small>
            See the summary of all AWSs. You may be able to spot missing AWS entry
            in "Date Wise" list.  </small>
        </td>
        <td>
            <a href="'.site_url('adminacad/summary_user_wise').'">User wise</a>
            <br />
            <a href="'.site_url('adminacad/summary_date_wise').'">Date wise</a>
        </td>
    </tr>
    <tr>
        <td>List of AWS speakers</td>
        <td> <a href="'.site_url('adminacad/aws_speakers').'">AWS speaker</a> </td>
    </tr>
  </table>';

echo '<h1>Manage talks and seminars</h1>';
echo '<table class="admin">';
echo '<tr>
        <td> <a class="clickable" href="'.site_url('adminacad/talks').'">Manage talks/seminar</td>
        <td> <a class="clickable" href="'.site_url('adminacad/speakers').'">Manage talk/seminar speakers</td>
    </tr>';
echo '</table>';

echo goBackToPageLink( 'adminacad', 'Go back' );

?>
