<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';
include_once 'check_access_permissions.php';

mustHaveAllOfTheseRoles( array( 'AWS_ADMIN' ) );

$speakerPiMap = array( );
// Collect all faculty
$faculty = getFaculty( );
$facultyByEmail = array( );
foreach( $faculty as $fac )
{
    $facultyByEmail[ $fac[ 'email' ] ] = $fac;
    $speakerPiMap[ $fac['email' ] ] = array( );
}


$facEmails = array_keys( $facultyByEmail );
$logins = array( );
foreach( getAWSSpeakers( ) as $login )
{
    $piOrHost = getPIOrHost( $login[ 'login' ] );
    $logins[] = $login[ 'login' ];
    $speakerPiMap[ $piOrHost ][] = $login;
}
ksort( $speakerPiMap );

echo userHTML( );

?>

<script type="text/javascript" charset="utf-8">
// Autocomplete pi.
$( function() {
    // These emails must not be key value array.
    var emails = <?php echo json_encode( $facEmails ); ?>;
    var logins = <?php echo json_encode( $logins ); ?>;
    $( "#pi_or_host" ).autocomplete( { source : emails });
    $( "#pi_or_host" ).attr( "placeholder", "type email of supervisor" );
    $( "#login" ).autocomplete( { source : logins });
    $( "#login" ).attr( "placeholder", "Speaker login" );
});
</script>


<?php

// Auto update PI_OR_HOST.
// If pi_or_host is not found, use ldap info.
foreach( $speakerPiMap as $pi => $logins )
{
    if( (! $pi) or $pi == 'UNSPECIFIED' or strpos( $pi, '@' ) === false )
    {
        foreach( $logins as $login )
        {
            if( ! $login['login'] )
                continue;

            $ldap = getUserInfoFromLdap( $login[ 'login' ] );
            $email = getEmailByName( $ldap[ 'laboffice' ] );
            if( $email )
            {
                // Update the table with laboffice from intranet.
                $res = updateTable( 'logins', 'login', 'pi_or_host'
                    , array( 'login' => $login[ 'login' ], 'pi_or_host' => $email )
                );

                if( $res )
                {
                    echo printInfo(
                        "Successfully updated PI_OR_HOST for " . $login[ 'login' ] .  " to $email"
                    );
                }
            }
        }
    }
}


/**
    * @name User interface.
    * @{ */
/**  @} */

echo '
    <form action="admin_acad_aws_speakers_action.php"
        method="post" accept-charset="utf-8">
    <table border="0">
    <tr>
        <td><input type="text" name="login" id="login" placeholder="Speaker id"/></td>
        <td><input type="text" name="pi_or_host" id="pi_or_host" placeholder="supervisor email"/></td>
        <td><button type="submit" name="response" value="update_pi_or_host">Update Speaker PI/HOST</button></td>
    </tr>
    </table>
    </form>
    ';


echo ' <h2>Table of active speakers</h2> ';

$index = 0;
foreach( $speakerPiMap as $pi => $speakers )
{
    if( count( $speakers ) < 1 )
        continue;

    echo "<h3>AWS Speaker list for " . $pi . "</h3>";
    $table = "<table class=\"info\">";
    $table .="<tr>";

    $i = 0;
    foreach( $speakers as $login )
    {
        if( ! $login )
            continue;

        $speaker = getLoginInfo( $login['login'] );

        $i ++;
        $index ++;
        $table .= "<td> $index: " . arrayToName( $speaker ) . "<br />
            <tt>(" .  $speaker[ 'email' ] . ")</tt></td>";
        if( $i % 4 == 0 )
            $table .= "<tr></tr>";
    }
    $table .= "</tr>";
    $table .= "</table>";
    echo $table;
}


echo goBackToPageLink( "admin_acad.php", "Go back" );


?>
