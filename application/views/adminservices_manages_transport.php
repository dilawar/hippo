<?php
include_once FCPATH . 'system/autoload.php';
mustHaveAllOfTheseRoles( Array( 'ADMIN' ) );
echo userHTML( );

echo '<h1>Quick Add/Update</h1>';

$default = ['vehicle'=>'', 'pickup_point'=>'', 'drop_point'=>''
    , 'trip_start_time'=>'', 'url' => ''];

$action='quickadd';
if( isset($_POST) )
{
    $action = 'quickupdate';
    $default = array_merge($default, $_POST);
}

$table = "<table class='editable_transport' id='editable_transport'>";
$table .= ' 
    <tr>
    <td>Vehicle</td> 
    <td> <input type="text" name="vehicle" value="'. $default['vehicle']. '" /> </td>
    </tr><tr>
    <td>Pickup Point</td> 
    <td> <input type="text" name="pickup_point" value="' . $default['pickup_point']. '" /> </td>
    </tr><tr>
    <td>Drop Point</td> 
    <td> <input type="text" name="drop_point" value="'. $default['drop_point'].'" /> </td>
    </tr><tr>
    <td>Start Times</td> 
    <td> <input type="text" name="trip_start_times" 
            value="' . $default['trip_start_time'] 
            . '" placeholder="HH:MM e.g. 09:00,10:00,13:30" /> </td>
    </tr><tr>
    <td>Duration (mins)</td> 
    <td> <input type="text" name="trip_duration" id="" value="" /> </td>
    </tr><tr>
    <td>Days</td> 
    <td> <input type="text" name="days" id="" placeholder="Sun,Mon etc" value="" /> </td>
    </tr><tr>
    <td>URL/Route</td> 
    <td> <input type="url" name="url" id="" value="'.$default['url'].'" /> </td>
    </tr>';
$table .= "</table>";

$form = '<form action="' . site_url("adminservices/transport/$action") . '" method="post">';
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
    $entry['url'] = $tables[0]['url'];
    $schedule[$newKey][] = $entry;
}
ksort($schedule);

echo '<h1> Transport details </h1>';

$hide = 'vehicle_no,score,edited_by,vehicle,pickup_point,' 
        . 'drop_point,status,last_modified_on';
$class = 'info sortable exportable';

foreach( $schedule as $key => $table )
{
    $arr = explode('||', $key);
    $vehicle = $arr[0];
    $pickup_point = $arr[1];
    $drop_point = $arr[2];

    echo "<h3>" . vsprintf( "%s from %s to %s", $arr) . '</h3>';

    // We need a form to delete them as well.
    echo "<table class='info'>";
    foreach( $table as $row )
    {
        $trip_start_time = $row['trip_start_time'];
        $url = $row['url'];

        echo '<tr>';
        echo arrayToRowHTML($row, 'info', $hide, true, false);
        echo '<form action="'.site_url('adminservices/transport/quickdelete').'" method="post">';
        echo "<input type='hidden' name='vehicle' value='$vehicle' />";
        echo "<input type='hidden' name='pickup_point' value='$pickup_point' />";
        echo "<input type='hidden' name='drop_point' value='$drop_point' />";
        echo '<td> <button type="submit" onclick="AreYouSure()">Delete</button> </td>';
        echo '</form>';
        echo '<form action="#" method="post">';
        echo "<input type='hidden' name='vehicle' value='$vehicle' />";
        echo "<input type='hidden' name='pickup_point' value='$pickup_point' />";
        echo "<input type='hidden' name='drop_point' value='$drop_point' />";
        echo "<input type='hidden' name='trip_start_time' value='$trip_start_time' />";
        echo "<input type='hidden' name='url' value='$url' />";
        echo '<td> <button type="submit">Update</button> </td>';
        echo '</form>';
        echo '</tr>';
    }
    echo "</table>";
}


echo goBackToPageLink( "$controller/home", "Go back" );

?>

<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>
