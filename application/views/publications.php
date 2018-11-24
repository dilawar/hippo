:?php
include_once FCPATH.'./system/autoload.php';

// This RSS url is generated from PubMed.
$rssURL = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/erss.cgi?rss_guid=1RMSezfb-YLyPFjXsWq0j6qB5a_k2bOqXvlArT8q7FY1XXx1FO";
$url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/erss.cgi?rss_guid=1NSu_CQNBizum_oQNyvEnfQmlhOTxJQa5H5sRESYexRAOfuYAI";
$url = "http://www.ncbs.res.in/publications/export/bibtex";
echo "getting content from $url";
$content = file_get_contents( $url );
echo $content;
?>

