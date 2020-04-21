<?php
require_once BASEPATH . 'autoload.php';
$today = dbDate('today');
$default = array( 'date' => $today );

if (__get__($_GET, 'date', '')) {
    $default[ 'date' ] = $_GET[  'date' ];
}

$form = '
<form method="get" action="">
    <table class="table">
        <tr>
            <td><input class="datepicker" type="text" name="date" value="' .
                $default[ 'date' ] . '" ></td>
            <td><button class="btn btn-primary" type="submit" name="response"
                    title="Show events on this day"
                    value="show">Show Events This Day Onwards</button></td>
        </tr>
    </table>
</form>'
;

echo "$form <br /> <br />";

$whichDay = $default[ 'date' ];
$eventTalks = getTableEntries(
    'events',
    'date,start_time',
    "date>='$whichDay'
        AND status='VALID' AND external_id LIKE 'talks%'"
);

// Only if a event has an external_id then push it into 'talks'
if (count($eventTalks) < 1) {
    echo printInfo(
        "I could not find any public event. Is there 
            nothing going on the campus? Or the World has finally ended?"
    );
} else {
    $talkHtml = '';
    foreach ($eventTalks as $event) {
        $talkId = explode('.', $event[ 'external_id'])[1];
        $talk = getTableEntry('talks', 'id', array( 'id' => $talkId ));
        if ($talk) {
            if (strtotime($event['date']) < strtotime($whichDay) + 28*24*3600) {
                $talkHtml .= '<div class="card bg-white py-1 my-2">';
                // This is the where-when line at the top.
                $talkHtml .= talkToHTMLLarge($talk, $with_picture = true, $header=whereWhenHTML($event));
                $talkHtml .= "<br>";

                // Link to pdf file.
                $talkHtml .= '<div class="my-1 small">';
                $talkHtml .= '<a target="_blank" class="mx-2" href="'
                        . site_url("user/downloadtalk/".$default['date']."/$talkId") . '">
                        <i class="fa fa-download ">PDF</i></a>';
                $talkHtml .= '<a target="_blank" class="mx-2" href="'
                        . site_url("user/downloadtalkical/".$default['date']."/$talkId") . '">
                        <i class="fa fa-calendar ">iCal</i></a>';
                $talkHtml .= '</div>';
                $talkHtml .= '</div>';
            } else {
                $talkHtml .=  '<p class="wherewhen">' . whereWhenHTML($event) . '</p>';
                $talkHtml .= talkToEventTitle($talk);
            }
        }
    }
    echo $talkHtml;
    echo '<br>';
}

echo closePage();
