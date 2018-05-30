<?php

require FCPATH. 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function read_spreadsheet( string $filename )
{
    $reader = \PhpOffice\PhpSpreadSheet\IOFactory::createReaderForFile( $filename );
    $reader->setReadDataOnly(true);
    $sheet = $reader->load($filename);
    return $sheet;

}


?>
