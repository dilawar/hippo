<?php

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( 'USER' ) );

$googleMapAPIKey = getConfigValue( 'GOOGLE_MAP_API_KEY' );

echo userHTML( );
$user = whoAmI( );

?>

<script src="https://maps.googleapis.com/maps/api/js?sensor=false&libraries=places&key=<?php echo $googleMapAPIKey ?>"></script>
</head>
<body>
    <script>
        function init() {
            var input = document.getElementById('apartments_address');
            var autocomplete = new google.maps.places.Autocomplete(input);
        }
        google.maps.event.addDomListener(window, 'load', init);
    </script>
</body>
</html>

<?php
// All alerts.
$allAlerts = getTableEntries( 'alerts' );
$count = array( );
foreach( $allAlerts as $alert )
    $count[ $alert[ 'value' ] ] = 1 + __get__( $count, $alert['value'], 0 );

$apartmentTypes = array( 'SHARE', '1BHK', '2BHK', '3BHK', 'STUDIO', 'PALACE' );
$apartmentSelect = arrayToSelectList( 'value', $apartmentTypes );


// Create alerts.
echo " <h2>My Email Alerts</h2> ";
echo printInfo( "You will recieve email whenever following types of listings are added by others." );

$where = "login='$user' AND on_table='apartments' AND  on_field='type'";
$myAlerts = getTableEntries( 'alerts', 'login', $where );

echo '<table><tr>';
if( count( $myAlerts ) > 0 )
{
    foreach( $myAlerts as $alert )
    {
        echo '<td>';
        echo ' <form action="user_tolet_action.php" method="post" > ';
        echo dbTableToHTMLTable( 'alerts', $alert, '', 'Delete Alert', 'on_field,on_table' );
        echo '</form>';
        echo '</td>';
    }
}
echo '</tr></table>';

echo '<h2>Create new alerts</h2>';
// New alert.
echo ' <form action="user_tolet_action.php" method="post" > ';
echo '<table> <tr> ';
echo '<td>Apartment Type : ' . $apartmentSelect . '</td>';
echo ' <input type="hidden" name="login" value="' . $user . '" />';
echo ' <input type="hidden" name="on_table" value="apartments" />';
echo ' <input type="hidden" name="on_field" value="type" />';
echo '<td> <button name="response" value="New Alert">Add Alert </button> </td>';
echo ' </tr></table>';
echo '</form>';


// Show all apartments.
echo ' <br /> <br />';
echo ' <h2>My TO-LET entries </h2> ';
$action = 'Add new listing';

$myApartments = getTableEntries( 'apartments', 'type'
            , "status='AVAILABLE' AND created_by='$user' "
        );

echo '<div style="font-size:small;">';
echo '<table border="0">';
foreach( $myApartments as $apt )
{
    echo '<tr>';
    echo ' <form method="post" action=""> ';
    echo '<td>' . arrayToTableHTML( $apt, 'info', ''
                    , 'status,last_modified_on,created_by'  ) . '</td>';
    echo ' <td>
            <button name="response" value="Update" > Edit </button> </td> ';
    echo '<input type="hidden" name="id" value="' . $apt[ 'id' ] . '" />';
    echo ' </form> ';
    echo '</tr>';
}

echo '</table>';
echo '</div>';


$default = array(  'created_by' => $_SESSION[ 'user' ]
        , 'created_on' => dbDateTime( 'now' )
        , 'open_vacancies' => 1
    );

// If edit button is pressed.
if( 'Update' == __get__( $_POST, 'response', '' ) )
{
    $default = getTableEntry( 'apartments', 'id', $_POST );
    $default[ 'last_modified_on' ] = dbDateTime( 'now' );
    $action = 'Update listing';
}

// Fill a new form.
echo '<div style="font-size:small;">';
echo ' <form method="post" action="user_tolet_action.php">';

// Create an editable entry.
$editable = 'type,available_from,open_vacancies,address,description,owner_contact,rent,advance';
if( 'Update listing' == $action )
    $editable .= ',status';

echo " <h2>$action</h2> ";

echo dbTableToHTMLTable( 'apartments', $default , $editable , $action);
echo '</form>';
echo '</div>';

echo goBackToPageLink( "user.php", "Go back" );

?>
