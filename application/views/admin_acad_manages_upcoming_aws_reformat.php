<?php

require_once BASEPATH.'autoload.php';

// Send user to another page to adit it. This is a 'view'.
// Update the user entry
echo printInfo( "Admin is allowed to reformat the entry. 
    That is why only abstract can be modified here." 
    );

$aws = getTableEntry( 'upcoming_aws', "speaker,date", $_POST );
if( ! $aws )
    echo alertUser( "Nothing to update." );
else
{
    echo '<form method="post" action="'.site_url('admin/acad_action/update_aws_entry').'">';
    echo dbTableToHTMLTable( 'upcoming_aws', $aws
        , 'abstract,title,is_presynopsis_seminar', 'Update AWS Entry' );
    echo '</form>';
}


echo goBackToPageLink( 'admin/acad/manages_upcoming_aws');


?>
