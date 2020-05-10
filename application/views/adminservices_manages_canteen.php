<?php
include_once FCPATH . 'system/autoload.php';
echo userHTML();
?>

<div class="card m-1 p=1">
    <div class="h3 card-header">Quick Add/Update Menu</div>
        <form action="<?=site_url('adminservices/canteen/quickadd'); ?>" method="post">
        <div class="card-body">
            <table class="editable_canteen_menu_quick">
            <tr>
                <td>Canteen Name</td>
                <td>
                    <input type="text" name="canteen_name" id="" value="" />
                </td>
            </tr>
            <tr>
                <td>Day</td>
                <td>
                    <input type="text" name="day" placeholder="Tue,Wed etc." value="" />
                </td>
            </tr>
            <tr>
                <td>Which Meal?</td>
                <td> <?=$cMealHtml; ?></td>
            </tr>
            <tr>
                <td>Available From</td>
                <td>
                    <input class="timepicker" name="available_from" id="" value="" />
                </td>
            </tr>
            <tr>
                <td>Available Upto</td>
                <td>
                    <input class="timepicker" name="available_upto" id="" value="" />
                </td>
            </tr>
            <tr>
                <td>Menu</td>
                <td>
                    <textarea name="menu" rows="4" cols="60"
                        placeholder="name1=price1,name2=price2"
                        required
                        > item1=2;item2=3;
                    </textarea>
                </td>
            </tr>
            </table>
        </div>
        <button class="btn btn-primary pull-right">Submit</button>
        </form>
    </div>
</div>

<?php
$hide = 'id,description,days_csv,modified_by,modified_on,status,popularity';
?>

<?php foreach ($cItemGroupedByDay as $day => $canteen): ?>
    <?php $display = ($day == $today) ? 'block' : 'none'; ?>

    <div class="card my-2 bg-light">
    <div class="card-header">
        Menu for <?=$day; ?> 
        <button class="btn large btn-link" onClick='toggleCardByID(this, "menuFor<?=$day; ?>")'>SHOW</button>
    </div>
    <div class="card-body" id="menuFor<?=$day; ?>" style="display:<?=$display; ?>">
    <?php foreach ($canteen as $cname => $items): ?>
        <div class="card">
            <div class="card-body">
            <table class="info exportable sortable" id="menu_<?=$day . '_' . $cname; ?>">
            <div class="h5"><?=$cname; ?></div>
            <?= arrayToTHRow($items[0], 'info', $hide); ?>
            <?php foreach ($items as $item): ?>
            <tr>
                <?php $id = $item['id']; ?>
                <?=arrayToRowHTML($item, 'info', $hide, '', false); ?>
                <td>
                    <form action="#" method="post">
                        <input type="hidden" name="id" value="<?=$item['id']; ?>"></input>
                        <button class="btn btn-primary small">EDIT</button>
                    </form>
                    <form action="<?=site_url("adminservices/canteen/delete/$id"); ?>" method="post">
                        <button class="btn btn-danger small">DELETE</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </table>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    </div>
<?php endforeach; ?>

<?=goBackToPageLink("$controller/home", 'Go back'); ?>

<script src="<?=base_url(); ?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url(); ?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url(); ?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>

<script>
function toggleCardByID(button, divID) 
{
    let x = document.getElementById(divID);
    if (x.style.display == "none")
    {
        button.innerHTML = "HIDE";
        x.style.display = "block";
    } 
    else 
    {
        button.innerHTML = "SHOW";
        x.style.display = "none";
    }
}
</script>
