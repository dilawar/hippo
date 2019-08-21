<?php
include_once BASEPATH . '/extra/methods.php';
$calURL =  getConfigValue( 'CALENDAR_URL' );

echo "<footer>
    <a href=\"$calURL\"> <i class=\"fa fa-calendar fa-3x\"></i> NCBS Google Calendar</a>
    <div class=\"bottom\">
    <a target=\"_blank\" href=\"https://github.com/dilawar/Hippo\" target='_blank'
        >GNU-GPL (v3)</a> (c) 
        <a target=\"_blank\" href=\"https://github.com/dilawar\">Dilawar Singh 2016-18</a>
    <br />
    Logo credit: <a href=\"https://github.com/nunojesus\" target=\"_blank\">Nuno Jesus</a>
    </div>
    </footer>";
?>

