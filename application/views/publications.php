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

$entries = getTableEntries( 'publications', 'date' );
echo "Total publications found " . count( $entries );

$bibs = [];
foreach( $entries as $entry )
    $bibs[date('Y', strtotime($entry['date']))][] = $entry;

// Higher year first.
krsort($bibs);

foreach( $bibs as $year => $bibs )
{
    $table = '<table class="show_info">';
    foreach( $bibs as $i => $bib )
    {
        $row = "<td>".($i+1)."</td>";
        $row .= "<td>". $bib['title'] ;
        $title = $bib['title'];
        $titleSha = hash( 'sha512', $title);
        $authors = array_map( function($e) { return $e['author']; }
            , getTableEntries( 'publication_authors', 'author', "publication_title_sha='$titleSha'")
        );

        $publisher = __get__( $bib, 'journal', __get__($bib, 'publisher', 'Unknown'));
        $row .= "<br /> <small>" . $publisher . "</small>" ;
        $row .= "<br/> <strong> <small>". implode(',', $authors) . "</small> </strong>";
        $row .= "</td>";
        $table .= "<tr>$row</tr>";
    }
    $table .= "</table>";
    echo "<h3>Publications in $year</h3>";
    echo $table;
    echo "<hr />";
}

?>

