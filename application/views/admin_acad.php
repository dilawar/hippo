<?php

include_once 'header.php';
include_once 'tohtml.php';
include_once 'methods.php';
include_once 'database.php';

include_once 'check_access_permissions.php';
mustHaveAllOfTheseRoles( array( 'AWS_ADMIN' ) );

$logins = getLoginIds( );

$pendingRequests = getPendingAWSRequests( );
$pendingScheduleRequest = getTableEntries( 'aws_scheduling_request'
    , 'status', "status='PENDING'" );

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

echo userHTML( );

echo '<h1>ANNUAL WORK SEMINAR</h1>';
echo '<table class="tasks">
    <tr>
      <td>
        <i class="fa fa-cog fa-spin fa-2x fa-fw"></i>
        <a class="clickable" href="admin_acad_manages_upcoming_aws.php">Manage upcoming AWSes</a>
        </td>
        <td> <a class="clickable" href="admin_acad_manages_scheduling_request.php">
            Manage ' . count( $pendingScheduleRequest ) .
            ' pending scheduling requests</a> </td>
    </tr>
  </table>';

echo ' <br /> ';
echo '<table class="tasks">
    <tr>
        <td> <a class="clickable" href="admin_acad_add_aws_entry.php">Add Missing AWS entry</td>
        <td>
         <form method="post" action="">
            <input id="autocomplete_user1" name="login" placeholder="AWS Speaker" type="text" />
            <input class="datepicker" name="date" placeholder="date(optional)" value="" >
            <button name="response" value="Select">' . $symbCheck . '</button>
            </form>
            Enter a login and optionally AWS date and you can delete that AWS entry
            from my database.
        </td>
    </tr>
    <tr>
        <td>
            <form method="post" action="admin_acad_update_user.php">
                <i fa class="fa fa-graduation-cap fa-2x"></i>
                <input id="autocomplete_user" name="login"
                    placeholder="student login" >
                <button title="Add or remove speakers from AWS list"
                    name="response" value="edit">' . $symbUpdate .
                '</button>
            </form>
            Update AWS list <br />
        </td>
        <td></td>
    </tr>
    </table>';

echo '<br />';
echo '<table class="tasks">
    <tr>
        <td> <a class="clickable_small"
            href="admin_acad_manages_requests.php">Manage ' . count( $pendingRequests) .
            ' pending requests</a>
        </td>
        <td> <a class="clickable_small" href="admin_acad_email_and_docs.php">Emails and Documents</td>
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
            echo printInfo( "Successfully deleted" );
            goToPage( 'admin_acad.php', 0);
            exit;
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
  <table class="tasks">
    <tr>
        <td>
            <a class="clickable" href="admin_acad_manages_enrollments.php">Manage Enrollments</a>
            <p>Add/Remove student enrollments from  courses and assign grades.</p>
        </td>
        <td>
            <a class="clickable" href="admin_acad_manages_grades.php">Manage Grades</a>
            <p>Add/Remove student enrollments from  courses and assign grades.</p>
        </td>
    </tr>
    <tr>
        <td>
            <i class="fa fa-cog fa-spin fa-2x fa-fw"></i>
            <a class="clickable"
                 href="admin_acad_manages_current_courses.php">Manage this semester courses</a>
        </td>
        <td>
            <a class="clickable"
                href="./admin_acad_schedule_upcoming_courses.php">Schedule Upcoming Courses</a>
            <p> Compute the best possible schedule for course (NOT: Not complete).  </p>
        </td>
    </tr>
    <tr>
        <td> <a class="clickable"
             href="admin_acad_manages_slots.php">Manage Slots</a> <br />
            Add/Delete or update slot.
        </td>
        <td> <a class="clickable" href="admin_acad_manages_courses.php">Manage all courses</a>  <br />
        Add new courses, or update course description.</td>
    </tr>
  </table>
  ';

// Journal clubs.
echo '<h1>Journal Clubs</h1>';
echo '
  <table class="tasks">
    <tr>
        <td><a class="clickable" href="admin_acad_manages_jc.php">Add/Update Journal Clubs</a> </td>
        <td><a class="clickable" href="admin_acad_manages_jc_admins.php">Manage Journal Club Admins</a></td>
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
        likely these entries have no data. You need to fix them. "
    );

    echo '<form action="./admin_acad_update_upcoming_aws.php" method="post">';
    foreach( $badEntries as $aws )
    {
        echo alertUser( "This AWS is incomplete." );
        echo arrayToVerticalTableHTML( $aws, 'info', '', 'status,comment' );
        echo '<input type="hidden" name="response" value="update" />';
        echo '<button name="id" value="' . $aws[ 'id' ] . '">Fix</button>';
    }
    echo '</form>';
}


echo "<h2>Information</h2>";
echo '
  <table border="0" class="tasks">
    <tr>
        <td>AWS summary <small>
            See the summary of all AWSs. You may be able to spot missing AWS entry
            in "Date Wise" list.  </small>
        </td>
        <td>
            <a href="admin_acad_summary_user_wise.php">User wise</a>
            <br />
            <a href="admin_acad_summary_date_wise.php">Date wise</a>
        </td>
    </tr>
    <tr>
        <td>List of AWS speakers</td>
        <td> <a href="admin_acad_aws_speakers.php">AWS speaker</a> </td>
    </tr>
  </table>';

echo '<h1>Manage talks and seminars</h1>';
echo '<table class="tasks">';
echo '<tr>
        <td> <a class="clickable" href="admin_acad_manages_talks.php">Manage talks/seminar</td>
        <td> <a class="clickable" href="admin_acad_manages_speakers.php">Manage talk/seminar speakers</td>
    </tr>';
echo '</table>';

echo goBackToPageLink( 'user.php', 'Go back' );

?>
