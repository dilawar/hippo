<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'USER' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

$jcs = getJournalClubs( );
echo '<h1>Table of All Journal Clubs</h1>';
$table = '<table class="info">';

$table .= '<tr>';
foreach( $jcs as $i => $jc )
{
    $jcInfo = getJCInfo( $jc );
    $buttonVal = 'Subscribe';
    if( isSubscribedToJC( $_SESSION['user'], $jc['id'] ) )
        $buttonVal = 'Unsubscribe';

    $table .= '<td>' . $jc['id'];
    $table .= ' (' . $jcInfo[ 'title' ] . ')';
    $table .=  '<form action="user_manages_jc_action.php"
        method="post" accept-charset="utf-8">';
    $table .= "<button name=\"response\"
        value=\"$buttonVal\">$buttonVal</button>";
    $table .= '<input type="hidden" name="jc_id" value="' . $jc['id'] . '" />';
    $table .= '<input type="hidden" name="login" value="'
                .  $_SESSION['user'] . '" />';
    $table .= '</form>';
    $table .= '</td>';

    if( ($i + 1 ) % 4 == 0 )
        $table .= '</tr><tr>';
}
$table .= '</tr>';
$table .= '</table>';
echo $table;


// Get all upcoming JCs in my JC.
$mySubs = getUserJCs( $login = $_SESSION[ 'user' ] );
foreach( $mySubs as $i => $mySub )
{
    echo "<h1>Upcoming presentations for " . $mySub[ 'jc_id' ] . "</h1>";
    $jcID = $mySub['jc_id' ];
    $upcomings = getUpcomingJCPresentations( $jcID );
    sortByKey( $upcomings, 'date' );

    echo '<table>';
    foreach( $upcomings as $i => $upcoming )
    {
        if( ! $upcoming[ 'presenter' ] )
            continue;

        echo '<tr>';
        echo '<td>';
        echo arrayToVerticalTableHTML( $upcoming, 'info', '', 'id,status' );
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}


// Check if I have any upcoming presentation.
$myPresentations = getUpcomingPresentationsOfUser( whoAmI( ) );
if( count( $myPresentations ) > 0 )
{
    echo '<h1>Your upcoming presentation(s)</h1>';

    foreach( $myPresentations as $upcoming )
    {
        if( $upcoming[ 'acknowledged' ] == 'NO' )
        {
            echo printWarning(
                "You need to 'Acknowledge' the presentation before you
                can edit this entry. "
            );
        }
        // If it is MINE then make it editable.
        echo ' <form action="user_manages_jc_update_presentation.php"
            method="post" accept-charset="utf-8">';
        $action = 'Edit';
        if( $upcoming[ 'acknowledged' ] == 'NO' )
            $action = 'Acknowledge';
        echo dbTableToHTMLTable( 'jc_presentations', $upcoming, '', $action );
        echo '</form>';
    }
}
else
{
    echo printInfo( "You have no upcoming JC presentation. <i class=\"fa fa-frown\"></i>" );
}


echo '<h1>JC presentation requests in your JCs</h1>';

$today = dbDate( 'today' );

$allreqs = array( );
foreach( $mySubs as $sub )
{
    $jcID = $sub[ 'jc_id' ];
    $allreqs = array_merge( $allreqs
        , getTableEntries( 'jc_requests', 'date' , "status='VALID' AND date >= '$today' AND jc_id='$jcID'")
        );
}

if( count( $allreqs ) > 0 )
{
    echo printInfo(
        "Following presentation requests have been made. If you like any paper to
        be presented, please vote for it. Voting is anonymous and only seen by
        JC coordinators.
        "
    );

    echo '<table>';
    foreach( $allreqs as $req )
    {
        echo '<tr>';
        echo '<td>';
        echo arrayToVerticalTableHTML( $req, 'info', '', 'id,status' );

        $voteId = "jc_requests." . $req['id'];
        $action = 'Add My Vote';
        if( getMyVote( $voteId ) )
            $action = 'Remove My Vote';

        echo '</td>';
        echo ' <form action="user_manages_jc_update_presentation.php" method="post" accept-charset="utf-8">';
        echo ' <input type="hidden" name="id" value="' . $voteId . '" />';
        echo ' <input type="hidden" name="voter" value="' . whoAmI( ) . '" />';
        echo "<td> <button name='response' value='$action'>$action</button></td>";
        echo '</form>';
        echo '</tr>';
    }
    echo '<table>';
}
else
{
    echo printInfo( "No presentation request has been made yet
        or the existing ones have been cancelled by users."
    );
}

echo goBackToPageLink( 'user.php', 'Go Back' );

?>
