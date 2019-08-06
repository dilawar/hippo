<?php

include_once BASEPATH.'autoload.php';

function test_methods( )
{
    $pat = constructRepeatPattern( "Mon", "All", 2 );
    print_r( $pat );
    print_r( repeatPatToDays( $pat, dbDate('today') ) );

    // $pat = constructRepeatPattern( "Tuesday,Wednes", "second,fourth", 3 );
    // print_r( $pat );
    // print_r( repeatPatToDays( $pat, dbDate('today') ) );
}

?>
