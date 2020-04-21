<?php

require_once BASEPATH.'autoload.php';

echo userHTML();
$response = __get__($_POST, 'response', '');

if ($response == 'edit') {
    echo alertUser(
        "Here you can change the hosts, coordinator, title and 
            description of the talk.",
        false
    );

    $id = $_POST[ 'id' ];
    $talk = getTableEntry('talks', 'id', $_POST);

    echo '<form method="post" action="'.site_url("user/manage_talks_action").'">';
    echo dbTableToHTMLTable('talks', $talk, 'class,coordinator,host,host_extra,title,description', 'Update');
    echo '</form>';
} elseif ($response == 'submit') {
    $res = updateTable(
        'talks',
        'id',
        'class,host,host_extra,coordinator,title,description',
        $_POST
    );

    if ($res) {
        echo printInfo('Successfully updated entry');
        // Now update the related event as wel.
        $event = getEventsOfTalkId($_POST[ 'id' ]);
        $tableName = 'events';
        if (! $event) {
            $event = getBookingRequestOfTalkId($_POST[ 'id' ]);
            $tableName = 'bookmyvenue_requests';
        }

        if ($event) {
            $res = updateTable(
                $tableName,
                'external_id',
                'title,description',
                array( 'external_id' => "talks." . $_POST[ 'id' ]
                                , 'title' => talkToEventTitle($_POST)
                                , 'description' => $_POST[ 'description' ]
                            )
            );

            if ($res) {
                echo printInfo("Successfully updated associtated event");
            }
        }
        echo goToPage('user/manage_talk');
    } else {
        echo printWarning("Failed to update the talk ");
    }
} else {
    echo printInfo("Unknown operation " . $_POST['response'] . '.');
}
    
echo goBackToPageLink('user/show_public', "Go Home");
