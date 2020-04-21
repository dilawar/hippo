<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$ref = 'adminbmv';
if (isset($controller)) {
    $ref = $controller;
}

$end_date = dbDate('today');
$start_date = dbDate('-3 month');

// User form to select dates.
$form = '
    <div class="important">
    <form action="" method="post" accept-charset="utf-8">
    <table border="0">
        <caption>Select date range to see the events</caption>
        <tr>
            <th>Start Date</th> <th>End date</th>
        </tr><tr>
            <td><input class="datepicker" name="start_date" id="" value="'.$start_date .'" /></td>
            <td><input class="datepicker" name="end_date" id="" value="'.$end_date .'" /></td>
            <td> <button type="submit">Browse</button> </td>
        </tr>
    </table>
    </form>
    </div>
    ';

echo $form;

if (isset($_POST)) {
    $end_date   = __get__($_POST, 'end_date', dbDate('today'));
    $start_date = __get__($_POST, 'start_date', dbDate('-3 month'));
}


echo "<h2>Events between " .humanReadableDate($start_date) ." and "
    . humanReadableDate($end_date) . "</h2>";

//$allevents = getTableEntries( 'events', 'date'
//    , "date > '$start_date' AND date < '$end_date' AND external_id LIKE 'talks.%'
//        AND status='VALID'"
//    );

// $talkIds = getTalkIDs( $start_date, $end_date );
$talks = getTalksWithEvent($start_date, $end_date);

$hide = 'gid,eid,external_id,description,status,calendar_event_id,is_public_event'
    . ',calendar_id,url,last_modified_on,id,speaker_id';

$table = '<table class="exportable info sortable">';
$table .= arrayToTHRow($talks[0], '', $hide);
foreach ($talks as $i => $talk) {
    $table .= arrayToRowHTML($talk, '', $hide);
}
$table .= '</table>';

echo $table;


echo goBackToPageLink("$ref/home");

?>
<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>
