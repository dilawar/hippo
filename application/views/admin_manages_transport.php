<?php
include_once FCPATH . 'system/autoload.php';
mustHaveAllOfTheseRoles( Array( 'ADMIN' ) );
echo userHTML( );

echo '<h3>Manage transport</h3>';

$default = array( );
echo '<form method="post" action="'.site_url("admin/add_transport").'">';
echo dbTableToHTMLTable( 'transport'
        , $default
        , 'vehicle,vehicle_no,pickup_point,drop_point,day,trip_start_time,trip_end_time,comment'
        , 'Add'
    );

$tables = getTableEntries('transport', 'day', "status='VALID'");
if($tables)
{
    echo '<h1> Transport details </h1>';

    $hide = 'id,vehicle_no,score,edited_by';
    $class = 'info sortable exportable';
    $html = "<table id='transport' class='$class'>";
    $html .= arrayToTHRow( $tables[0], $class, $hide );
    foreach( $tables as $table )
    {
        $html .= '<tr>';
        $html .= arrayToRowHTML( $table, $class, $hide, false, false);
        $html .= "<td>";
        $html .= "<form action='" .site_url('admin/delete_transport/'.$table['id']). "' method='post'>";
        $html .= "<button type='submit'>Delete</button>";
        $html .= "</form>";
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