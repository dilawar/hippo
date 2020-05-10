<!DOCTYPE html>
<html>
<meta charset="utf-8">
<head>
    <title><?php if (isset($title)) {
    echo $title;
} else {
    echo 'Hippo';
} ?></title>
</head>

<body>
<?php
if (isset($header)) {
    require_once "$header";
} else {
    echo '';
}

$cats = explode(',', 'success,info,error,warning,failure,primary,primary,secondary,light,dark');
foreach ($cats as $x) {
    if (isset($_SESSION[$x])) {
        echo '<div class="alert alert-' . $x . '">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
            <strong>' . $_SESSION[$x] . '</strong>
            </div>';
        unset($_SESSION[$x]);
    }
}

$symbDelete = ' <i class="fa fa-trash "></i> ';
?>

    <div id="contents"><?= $contents; ?></div>
    <div id="div_background_image"></div>
    <div id="footer">
        <?php if (isset($footer)) {
    echo $footer;
} else {
    echo '';
} ?>
    </div>
</body>

</html>
