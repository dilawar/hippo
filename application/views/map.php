
<?php

include_once "header.php";
include_once 'tohtml.php';
include_once 'methods.php';
?>

<script src="js/jquery.imagemapster.min.js"> </script>


<?php
$imageUrl = __DIR__ . "/data/ncbs_map_route_map_to_lecture_halls.jpeg";

echo "<h1> NCSB Map</h1>";

if( file_exists( $imageUrl ) )
{
    echo  "Selected location <p id=\"location_info\"></p>";

    $html = '<img width="800px"  height="auto" id="ncbsmap_img" 
            src="' . dataURI( $imageUrl, 'image/jpg' ) .  '" usemap="#ncbsmap" >';
    echo $html;
    echo '
    <map name="ncbsmap">
      <area shape="rect" coords="100,0,110,10" href="#" data-key="tl" />
      <area shape="rect" coords="100,800,110,810" href="#" data-key="bl" />
      <area shape="rect" coords="1100,0,1110,10" href="#" data-key="tr" />
      <area shape="rect" coords="1100,800,1110,810" href="#" data-key="bl" />
      <area shape="circle" coords="600,530,30" href="#" data-key="RAS" />
    </map>
    ';
    echo "
        <script type=\"text/javascript\" charset=\"utf-8\">
        $('#ncbsmap_img').mapster( { 
            mapKey : 'data-key' 
            , toolTip : true
        })
            .mapster( 'set', true, 'bl,br,tl,tr,RAS' )
        </script>
    ";
}
else
{
    echo printWarning( "No map is found." );
}

echo closePage( );

?>

<!--
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.3/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.0.3/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.3/dist/leaflet.css"
  integrity="sha512-07I2e+7D8p6he1SIM+1twR5TIrhUQn9+I6yjqD53JQjFiMf8EtC93ty0/5vJTZGF8aAocvHYNEDJajGdNx1IsQ=="
  crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.0.3/dist/leaflet.js"
  integrity="sha512-A7vV8IFfih/D732iSSKi20u/ooOfj/AGehOKq0f4vLT1Zr2Y+RX7C+w8A1gaSasGtRUZpF/NZgzSAu4/Gc41Lg=="
  crossorigin=""></script>

<h2> Testing area </h2>

<div id="ncbsmap"></div>

<script type="text/javascript" charset="utf-8">
    
var map = L.map('ncbsmap').setView([51.505, -0.09], 13);

L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

L.marker([51.5, -0.09]).addTo(ncbsmap)
    .bindPopup('A pretty CSS3 popup.<br> Easily customizable.')
    .openPopup();

</script>

-->
