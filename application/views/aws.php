<?php
require_once BASEPATH.'autoload.php';

$form = '<form method="post" action="aws">
        <input class="datepicker" type="text" name="date" value="' . $date . '" > 
        <button class="btn btn-primary" 
                type="submit" name="response" title="Show AWS" value="show">Show AWSs</button>
    </form>
    ';

$table = '<table class="table table-secondary"><tr>
    <td>' . $form .  '</td>
    <td><a href="'.site_url('info/aws/search'). '">Search AWS database</a></td>
    <td><a href="'.site_url('info/aws/roster'). '"> List of AWS speakers <br /> and upcoming AWSs</a>
    </td> 
    </tr></table>';

echo $table;
echo "<br />";

echo heading("Annual Work Seminars on " .  humanReadableDate($date), 2);

if (count($awses) < 1) {
    echo printInfo("I could not find any AWS in my database on this day");
    $holiday = getTableEntry('holidays', 'date', array( 'date' => $date ));
    if ($holiday) {
        echo alertUser("May be due to following");
        echo arrayToTableHTML($holiday, 'info');
    }

    echo printInfo("That's all I know!");
    echo "<br><br>";
} else {
    foreach ($awses as $aws) {
        $user = $aws[ 'speaker' ];
        $link = '<a class="card-link" href="'.site_url("user/downloadaws/".$aws['date']."/".$aws['speaker']).'"
            target="_blank">Download PDF</a>';
        $awstext = awsToHTMLLarge($aws, $with_picture = true, $links=[$link]);
        echo $awstext;
    }
}

echo closePage();
