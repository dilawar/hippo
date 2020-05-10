<?php

require BASEPATH . 'autoload.php';
echo userHTML();

$jcs = getJournalClubs();

echo '<h1>Journal Clubs</h1>';
$table = '<table class="info">';
$table .= '<tr>';
foreach ($jcs as $i => $jc) {
    $jcInfo = getJCInfo($jc);
    $buttonVal = 'Subscribe';
    if (isSubscribedToJC(whoAmI(), $jc['id'])) {
        $buttonVal = 'Unsubscribe';
    }

    $table .= '<td>' . $jc['id'];
    $table .= ' (' . $jcInfo['title'] . ')';
    $table .= '<form action="' . site_url("user/jc_action/$buttonVal") . '" method="post" >';
    $table .= '<button name="response" value="' . $buttonVal . '">' . $buttonVal . '</button>';
    $table .= '<input type="hidden" name="jc_id" value="' . $jc['id'] . '" />';
    $table .= '<input type="hidden" name="login" value="' . whoAmI() . '"/>';
    $table .= '</form>';
    $table .= '</td>';

    if (0 == ($i + 1) % 4) {
        $table .= '</tr><tr>';
    }
}
$table .= '</tr>';
$table .= '</table>';

echo $table;

echo '<h1>Upcoming JC presentations.</h1>';

// Get all upcoming JCs in my JC.
$mySubs = getUserJCs($login = whoAmI());

echo '<table class="show_info"><tr>';
foreach ($mySubs as $i => $mySub) {
    $jcID = $mySub['jc_id'];
    $upcomings = getUpcomingJCPresentations($jcID);
    sortByKey($upcomings, 'date');

    foreach ($upcomings as $i => $upcoming) {
        if (!$upcoming['presenter']) {
            continue;
        }

        echo '<td>';
        echo arrayToVerticalTableHTML($upcoming, 'info', '', 'id,status');
        echo '</td>';

        if (0 == ($i + 1) % 3) {
            echo '</tr><tr>';
        }
    }
}
echo '</tr></table>';

// Check if I have any upcoming presentation.
$myPresentations = getUpcomingPresentationsOfUser(whoAmI());
if (count($myPresentations) > 0) {
    echo '<h1>Your upcoming presentation(s)</h1>';
    foreach ($myPresentations as $upcoming) {
        if ('NO' == $upcoming['acknowledged']) {
            echo printWarning(
                "You need to 'Acknowledge' the presentation before you
                can edit this entry. "
            );
        }
        // If it is MINE then make it editable.
        echo ' <form action="' . site_url('user/jc_update_presentation') . '" method="post" >';
        $action = 'Edit';

        if ('NO' == $upcoming['acknowledged']) {
            $action = 'Acknowledge';
        }
        echo dbTableToHTMLTable('jc_presentations', $upcoming, '', $action);
        echo '</form>';
    }
} else {
    echo '<br />';
    echo printInfo(
        'No JC presentation has been assigned for you. If you have something 
        cool to present, raise a <a class="clickable" 
        href="' . site_url('user/jc_presentation_requests') . '">presentation request</a>.'
    );
}

echo '<h1>Presentation requests in your JCs</h1>';

$today = dbDate('today');

$allreqs = [];
foreach ($mySubs as $sub) {
    $jcID = $sub['jc_id'];
    $allreqs = array_merge(
        $allreqs,
        getTableEntries('jc_requests', 'date', "status='VALID' AND date >= '$today' AND jc_id='$jcID'")
    );
}

if (count($allreqs) > 0) {
    echo printInfo(
        'Following presentation requests are available along with preferred presentation date.
        If you like this paper, vote for it. Voting is anonymous and can only be seen by
        JC coordinators. Number of votes will be visible to presenter.
        '
    );

    echo '<table>';
    foreach ($allreqs as $req) {
        echo '<tr>';
        echo '<td>';
        echo arrayToVerticalTableHTML($req, 'info', '', 'id,status');

        $voteId = 'jc_requests.' . $req['id'];
        $action = 'Add My Vote';
        if (getMyVote($voteId)) {
            $action = 'Remove My Vote';
        }

        echo '</td>';
        echo ' <form action="' . site_url('user/jc/update_presentation') . '" method="post">';
        echo ' <input type="hidden" name="id" value="' . $voteId . '" />';
        echo ' <input type="hidden" name="voter" value="' . whoAmI() . '" />';
        echo "<td> <button name='response' value='$action'>$action</button></td>";
        echo '</form>';
        echo '</tr>';
    }
    echo '<table>';
} else {
    echo printInfo('Its very quiet in here <i class="fa fa-frown-o fa-2x"></i>.');
}

echo goBackToPageLink('user/home', 'Go Back');
