<?php

require_once BASEPATH . 'autoload.php';
require_once __DIR__ . '/snippets/pi_specialization.php';
echo userHTML();

$user = __get__($_POST, 'login', '');
$default = getUserInfo($user, true);
$buttonVal = 'Update';
if (!$default) {
    $userName = __get__($_POST, 'login', '');
    $default = getUserInfoFromLdap($userName);
    if (!$default) {
        echo printWarning(
            "Invalid username $userName. I did not find anyone named " .
            $_POST['login'] . ' on LDAP server'
        );
        redirect('admin');
    }

    $default['login'] = $_POST['login'];
    $buttonVal = 'Add New';
}

echo '<form method="post" action="' . site_url('admin/updateuser') . '">';
echo dbTableToHTMLTable(
    'logins',
    $default,
    'alternative_email,honorific,roles,status,title,eligible_for_aws,joined_on'
                . ',valid_until,laboffice,specialization,pi_or_host',
    $buttonVal
);
echo '</form>';

// Button for deleting user.
echo '<br/><br/>';
echo '<form action="' . site_url('admin/deleteuser/' . md5($user)) . '" method="post"> ';
echo '<button type="submit" name="response" value="Delete">Delete User!</button>';
echo '<input type="hidden" name="login" value="' . $user . '" />';
echo '</form>';

echo goBackToPageLink('admin', 'Go back');
