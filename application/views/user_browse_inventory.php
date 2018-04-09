<?php
include_once 'header.php';
include_once 'database.php';
include_once 'methods.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles('USER' );
echo userHTML( );

$items = getTableEntries( 'inventory', 'common_name', "status='VALID'" );

// Add JS based real time search.
?>

<script type="text/javascript" charset="utf-8">
function filterTable( button ) {
    console.log( "here" );
    var input, filter, table, tr, td, i;
    table = document.getElementById( "searchable_by_js" );
    input = document.getElementById( "search_query" );
    filter = input.value.toUpperCase( );

    tr = table.getElementsByTagName("tr");

    // Loop through all table rows except 1 (which is th), and hide
    // those who don't match the search query
    for (i = 1; i < tr.length; i++)
    {
        var found = false;
        td = tr[i].getElementsByTagName("td");
        for( var j = 0; j < td.length; j++ )
        {
            if (td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
                found = true;
            }
        }

        if( ! found )
            tr[i].style.display = "none";
    }

}
</script>

<?php

if (count( $items ) < 1)
{
    echo printInfo( "No item found in inventory." );
}
else
{
    echo '<input type="text" id="search_query" onkeyup="filterTable()"
         placeholder="Type to search">';
    echo printNote( "Click on column name to sort the table." );
    $hide = 'id,status,last_modified_on,edited_by';
    echo ' <table id="searchable_by_js" class="info sortable">';
    echo arrayHeaderRow( $items[0], 'info sorttable', $hide );
    foreach( $items  as $item )
        echo arrayToRowHTML( $item, 'info sorttable', $hide );
    echo '</table>';
}

echo " <br /> ";
echo goBackToPageLink( "user.php", "Go back" );

?>
