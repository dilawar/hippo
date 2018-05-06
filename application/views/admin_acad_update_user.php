<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

$faculty = array( );
foreach( getFaculty( ) as $fac )
    $faculty[ ] = $fac[ 'email'] ;
?>

<script type='text/javascript' charset='utf-8'>
$(function() {
    var data = <?php echo json_encode( $faculty ); ?>;
    $( "#logins_pi_or_host" ).autocomplete( { source : data } );
    $( "#logins_pi_or_host" ).attr( "placeholder", "placeholder" );
})
</script>

<?php

echo "<p>Make sure to double check '<tt>PI OR HOST</tt>' and '<tt>JOINED ON</tt>' date.</p>";

if(! __get__($_POST, 'login'))
{
    echo printWarning( "You didn't select anyone. Going back ... " );
    redirect( 'adminacad/home' );
}

$default = getUserInfo( $_POST['login'] );
if( ! $default )
{
    echo printWarning( "Invalid username. I did not find anyone named " 
        .  $_POST[ 'login' ] . " on LDAP server" );
    echo goBackToPageLink( 'admin_acad.php', 'Go back' );
    exit;
}

echo '<form method="post" action="'.site_url("adminacad/update_user").'">';
echo dbTableToHTMLTable( 'logins', $default
    , 'status,title,eligible_for_aws,joined_on,pi_or_host'
    , 'submit'
    );
echo '</form>';

echo goBackToPageLink( 'adminacad/home', 'Go back' );
?>
