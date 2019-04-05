<?php
include_once FCPATH . 'system/autoload.php';
mustHaveAllOfTheseRoles( Array( 'ADMIN' ) );
echo userHTML( );

echo '<h3>Manage transport</h3>';


$default = [];
$action = 'Add';
if( __get__($_POST, 'id', 0) > 0)
{
    $action = 'Update';
    $default = getTableEntry( 'transport', 'id', $_POST);
}

echo '<form method="post" action="'.site_url("admin/manage_transport/$action").'">';
echo dbTableToHTMLTable( 'transport'
        , $default
        , 'vehicle,vehicle_no,pickup_point,drop_point,day,trip_start_time,trip_end_time,comment'
        , $action
    );
$groupBy = [];
$table = getTableEntries('transport', 'day,trip_start_time', "status='VALID'");
foreach( $table as $row )
{
    $key = implode('||', [$row['vehicle'], $row['pickup_point'], $row['drop_point']]);
    $groupBy[$key][]=$row;
}


// Group these entries by vehicle, pickup_point and drop_points.

echo '<h1> Transport details </h1>';


$hide = 'vehicle_no,score,edited_by,vehicle,pickup_point,drop_point,status,last_modified_on';
$class = 'info sortable exportable';
foreach( $groupBy as $key => $tables )
{
    $arr = explode('||', $key);
    $vehicle = $arr[0];
    $from = $arr[1];
    $to = $arr[2];

    $route ="$vehicle-$from-$to";
    echo "<h2>$vehicle from $from to $to </h2>";

    $html = "<table id='$route' class='$class'>";
    $html .= arrayToTHRow( $tables[0], $class, $hide );
    foreach( $tables as $table )
    {
        $html .= '<tr>';
        $html .= arrayToRowHTML( $table, $class, $hide, false, false);
        $html .= "<td>";
        $html .= "<form action='" .site_url('admin/delete_transport/'.$table['id']). "' method='post'>";
        $html .= "<button type='submit'>Delete</button>";
        $html .= "</form>";
        $html .= '</td><td>';
        $html .= "<form action='#' method='post'>";
        $html .= '<input type="hidden" name="id" id="" value="'.$table['id'].'" />';
        $html .= "<button type='submit'>Edit</button>";
        $html .= '</form>';
        $html .= "</td>";
        $html .= '</tr>';
    }
    $html .= "</table>";
    echo $html;
}

echo goBackToPageLink( "admin/home", "Go back" );

?>

<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>
