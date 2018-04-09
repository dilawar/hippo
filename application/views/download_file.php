<?php

// Let user download it.
$fileUrl= $_GET['filename'];
if( ! file_exists( $fileUrl) )
    $fileUrl= __DIR__ . '/data/' . $_GET['filename'];

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . 
    urlencode(basename($fileUrl)));   
header("Content-Type: application/octet-stream");
header("Content-Transfer-Encoding: Binary" );
header("Content-Type: application/force-download");
header("Content-Description: File Transfer");            
header("Content-Length: " . filesize($fileUrl));
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
flush(); // this doesn't really matter.
readfile( $fileUrl );
?>
