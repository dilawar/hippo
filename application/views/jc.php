<?php

require BASEPATH . 'autoload.php';

echo '
<div class="container">
<form method="post" action="' . site_url('info/jc') . '">
    <input class="datepicker" type="text" name="date" value="' . $date . '" >
    <button class="btn btn-primary" name="response" 
            title="Show JCs on this day." value="show"
        > Show All JCs</button>
    </form>
</div> 
<br />
';

$whichDay = $date;

if (count($jcs) < 1) {
    echo alertUser("This is embarrassing. I could not find any JC scheduled. That's all I know!", false);
    echo '<br />';
} else {
    // Display details of JCs which are withing this week. Otherwise just show
    // the summary.
    echo heading('Upcoming presentations in Journal Clubs', 2);

    foreach ($jcs as $jc) {
        if (strtotime($jc['date']) < strtotime($whichDay) + 7 * 24 * 3600) {
            echo jcToHTML($jc);
        } else {
            echo jcToHTML($jc, true);
        }

        echo horizontalLine();
    }
}

echo closePage();
