<html>
<head>
    <title><?php if(isset($title)){ echo $title;}else{echo "Hippo";} ?></title>
</head>
<body>

<?php if(isset($header))
    require_once "$header";
else
    echo "";

if( isset( $_SESSION['success'] ) )
{
    echo '<div class="alert alert-success">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
        <strong>' . $_SESSION['success'] . '</strong>
        </div>';
    unset( $_SESSION['success'] );
}
else if( isset( $_SESSION['info'] ) )
{
    echo '<div class="alert alert-info">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
    <strong>' . $_SESSION['info'] . '</strong></div>';
    unset( $_SESSION['info'] );
}

?>
    <div id="contents"><?= $contents ?></div>
    <div id="header"><?php if(isset($footer)){echo $footer;}else{echo "";} ?></div>
</body>
</html>
