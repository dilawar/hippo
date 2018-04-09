<?php

// Collect all faculty
$faculty = getFaculty( );
$facultyByEmail = array( );
foreach( $faculty as $fac )
    $facultyByEmail[ $fac[ 'email' ] ] = $fac;
$facEmails = array_keys( $facultyByEmail );

$specialization = array( );
foreach( getAllSpecialization( ) as $spec )
    $specialization[$spec[ 'specialization' ] ] = 1;
$specialization = array_keys( $specialization );


?>

<script type="text/javascript" charset="utf-8">
// Autocomplete pi.
$( function() {
    // These emails must not be key value array.
    var emails = <?php echo json_encode( $facEmails ); ?>;
    var specialization = <?php echo json_encode( $specialization ); ?>;
    $( "#logins_pi_or_host" ).autocomplete( { source : emails });
    $( "#logins_pi_or_host" ).attr( "placeholder", "type email of supervisor" );
    $( "#logins_specialization" ).autocomplete( { source : specialization });
    $( "#logins_specialization" ).attr( "placeholder", "Your specialization" );
});
</script>
