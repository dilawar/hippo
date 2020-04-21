<?php

require_once BASEPATH.'autoload.php';
echo userHTML();

echo ' <h1>You are creating preferences for your upcoming AWS schedule.</h1>';

echo alertUser("If chosen date is not a monday, I will pick first monday next to chosen date while scheduling.");

if (! __get__($_POST, 'created_on', null)) {
    $_POST[ 'created_on' ] = dbDateTime('now');
}

if (! __get__($_POST, 'speaker', null)) {
    $_POST[ 'speaker' ] = $_SESSION[ 'user' ];
}

// Check if this user already has a preference.
$_POST[ 'status' ] = 'VALID';
$prefs = getTableEntry('aws_scheduling_request', 'speaker,status', $_POST);
if (! $prefs) {
    $prefs = array( );
}

$prefs = array_merge($prefs, $_POST);

echo '<form method="post" action="' .site_url('user/aws/schedulingrequest/submit'). '">';
echo dbTableToHTMLTable(
    'aws_scheduling_request',
    $prefs,
    'first_preference,second_preference,reason',
    'submit'
);
echo '<input type="hidden" name="created_on" value="' . dbDateTime('now') . '">';
echo '</form>';

echo goBackToPageLink('user/aws', 'Go back');
