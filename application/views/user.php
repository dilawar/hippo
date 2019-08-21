<?php
require_once BASEPATH. 'autoload.php';
echo userHTML( );
$thisSem = getCurrentSemester( ) . ' ' . getCurrentYear( );
?>

<div class="row">
    <div class="col card m-3 p-1 text-center">
        <i class="fa fa-book fa-2x">
            <a class="clickable" href="<?=site_url('/user/courses')?>">My Courses</a>
        </i>
        <br />
        Register/deregister courses for <?=$thisSem?> semster.
    </div>
<?php if( __get__($cUserInfo, 'eligible_for_aws', 'NO' ) == 'YES'): ?>
    <div class="col card m-3 p-1 text-center">
        <i class="fa fa-graduation-cap fa-2x">
            <a class="clickable" href="<?=site_url("/user/aws")?>" >My AWS</a> 
        </i>
            See your previous AWSs and update them. Check
            the details about upcoming AWS and provide preferred dates.
            <br />
            <a class="btn btn-secondary"
                 href="<?=site_url("/user/update_supervisors") ?>">
                Update TCM Members/Supervisors</a>
    </div>
<?php else: ?>
    <div class="col card">
    </div>
<?php endif; ?>
</div>

<?php
// Journal club entry.
$table = '<table class="admin">
    <tr>
        <td>
            <a class="clickable" href="'. site_url("/user/jc"). '">My Journal Clubs</a> <br />
            Subscribe/Unsubscribe from journal club. See upcoming presentation.
            Vote on presentation requests.
         </td>
        <td>
            <a class="clickable" href="'. site_url("/user/jc_presentation_requests"). '">
                My JC Presentation Requests</a>
            <br />
            Submit a journal paper and a preferred date to present it. You can submit
            one presentations requests for nay date. The community can vote on the
            presentation requests.
         </td>
    </tr>';

if( isJCAdmin( whoAmI() ) )
{
    $table .= '<tr>
        <td>
        <i class="fa fa-lock fa-2x"></i>
        <a class="clickable" href="'. site_url("/user/jcadmin"). '">JC Admin</a> <br />
        Journal club admin</td>
        <td></td>
    </tr>';
}


$table .= '</table>';
echo $table;

echo '<h1>Booking</h1>';
$html = '<table class="admin">';

// Count pending requests.
$user = whoAmI();
$reqs = getTableEntries( 'bookmyvenue_requests', 'date'
                    , "status='PENDING' AND created_by='$user' GROUP BY gid" 
                );

$flag = '';
if( count( $reqs ) > 0)
    $flag = count( $reqs ) . " requests.";


$html .= '
    <tr>
    <td>
        <i class="fa fa-hand-pointer-o fa-2x"></i>
         <a class="clickable" href="'. site_url("/user/book/venue"). '">Book for small event</a>
         <br />
         E.g. Labmeets,meeting,interview etc.. No email will be sent to Academic community.
        <br /> <br /> <i class="fa fa-pencil-square-o fa-1x"></i>
        <a href="'. site_url("/user/show_private") . '"> Manage My Private Events ('. $flag .')</a> 
    </td>
    <td>
        <i class="fa fa-comments fa-2x"></i>
        <a class="clickable" href="'. site_url("/user/register_talk"). '">Book for talks/seminar etc. (Academic Events)</a>
        <br />
        E.g. talk, seminar, public lectures, pre-synopsis/thesis seminar; essentially any event 
        for which academic community needs to be notified by email.
        <br /> <br /> <i class="fa fa-pencil-square-o fa-1x"></i>
        
        <a href="'. site_url("/user/show_public"). '">Manage my public events.</a>
    </td>
   </tr>
   </table>';
echo $html;

echo '<h1>Lab management</h1>';

echo printNote( 
    "You can create equipments private to your lab. These equipments can be booked by your lab members. 
    This is not a replacement for common-equipment booking system." 
    );

$html = '<table class="admin">';
$html .= '
    <tr>
    <td>
        <i class="fa fa-hand-archive fa-2x"></i>
         <a class="clickable" href="'. site_url("/user/inventory_browse"). '">Browse Lab Inventory or Book Equipments</a>
    </td>
    <td>
        <i class="fa fa-flask fa-2x"></i>
         <a class="clickable" href="'. site_url("/user/inventory_manage"). '">Manage Lab Inventory</a>
    </td>
    </tr>
   </table>';
echo $html;

if( anyOfTheseRoles( 'ADMIN,BOOKMYVENUE_ADMIN,JOURNALCLUB_ADMIN,ACAD_ADMIN' ) )
{
   echo "<h1> <i class=\"fa fa-cogs\"></i>   Admin</h1>";
   $roles =  getRoles(whoAmI() );

   $html = "<table class=\"admin\">";
   if( in_array( "ADMIN", $roles ) )
       $html .= '<tr><td>
                <i class="fa fa-lock fa-2x"></i>
                <a class="clickable" href="'. site_url("/admin"). '"> Admin</a></td>';

   if( in_array( "BOOKMYVENUE_ADMIN", $roles ) )
       $html .= '<td><i class="fa fa-calendar-plus-o fa-2x"></i>
            <a class="clickable" href="'. site_url("/adminbmv"). '"> 
            BookMyVenue Admin</a></td>';
   else
       $html .= ' <td></td>';

   if( in_array( "ACAD_ADMIN", $roles ) )
       $html .= '<td><i class="fa fa-graduation-cap fa-2x"></i>
            <a class="clickable" href="'. site_url("/adminacad"). '">Academic Admin</a></td>';
   else
       $html .= ' <td></td>';

   $html .= "</tr></table>";
   echo $html;
}

if(anyOfTheseRoles("SERVICES_ADMIN")) 
{
    echo "<h1> Services Admin </h1>";
    $html = "<table class=\"admin\">";
    $html .= '<tr>';
    $html .= '<td>
        <i class="fa fa-bus fa-2x"></i>
        <a class="clickable" href="'.site_url( 'adminservices/transport') . '">Manage Transport</a>
        </td>';
    $html .= '
    <td>
        <i class="fa fa-cutlery fa-2x"></i>
        <a class="clickable" href="'.site_url("adminservices/canteen").'">Manage Canteen Menu</a>
    </td>';
    $html .= '</tr>';
    echo $html ;
}

?>
