<?php
require_once BASEPATH. 'autoload.php';
echo userHTML( );
$thisSem = getCurrentSemester( ) . ' ' . getCurrentYear( );
?>

<div class="row">
    <div class="col rounded bg-light text-left m-3 p-2">
        <a class="fa fa-book fa-2x " 
            href="<?=site_url('/user/courses')?>"> My Courses
        </a>
        <br />
        Register/deregister courses for <?=$thisSem?> semster.
    </div>
<?php if( __get__($cUserInfo, 'eligible_for_aws', 'NO' ) == 'YES'): ?>
    <div class="col rounded bg-light m-3 p-2 text-left">
        <a class="fa fa-2x fa-graduation-cap" 
            href="<?=site_url("/user/aws")?>"> My AWS</a> 
        <br />
        See your previous AWSs and update them. Check
        the details about upcoming AWS and provide preferred dates.
        <br />
        <a class="btn btn-secondary"
             href="<?=site_url("/user/update_supervisors") ?>">
            Update TCM Members/Supervisors
        </a>
    </div>
<?php else: ?>
    <div class="col rounded">
    </div>
<?php endif; ?>
</div>

<div class="row">
    <div class="col rounded bg-light text-left m-3 p-2">
        <a class=" fa fa-2x" 
            href="<?=site_url("/user/jc")?>">My Journal Clubs
        </a>
        <br />
        Subscribe/Unsubscribe from journal club. See upcoming presentation.
        Vote on presentation requests.
    </div>
    <div class="col rounded bg-light text-left m-3 p-2">
        <a class="fa fa-2x " 
            href="<?=site_url("/user/jc_presentation_requests")?>">
            My JC Presentation Requests
        </a>
        <br />
        Submit a journal paper and a preferred date to present it. You can submit
        one presentations requests for nay date. The community can vote on the
        presentation requests.
    </div>
</div>

<?php if(isJCAdmin( whoAmI())): ?>
    <div class="row">
        <div class="col rounded bg-light text-left m-3 p-2">
            <i class="fa fa-lock fa-2x"></i>
            <a class="" href="'. site_url("/user/jcadmin"). '">JC Admin</a> <br />
            Journal club admin</td>
        </div>
        <div class="col rounded">
        </div>
    </div>
<?php endif; ?>

<h1>Booking</h1>

<?php
// Count pending requests.
$user = whoAmI();
$reqs = getTableEntries( 'bookmyvenue_requests'
    , 'date'
    , "status='PENDING' AND created_by='$user' GROUP BY gid" 
);
$flag = '';
if( count( $reqs ) > 0)
    $flag = count( $reqs ) . " requests.";
?>

<div class="row">
<div class="col rounded bg-light text-left m-3 p-2">
     <a class=" fa fa-hand-pointer-o fa-2x" 
         href="<?=site_url("/user/book/venue")?>">
         Book for small event
     </a>
     <br />
     E.g. Labmeets,meeting,interview etc.. No email will be sent to Academic community.
    <br />
    <a class="btn btn-secondary fa fa-pencil-square-o" 
        href="<?=site_url("/user/show_private")?>">
        Manage My Private Events ('<?=$flag?>)
    </a> 
</div>
<div class="col rounded bg-light text-left m-3 p-2">
    <a class="fa fa-comments fa-2x " 
        href="<?=site_url("/user/register_talk")?>">
        Book for talks/seminar etc. (Academic Events)
    </a>
    <br />
    E.g. talk, seminar, public lectures, pre-synopsis/thesis seminar; essentially any event 
    for which academic community needs to be notified by email.
    <br />
    <a class="btn btn-secondary fa fa-pencil-square-o fa-1x" 
        href="<?=site_url("/user/show_public")?>">Manage my public events.
    </a>
</div>
</div>

<div class="p-2 my-10">
<h1>Lab management</h1>

<div class="text-left">
You can create equipments private to your lab. These equipments can be 
booked by your lab members.  This is not a replacement of 
common equipment booking system.</div>

<div class="row">
<div class="col rounded bg-light text-left m-3 p-2">
     <a class="fa fa-hand-archive fa-2x" 
     href="<?=site_url("/user/inventory_browse")?>">
        Browse Lab Inventory or Book Equipments</a>
</div>
<div class="col rounded bg-light text-left m-3 p-2">
     <a class="fa fa-flask fa-2x" 
         href="<?=site_url("/user/inventory_manage")?>"
        >Manage Lab Inventory</a>
</div>
</div>
</div>

<?php
$roles =  getRoles(whoAmI() ); 
?>

<?php if(anyOfTheseRoles("SERVICES_ADMIN")): ?>
    <div class=" p-2 my-2">
    <h1> Services Admin </h1>
    <div class="row">
        <div class="col rounded bg-light text-left m-3 p-2">
            <a class="fa fa-bus fa-2x" 
                href="<?=site_url( 'adminservices/transport')?>"> Manage Transport</a>
        </div>
        <div class="col rounded bg-light text-left m-3 p-2">
            <a class="fa fa-cutlery fa-2x"
            href="<?=site_url("adminservices/canteen")?>"> Manage Canteen Menu</a>
        </div>
    </div>
    </div>
<?php endif; ?>

<?php if(anyOfTheseRoles('ADMIN,BOOKMYVENUE_ADMIN,JOURNALCLUB_ADMIN,ACAD_ADMIN')): ?>
    <h1> <i class="fa fa-cogs"></i> Admin</h1>

    <div class="row">
    <?php if(in_array("ADMIN", $roles)): ?>
       <div class="col rounded bg-light text-left m-3 p-2"> 
            <a class="fa fa-lock fa-2x" 
                href="<?=site_url("/admin")?>"> Admin</a>
        </div>
    <?php endif; ?>

    <?php if(in_array("BOOKMYVENUE_ADMIN", $roles)): ?>
       <div class="col rounded bg-light text-left m-3 p-2">
           <a class="fa fa-calendar-plus-o fa-2x" 
                href="<?=site_url("/adminbmv")?>"> Book My Venue Admin</a>
       </div>
    <?php else: ?>
       <div class="col rounded bg-light text-left m-3 p-2"></div>
    <?php endif; ?>

    <?php if( in_array( "ACAD_ADMIN", $roles)): ?>
       <div class="col rounded bg-light text-left m-3 p-2">
            <a class="fa fa-graduation-cap fa-2x" 
                href="<?=site_url("/adminacad")?>"> Academic Admin</a>
       </div>
    <?php else: ?>
       <td></td>
    <?php endif; ?>
    </div>
<?php endif; ?>
