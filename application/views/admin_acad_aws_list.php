<script>
$( function() {
    $( "#accordion" ).accordion({
        collapsible: true
        , heightStyle: "fill"
        });
    });
</script>

<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$awses = $AWSES_CI;

// Create various maps from these AWSes.
$awsByDate = [];
foreach ($awses as $aws) {
    $awsByDate[$aws['date']][] = $aws;
}
?>



<table class="table">
<?php foreach ($awsByDate as $date => $awses): ?> 
    <tr>
        <td> <?= humanReadableDate($date) ?> </td>
        <?php foreach ($awses as $aws): ?>
        <td> <?= $aws['title'] ?> </td>
        <?php endforeach; ?>
    </tr>
<?php endforeach;?>
</table>


<?= goBackToPageLink("Go back") ?>
<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>

<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>

