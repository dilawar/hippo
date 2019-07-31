<!-- bootstrap -->
<script type="text/javascript" href="<?= base_url() ?>/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="<?= base_url() ?>/node_modules/bootstrap/dist/css/bootstrap.min.css"></link>

<!--  REQUIRED -->
<script src="<?= base_url() ?>/node_modules/jquery/dist/jquery.js"></script>
<script src="<?= base_url() ?>/node_modules/jquery-ui-dist/jquery-ui.min.js"></script>

<script src="<?= base_url() ?>/node_modules/jquery-timepicker/jquery.timepicker.js"></script>

<!-- hippo css -->
<link href="<?= base_url() ?>/assests/css/hippo.css" rel="stylesheet" type="text/css" />

<link  href="<?= base_url() ?>/node_modules/jquery-timepicker/jquery.timepicker.css" 
    rel="stylesheet" type="text/css" />
<link  href="<?= base_url() ?>/node_modules/jquery-ui-dist/jquery-ui.min.css" 
    rel="stylesheet" type="text/css" />
<link  href="<?= base_url() ?>/node_modules/jquery-ui-bootstrap/jquery.ui.theme.css" 
    rel="stylesheet" type="text/css" />

<!-- Multi-dates pickers -->
<link  href="<?= base_url() ?>/node_modules/jquery-ui-multidatespicker/jquery-ui.multidatespicker.css" rel="stylesheet" type="text/css" />
<script src="<?= base_url() ?>/node_modules/jquery-ui-multidatespicker/jquery-ui.multidatespicker.js"></script>

<!-- new vue -->
<script src="<?= base_url() ?>/node_modules/vue/dist/vue.js"></script>
 
<!-- Font awesome -->
<link rel="stylesheet" href="<?= base_url() ?>/node_modules/font-awesome/css/font-awesome.css"/>
<!-- font awesome animation -->
<link rel="stylesheet" href="<?= base_url() ?>/node_modules/font-awesome-animation/dist/font-awesome-animation.min.css"/>


<!-- sort table. -->
<script src="<?= base_url() ?>/node_modules/sorttable/sorttable.js"></script>
<script type="text/javascript" charset="utf-8">
    $(".sortable").sortable( );
</script>

<script>
$( function() {
    $( "input.datepicker" ).datepicker( { dateFormat: "yy-mm-dd" } );
  } );
</script>

<script type="text/javascript" charset="utf-8">
for( i = new Date( ).getFullYear( ) + 1; i > 2000; i-- )
{
    $( "#yearpicker" ).append( $('<option />').val(i).html(i));
}
</script>

<script>
$( function() {
    $( "input.datetimepicker" ).datepicker( { dateFormat: "yy-mm-dd" } );
  } );
</script>

<script>
$(function(){
    $('input.timepicker').timepicker( {
            minTime : '8am'
            , scrollDefault : 'now'
            , timeFormat : 'H:mm', interval : '15'
            , startTime : '8am'
            , dynamic : false
    });
});
</script>

<!-- Make sure date is in yyyy-dd-mm format e.g. 2016-11-31 etc. -->
<script>
$( function() {
    var date = new Date();
    $( "input.multidatespicker" ).multiDatesPicker( {
        dateFormat : "yy-m-d"
    });
} );

</script>


<script src="<?= base_url() ?>/node_modules/tinymce/tinymce.min.js"></script>

<!-- confirm on delete -->
<script type="text/javascript" charset="utf-8">
function AreYouSure( button, value = 'delete' )
{
    var x = confirm( "ALERT! Destructive operation! Continue?" );
    if( x )
        button.setAttribute( 'value', value );
    else
        button.setAttribute( 'value', 'DO_NOTHING' );
    return x;
}
</script>

<!-- toggle  show hide -->
<script type="text/javascript" charset="utf-8">
function ToggleShowHide( button )
{
    var div = document.getElementById( "show_hide" );
    if (div.style.display !== 'none') {
        div.style.display = 'none';
        button.innerHTML = 'Show form';
    }
    else {
        div.style.display = 'block';
        button.innerHTML = 'Hide form';
    }
}
</script>

<!-- toggle  show hide -->
<script type="text/javascript" charset="utf-8">
function toggleShowHide( button, eid, value = 'enrollment' )
{
    var div = document.getElementById( eid );
    if (div.style.display !== 'none') {
        div.style.display = 'none';
        button.innerHTML = 'Show ' + value;
    }
    else {
        div.style.display = 'inline';
        button.innerHTML = 'Hide ' + value;
    }
};
</script>

<script type="text/javascript">
  (function() {
    var blinks = document.getElementsByTagName('blink');
    var visibility = 'hidden';
    window.setInterval(function() {
      for (var i = blinks.length - 1; i >= 0; i--) {
        blinks[i].style.visibility = visibility;
      }
      visibility = (visibility === 'visible') ? 'hidden' : 'visible'; }, 1000);
  })();
</script>

<!-- Fade in -->
<script type="text/javascript" charset="utf-8">
$(document).ready(function(){
    $('div#fadein').fadeIn(3000).delay(3000).fadeOut(2000);
});
</script>

<div style="font-size:small" class="header">
<h1 class="title"><a href="<?= site_url( 'welcome' ) ?>" >
<?php echo img( 'data/HippoLogoCropped.png', false, array('height'=>'70px')); ?> Hippo</a></h1>
<table class="public_links">
    <tr>
    <td><a class="bright" href="<?= site_url('info/booking') ?>" target="hippo_popup">Bookings</a> </td>
    <td><a class="bright" href="<?= site_url('info/aws') ?>" target="hippo_popup">AWSs</a></td>
    <td><a class="bright" href="<?= site_url('info/talks') ?>" target="hippo_popup">Talks</a></td>
    <td><a class="bright" href="<?= site_url('info/jc') ?>" target="hippo_popup">JCs</a> </td>
    <td><a class="bright" href="<?= site_url('info/statistics') ?>" target="hippo_popup" >Statistics </a> </td>
    <td><a class="bright" href="<?= site_url('info/courses') ?>" target="hippo_popup" >Courses</a></td>
    <td><a class="bright" href="<?= getConfigValue( 'CALENDAR_URL' ) ?>" target="hippo_popup">Calendar</a> </td>
    <td><a class="bright" href="https://ncbs-hippo.readthedocs.io/en/latest/" target="hippo_popup">Docs</a> </td>
    </tr>
</table>
</div>
