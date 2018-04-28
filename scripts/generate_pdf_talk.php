<?php

include_once 'database.php';
include_once 'tohtml.php';

// This script may also be called by command line by the email bot. To make sure 
// $_GET works whether we call it from command line or browser.
if( isset($argv) )
    parse_str( implode( '&' , array_slice( $argv, 1 )), $_GET );

function get_suitable_font_size( $desc )
{
    $nchar = strlen( $desc );
    if( $nchar > 1900 )
        return '14pt';
    return '12pt';
}

function eventToTex( $event, $talk = null )
{
    // First sanities the html before it can be converted to pdf.
    foreach( $event as $key => $value )
    {
        // See this 
        // http://stackoverflow.com/questions/9870974/replace-nbsp-characters-that-are-hidden-in-text
        $value = htmlentities( $value, null, 'utf-8' );
        $value = str_replace( '&nbsp;', '', $value );
        $value = preg_replace( '/\s+/', ' ', $value );
        $value = html_entity_decode( trim( $value ) );
        $event[ $key ] = $value;
    }

    // Crate date and plate.
    $where = venueSummary( $event[ 'venue' ] );
    $when = humanReadableDate( $event['date'] ) . ' | ' . humanReadableTime( $event[ 'start_time' ] );

    $title = $event[ 'title' ];
    $desc = $event[ 'description' ];


    // Prepare speaker image.
    $imagefile = getSpeakerPicturePath( $talk[ 'speaker_id' ] );
    if( ! file_exists( $imagefile ) )
        $imagefile = nullPicPath( );

    // Add user image.
    $imagefile = getThumbnail( $imagefile );
    $speakerImg = '\includegraphics[width=4cm]{' . $imagefile . '}';

    $speaker = '';
    if( $talk )
    {
        $title = $talk['title'];
        $desc = fixHTML( $talk[ 'description' ] );

        // Get speaker if id is valid. Else use the name of speaker.
        if( intval( $talk[ 'speaker_id' ] ) > 0 )
            $speakerHTML = speakerIdToHTML( $talk['speaker_id' ] );
        else 
            $speakerHTML = speakerToHTML( getSpeakerByName( $talk[ 'speaker' ]));

        $speaker = html2Tex( $speakerHTML );
    }


    // Header
    $head = '';

    // Put institute of host in header as well
    $isInstem = false;
    $inst = emailInstitute( $talk[ 'host' ], "latex" );

    if( strpos( strtolower( $inst ), 'institute for stem cell' ) !== false )
        $isInstem = true;

    $logo = '';
    if( $isInstem )
        $logo = '\includegraphics[height=1.5cm]{' . __DIR__  . '/data/inStem_logo.png}';
    else
        $logo = '\includegraphics[height=1.5cm]{' . __DIR__ . '/data/ncbs_logo.png}';


    // Logo etc.
    $date = '\faClockO \,' .  $when;
    $place = ' \faHome \,' . $where;

    $head .= '\begin{tikzpicture}[remember picture,overlay
        , every node/.style={rectangle, node distance=5mm,inner sep=0mm} ]';

    $head .= '\node[below=of current page.north west,anchor=west,shift=(-45:1cm)] (logo) { ' . $logo . '};';

    $head .= '\node[below=of current page.north east,anchor=south east,shift=(-135:1cm)] (tclass) 
            {\LARGE \textsc{\textbf{' . $talk['class'] . '}}};';
    $head .= '\node[below=of tclass.south east, anchor=east] (date) {\small \textsc{' . $date . '}};';
    $head .= '\node[below=of date.south east, anchor=south east] (place) {\small \textsc{' . $place . '}};';
    $head .= '\node[below=of place] (place1) {};';
    $head .= '\node[fit=(current page.north east) (current page.north west) (place1)
                    , fill=red, opacity=0.3, rectangle, inner sep=1mm] (fit_node) {};';
    $head .= '\end{tikzpicture}';
    $head .= '\par \vspace{5mm} ';

    $head .= '\begin{tikzpicture}[ ]';
    $head .= '\node[inner sep=0, inner sep=0pt] (image) {' . $speakerImg . '};';
    $head .= '\node[right=of image.north east, anchor=north west, text width=0.6\linewidth] (title) { ' .  '{\Large ' . $title . '} };';
    $head .= '\node[below=of title,text width=0.6\linewidth,yshift=10mm] (author) { ' .  '{\small ' . $speaker . '} };';
    $head .= '\end{tikzpicture}';
    $head .= '\par'; // So tikzpicture don't overlap.

    $tex = array( $head );

    $tex[] = '\par';
    file_put_contents( '/tmp/desc.html', $desc );
    $texDesc = html2Tex( $desc ); 
    if( strlen(trim($texDesc)) > 10 )
        $desc = $texDesc;

    $extra = '';
    if( $talk )
    {
        $extra .= '\newline \vspace{1.5cm} \vfill ';
        $extra .= "\begin{tabular}{ll}\n";
        $extra .= '{\bf Host} & ' . html2Tex( loginToHTML($talk[ 'host' ]) ) . '\\\\';
        if( $talk[ 'coordinator' ] )
            $extra .= '{\bf Coordinator} & ' . html2Tex( loginToHTML($talk[ 'coordinator' ])) . '\\\\';
        $extra .= '\end{tabular}';
    }

    $tex[] = '\begin{tcolorbox}[colframe=black!0,colback=red!0
        , fit to height=18 cm, fit basedim=14pt
        ]' . $desc . $extra . '\end{tcolorbox}';

    $texText = implode( "\n", $tex );
    return $texText;

} // Function ends.


///////////////////////////////////////////////////////////////////////////////
// Intialize pdf template.
//////////////////////////////////////////////////////////////////////////////
// Institute 
$tex = array( 
    "\documentclass[12pt]{article}"
    , "\usepackage[margin=25mm,top=3cm,a4paper]{geometry}"
    , "\usepackage[]{graphicx}"
    , "\usepackage[]{wrapfig}"
    , "\usepackage[]{grffile}"
    , "\usepackage[]{amsmath,amssymb}"
    , "\usepackage[colorlinks=true]{hyperref}"
    , "\usepackage[]{color}"
    , "\usepackage{tikz}"
    , "\usepackage{fontawesome}"
    , '\linespread{1.15}'
    , '\pagenumbering{gobble}'
    , '\usetikzlibrary{fit,calc,positioning,arrows,backgrounds}'
    , '\usepackage[sfdefault,light]{FiraSans}'
    , '\usepackage{tcolorbox}'          // Fit text in one page.
    , '\tcbuselibrary{fitting}'
    , '\begin{document}'
    );


$ids = array( );
$date = null;
if( array_key_exists( 'id', $_GET ) )
{
    array_push( $ids, $_GET[ 'id' ] );
}
else if( array_key_exists( 'date', $_GET ) )
{
    // Get all ids on this day.
    $date = $_GET[ 'date' ];
    echo "Found date $date";
    echo printInfo( "Events on $date" );
    
    // Not all public events but only talks.
    $entries = getPublicEventsOnThisDay( $date );
    foreach( $entries as $entry )
    {
        $eid = explode( '.', $entry[ 'external_id' ] );

        // Only from table talks.
        if( $eid[0] == 'talks' && intval( $eid[1] ) > 0 )
            array_push( $ids, $eid[1] );
    }
}
else
{
    echo alertUser( 'Invalid request.' );
    exit;
}

// Prepare TEX document.
$outfile = 'EVENTS';
if( $date )
    $outfile .= '_' . $date;

foreach( $ids as $id )
{
    $talk = getTableEntry( 'talks', 'id', array( 'id' => $id ) );
    $event = getEventsOfTalkId( $id );
    $tex[] = eventToTex( $event, $talk );
    $tex[] = '\pagebreak';
    $outfile .= "_$id";
}

$tex[] = '\end{document}';
$TeX = implode( "\n", $tex );

// Generate PDF now.
$outdir = __DIR__ . "/data";
$pdfFile = $outdir . '/' . $outfile . ".pdf";
$texFile = sys_get_temp_dir() . '/' . $outfile . ".tex";

if( file_exists( $pdfFile ) )
    unlink( $pdfFile );

file_put_contents( $texFile,  $TeX );
$cmd = __DIR__ . "/tex2pdf.sh $texFile";
if( file_exists( $texFile ) )
    $res = `$cmd`;

if( file_exists( $pdfFile ) )
{
    // Remove tex file.
    // unlink( $texFile );

    // Download only if called from browser.
    if( ! isset( $argv ) )
        goToPage( 'download_pdf.php?filename=' .$pdfFile, 0 );
}
else
{
    echo printWarning( "Failed to genered pdf document <br>
        This is usually due to hidden special characters 
        in your text. You need to cleanupyour entry." );

    echo printWarning( "Error message <small>This is only for diagnostic
        purpose. Show it to someone who is good with LaTeX </small>" );
    echo "Command <pre> $cmd </pre>";
    echo "<pre> $res </pre>";
}


echo "<br/>";
echo closePage( );

?>
