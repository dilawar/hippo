<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAllOfTheseRoles(['USER']);

echo '<h3>Submit a request for AWS</h3>';

// User add a request for editing the AWS.
if ('edit' == $_POST['response']) {
    $id = $_POST['id'];
    $aws = getAwsById($id);
    echo dbTableToHTMLTable(
        'annual_work_seminars',
        $aws,
        ['title', 'abstract']
    );
}
