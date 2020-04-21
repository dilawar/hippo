<?php

require_once BASEPATH.'autoload.php';

// Send user to another page to adit it. This is a 'view'.
// Update the user entry
echo printInfo(
    "Admin is allowed to reformat the entry. 
    That is why only abstract can be modified here."
);

$aws = getTableEntry('upcoming_aws', "speaker,date", $_POST);
if (! $aws) {
    echo alertUser("Nothing to update.");
} else {
    echo '<form method="post" action="'.site_url('adminacad/update_aws_entry').'">';
    echo dbTableToHTMLTable('upcoming_aws', $aws, 'abstract,title,is_presynopsis_seminar,supervisor_1', 'Update AWS Entry');
    echo '</form>';
}


echo goBackToPageLink('adminacad/upcoming_aws');
