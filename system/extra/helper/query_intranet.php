<?php

function sendQuery( $query, $page = 1 )
{
    $url =  "https://intranet.ncbs.res.in/people?".$query."&page=$page";
    $content = file_get_contents( $url );
    return $content;
}


?>
