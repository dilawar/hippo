<?php

require_once FCPATH. 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;


function read_spreadsheet( string $filename )
{
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile( $filename );
    $reader->setReadDataOnly(true);
    $sheet = $reader->load($filename);
    $currsheet = $sheet->getActiveSheet();
    $data = $currsheet->toArray();

    // First row of data is header. They must be all small caps and no space.
    // Replace all spaces with _.
    $header = $data[0];
    foreach( $header as $i => $val )
        $header[$i] = str_replace( ' ', '_', strtolower($val));

    $data[0] = $header;
    return $data;
}


?>
