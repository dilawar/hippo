<?php
// Let user download it.
$pdfFileUrl=$_GET['filename'];
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . 
    urlencode(basename($pdfFileUrl)));   
header("Content-Type: application/octet-stream");
header("Content-Transfer-Encoding: Binary" );
header("Content-Type: application/force-download");
header("Content-Description: File Transfer");            
header("Content-Length: " . filesize($pdfFileUrl));
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
flush(); // this doesn't really matter.
readfile( $pdfFileUrl );
?>
