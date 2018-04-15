<html>
<head>
    <title><?php if(isset($title)){ echo $title;}else{echo "Hippo";} ?></title>
</head>
<body>
    <div id="header">
<?php if(isset($header))
{
    require_once "$header";
}else
{
    echo "";
} 
?>
    </div>
    <div id="contents"><?= $contents ?></div>
    <div id="header"><?php if(isset($footer)){echo $footer;}else{echo "";} ?></div>
</body>
</html>
