<?php
include_once FCPATH . 'system/autoload.php';
mustHaveAllOfTheseRoles( Array( 'ADMIN' ) );
echo userHTML( );

echo '<h3>Quick Add</h3>';

$table = "<table class='editable_transport' id='editable_transport'>";
$table .= ' 
    <tr>
    <td>Vehicle</td> 
    <td> <input type="text" name="vehicle" id="" value="" /> </td>
    </tr><tr>
    <td>Trip Pickup Point</td> 
    <td> <input type="text" name="pickup_point" id="" value="" /> </td>
    </tr><tr>
    <td>Trip Drop Point</td> 
    <td> <input type="text" name="drop_point" id="" value="" /> </td>
    </tr><tr>
    <td>Trip Start Times</td> 
    <td> <input type="text" name="trip_start_times" id="" value="" 
            placeholder="HH:MM e.g. 09:00,10:00,13:30" /> </td>
    </tr><tr>
    <td>Duration (mins)</td> 
    <td> <input type="text" name="trip_duration" id="" value="" /> </td>
    </tr><tr>
    <td>Days</td> 
    <td> <input type="text" name="days" id="" placeholder="Sun,Mon etc" value="" /> </td>
    </tr><tr>
    <td>URL/Route</td> 
    <td> <input type="url" name="url" id="" value="" /> </td>
    </tr>';
$table .= "</table>";

$form = '<form action="' . site_url("admin/transport/quickadd") . '" method="post">';
$form .= $table;
$form .= '<button class="submit" type="submit">Submit</button> ';
$form .= '</form>';

echo "<div> $form </div>";
echo ' <br /> <br />';

// DISPLAY TRANSPORT.
$groupBy = [];
$table = getTableEntries('transport', 'pickup_point,trip_start_time,day', "status='VALID'"
);
foreach( $table as $r )
{
    $key = implode('||', [$r['vehicle'], $r['pickup_point'], $r['drop_point'],$r['trip_start_time']]);
    $groupBy[$key][]=$r;
}

// Construct a new table for easy display.
$schedule = [];
foreach( $groupBy as $key => $tables )
{
    $arr = explode( '||', $key);

    // This is new key. We don't use trip_start time to group anymore.
    $newKey = implode( '||', array_slice($arr, 0, 3));

    $entry = ['days' => implode(','
        , array_map(function($x){ return $x['day'];}, $tables)
        )];
    $entry['vehicle'] = $arr[0];
    $entry['pickup_point'] = $arr[1];
    $entry['drop_point'] = $arr[2];
    $entry['trip_start_time'] = $arr[3];
    $schedule[$newKey][] = $entry;
}
ksort($schedule);

echo '<h1> Transport details </h1>';

$hide = 'vehicle_no,score,edited_by,vehicle,pickup_point,' 
        . 'drop_point,status,last_modified_on';
$class = 'info sortable exportable';

foreach( $schedule as $key => $tables )
{
    $arr = explode('||', $key);
    echo "<h3>" . vsprintf( "%s from %s to %s", $arr) . '</h3>';
    echo arraysToCombinedTableHTML($tables, 'info', 'vehicle,pickup_point,drop_point');
}


echo goBackToPageLink( "admin/home", "Go back" );

?>

<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>
