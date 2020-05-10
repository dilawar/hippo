<?php

require_once BASEPATH . 'autoload.php';

// Show it only if accessed from intranet or user have logged in.
if (!(isIntranet() || isAuthenticated())) {
    echo printWarning('To access this page, either use Intranet or log-in first');
    echo closePage();
    exit;
}

echo '<strong>Multiple keywords can be separated by , </strong>';
echo '<form action="' . site_url('info/aws/search') . '" method="post">
    <input type="text" name="query" value="" >
    <button type="submit" name="response" value="Search">Search</button>
    </form>
    ';

echo printInfo('Total matches ' . count($awses));

foreach ($awses as $aws) {
    // Add user info to table.
    $aws['speaker'] = loginToHTML($aws['speaker'], true);
    $aws['date'] = humanReadableDate($aws['date']);

    echo "<div class='container'>";
    echo awsToHTMLLarge($aws, true);
    echo '</div>';
    echo '<hr/>';
}

echo goBackToPageLink('info/aws', 'Go back');
