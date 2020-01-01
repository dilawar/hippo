<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

echo "<h2>Send notifications to Hippo App</h2>";

$topics = getConfigValue('ALLOWED_BOARD_TAGS');
$topics = explode(',', "hippo,$topics");
sort($topics);
$selectHTML = arrayToSelectList('topic', $topics);

$form = "
<form method='post' action='". site_url('admin/sendfcm') . "' accept-charset='utf-8'>
    <table class='table'>
        <tr>
            <td>Select a topic</td>
            <td>$selectHTML</td>
        </tr>
        <tr>
            <td>Title</td>
            <td>
                <input class='input' name='title' value='' />
            </td>
        </tr>
        <tr>
            <td>Message Body</td>
            <td>
                <textarea name='body' rows='5' cols='50' value=''></textarea>
            </td>
        </tr>
        <tr>
            <td> </td>
            <td><button class='btn btn-primary'>Send</button></td>
        </tr>
    </table>
</form>
";

echo "<div>$form</div>";


echo goBackToPageLink("admin");

?>

