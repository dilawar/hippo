<?php
include_once FCPATH.'./system/autoload.php';


function pubmedToTable( ) : string
{
    $pubmedJSON = FCPATH . '/temp/pubmed.json';
    if( file_exists( $pubmedJSON ) )
    {
        $rssFromPubmed = json_decode(file_get_contents($pubmedJSON), true);
        $entries = $rssFromPubmed['entries'];
        $table = '<table class="info">';
        foreach( $entries as $i => $entry )
        {
            $index = $i + 1;
            $journal = $entry['tags'][0]['term'];
            $table .= '<tr>';
            $table .= "<td>$index</td>";
            $table .= '<td>' . $entry['author'] . '</td>';
            $table .= '<td>' . $entry['title'] . '</td>';
            $table .= '<td>' . $journal . '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        return $table;
    }
    else
        return '';
}

// Populate ncbs feed.
$ncbsList = FCPATH . '/temp/publications.json';
$bibs = json_decode( file_get_contents($ncbsList), true);

$bibYear = [];
foreach( $bibs as $i => $bib )
    $bibYear[ $bib['year'] ][] = $bib;

foreach( $bibYear as $year => $bibs )
{
    $table = '<table class="info">';
    $table .= "<caption> $year </caption>";
    foreach( $bibs as $i => $bib )
    {
        $row = "<td>".($i+1)."</td>";
        $row .= "<td>". $bib['author'] ."</td>";
        $row .= "<td>". $bib['title'] ."</td>";
        $table .= "<tr>$row</tr>";
    }
    $table .= "</table>";
    echo $table;
    echo "<hr />";
}

?>

