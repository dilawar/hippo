<?php

require_once __DIR__.'/display_content.php';
require_once __DIR__.'/logger.php' ;
require_once __DIR__.'/html2text.php';
require_once __DIR__.'/helper/imap.php';
require_once __DIR__.'/ldap.php';
require_once __DIR__.'/mail.php';
require_once __DIR__.'/jc.php';

global $symbClock;

// Error code when uploading images.
$phpFileUploadErrors = array(
    0 => 'There is no error, the file uploaded with success',
    1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
    2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
    3 => 'The uploaded file was only partially uploaded',
    4 => 'No file was uploaded',
    6 => 'Missing a temporary folder',
    7 => 'Failed to write file to disk.',
    8 => 'A PHP extension stopped the file upload.',
);

function getUserIpAddr()
{
    $ip = 'NA';
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function whoAmI( )
{
    // Return the login name.
    $me = 'HIPPO';
    if( isset( $_SESSION) )
        $me = __get__($_SESSION, 'WHOAMI', $me);
    return explode('@', $me)[0];
}

/**
    * @brief Find valid email in text.
    *
    * @param $text
    *
    * @return 
 */
function extract_emails_from( $text )
{
    preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $text, $matches);
    $res =  implode( ',', $matches[0] );
    return $res;
}


function authenticateUser(string $ldap, string $pass )
{
    if(true) //ldapAlive( 'ldap.ncbs.res.in'))
    {
        $auth = authenticateUsingLDAP( $ldap, $pass );
    }
    else
    {
        // Try login using IMAP.
        flashMessage("LDAP is not alive. Using EMAIL services ...");
        $auth = authenticateUsingIMAP( $ldap, $pass );
    }
    return $auth;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Find difference between two days in months.
    *
    * @Param $dateA
    * @Param $dateB
    *
    * @Returns  Fractional value. Decimal part is computed by assuming 30 days
    * month.
 */
/* ----------------------------------------------------------------------------*/
function dateDiffInMonths( $dateA, $dateB ) : float
{
    $dateA = new DateTime( dbDate( $dateA ) );
    $dateB = new DateTime( dbDate( $dateB ) );
    $interval = $dateA->diff($dateB);
    $nMonths = $interval->y * 12 + $interval->m + $interval->d /30.0;
    return $nMonths;
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Return login id if email is given. This is  a helper function.
    *
    * @Param $text
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getLoginID( $text )
{
    if( __substr__( '@', $text ) )
        return explode( '@', extract_emails_from($text) )[0];
    return $text;
}

// Form HERE: https://stackoverflow.com/a/25879953/180512://stackoverflow.com/a/25879953/1805129
function hippo_shell_exec($cmd, &$stdout=null, &$stderr=null)
{
    $proc = proc_open($cmd,[
        1 => ['pipe','w'],
        2 => ['pipe','w'],
    ],$pipes);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    if( $stderr && trim( $stderr ) )
        printWarning( "There was an error executing <pre>$cmd</pre> $stderr. " );

    return proc_close($proc);
}

function authenticate( $ldap, $pass )
{
    $auth = null;
    if( ldapAlive( 'ldap.ncbs.res.in' ) )
        $auth = @authenticateUsingLDAP( $ldap, $pass );
    else
    {
        // Try login using IMAP.
        $auth = @authenticateUsingIMAP( $ldap, $pass );
        if( ! $auth )
            $auth = null;
    }
    return $auth;
}

function venueToShortText( $venue )
{
    if( is_string( $venue ) )
        $venue = getVenueById( $venue );
    $txt = '';
    $txt .= $venue['name'];
    return $txt;
}

function venueToText($venue, $show_strength = true )
{
    if( is_string( $venue ) )
        $venue = getVenueById( $venue );

    $txt = '';
    $txt .= $venue['id'] . ' ';

    if( $show_strength )
        $txt .= ' ' . $venue['strength'] . ' ';

    $txt .= '[' . $venue['type'] . ']' ;
    return $txt;
}

function sortByKey( &$arr, $key, $ascending = true )
{
    if( ! $arr )
        return false;

    if( ! array_key_exists( $key, $arr[0] ) )
        return false;

    if( $ascending )
        usort( $arr , function( $x, $y ) {
            global $key;
            if( $key == 'date' or $key == 'time' )
                return strtotime( $x[$key] ) < strtotime( $y[$key] );
            else
                return __get__( $x, $key, '') < __get__($y, $key, '' );
        }
    );
    else
        usort( $arr , function( $x, $y ) {
            global $key;
            if( $key == 'date' or $key == 'time' )
                return strtotime( $x[$key] ) >= strtotime( $y[$key] );
            else
                return __get__( $x, $key, '') >= __get__($y, $key, '' );
        }
    );

    return true;
}


// Convert an integer to color.
function toColor($n)
{
    $n = crc32($n % 1000);
    $n &= 0xffffffff;
    return("#".substr("000000".dechex($n),-6));
}

/**
    * @brief Data directory for temp storage.
    *
    * @return
 */
function getDataDir( )
{
    return '/srv/hippo';
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Find the upload directory. Its hardcoded to the
    * getDataDir()/images.
    *
    * @Returns   getDataDir() / images
 */
/* ----------------------------------------------------------------------------*/
function getUploadDir( )
{
    $dir = getDataDir() . '/images/';
    if( ! is_dir($dir))
        mkdir($dir, 0700, true);
    return $dir;
}

/**
    * @brief Generate a select list outof given values.
    *
    * @param $venues List of venues.
    * @param $ismultiple Do we want to select multiple entries.
    * @param $selected Pre-select these guys.
    *
    * @return
 */
function venuesToHTMLSelect( $venues = null, $ismultiple = false
    , $selectName = 'venue', $preSelected = array()
    ) : string
{
    if( ! $venues )
        $venues = getVenues( );

    $multiple = '';
    $default = '-- select a venue --';
    if( $ismultiple )
    {
        $multiple = 'multiple size="5"';
        $default = '-- select multiple venues --';
        $selectName .= "[]";
    }

    $html = "<select $multiple name=\"$selectName\" id=\"$selectName\">";
    if( ! $ismultiple )
        $html .= "<option selected value>$default</option>";

    foreach( $venues as $v )
    {
        $selected = '';
        if( in_array( $v['id'], $preSelected ) )
            $selected = 'selected';

        $text = venueToText( $v );
        if( $v['suitable_for_conference'] == 'YES' )
            $text .= '<font color=\"blue\"> +C </font>';
        if( $v['has_projector'] == 'YES' )
            $text .= '<font color=\"blue\"> +P </font>';

        $venueid = $v['id'];
        $html .= "<option value=\"$venueid\" $selected> $text </option>";
    }

    $html .= "</select>";
    return $html;
}

function generateRandomString($length = 10)
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function appRootDir( )
{
    if(isset($_SERVER['HTTPS'])){
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    }
    else{
        $protocol = 'http';
    }
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . '/hippo';
}

/**
    * @brief From
    * http://stackoverflow.com/questions/2820723/how-to-get-base-url-with-php
    *
    * @return
 */
function appURL( )
{
    return "https://ncbs.res.in/hippo/";
}

/* Go to a page relative to base dir. */
function goToPage($page="/welcome", $delay = 3)
{
    $url = site_url() . $page;

    // echo alertUser( "Sending you to $page... Please wait!" );
    try 
    {
        header("Refresh: $delay, url=$url");
    }
    catch (Exception $e) 
    {
        echo printWarning( "Failed to redirect!" );
    }
}

function __get__( array $arr, $what, $default = NULL )
{
    if( ! $arr )
        return $default;

    if( array_key_exists( $what, $arr ) )
    {
       $val = $arr[$what];
       if( $val )
           return $val;
    }
    return $default;
}

/**
    * @brief Convert a repeat pattern to dates.
    *
    * @param $pat This pattern is command separeted list of days,
    * weeks,durations. eg. 0/2/4,2/3,5 means that event will be scheduled on day
    * 0 (sun), day 2 (Tue), and day 4 (Thu), every 2nd and 3rd week for 5
    * months.
    *
    * @return List of dates generated from this pattern.
 */
function repeatPatToDays( string $pat, string $start_day = 'today' ) : array
{
    if( trim($pat) == '' )
        return array();

    $exploded = explode( ",", $pat);
    $days = $exploded[0];

    $weeks = __get__( $exploded, 1, "All" );

    $durationInMonths = intval($exploded[2]);
    if( ! $durationInMonths )
        $durationInMonths = 6;

    if( $weeks == "All" )
        $weeks = "first/second/third/fourth/fifth";

    $weeks = explode( "/", $weeks );
    $days = explode( "/", $days );

    // Get the base day which is Sunday. If today is not sunday then previous
    // Sunday must be taken into account.
    $baseDay = dbDate( $start_day );
    // echo( "BASEDAY $start_day $baseDay" );

    // Now fill the dates for given pattern.
    $dates = [];

    $thisMonth = date( 'F', strtotime( $baseDay ) );

    for( $i = 0; $i <= $durationInMonths;  $i ++ ) // Iterate of maximum duration.
    {
        $month = date( "F Y", strtotime( "+" . "$i months", strtotime($baseDay) ) );
        foreach( $weeks as $w )
        {
            foreach( $days as $d )
            {
                $strDate = "$w $d  $month";
                $date = dbDate( strtotime( $strDate ) );

                if( (strtotime( $date ) >= strtotime( $start_day )) && 
                    (dateDiffInMonths( $date, $start_day ) <= $durationInMonths) 
                )
                {
                    if( ! in_array( $date, $dates ) )
                        $dates[] = $date;
                }
            }
        }
    }
    sort( $dates );
    return $dates;
}

function daysToDate( $ndays, $baseDay = NULL )
{
    $bd = date("l", $baseDay);
    $result = Array( );
    $baseDay = date("Y-m-d", $baseDay);
    foreach( $ndays as $nd )
    {
        $date = date('Y-m-d', strtotime( $baseDay . ' + ' . $nd  . ' days'));
        array_push( $result, $date );
    }
    return $result;
}

function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function humanReadableDate( $date, $with_day = true )
{
    $fmt = 'l, M d, Y';
    if(! $with_day )
        $fmt = 'M d, Y';

    if( is_int( $date ) )
        return date( $fmt, $date );

    return date( $fmt, strtotime($date) );
}

function humanReadableShortDate( $date )
{
    if( is_int( $date ) )
        return date( 'l, M d, Y', $date );

    return date( 'D, M d', strtotime($date) );
}

function humanReadableTime( $time )
{
    if( is_int( $time ) )
        return date( 'h:i A', $time );

    return date( 'h:i A', strtotime($time) );
}


// Return a format date for mysql database.
function dbDate( $date )
{
    if( is_int( $date ) )
        return date( 'Y-m-d', $date );

    return date( 'Y-m-d', strtotime( $date ) );
}

function dbDateTime( $date )
{
    if( is_int( $date ) )
        return date( 'Y-m-d H:i:s', $date );

    return date( 'Y-m-d H:i:s', strtotime( $date ) );
}

function dbTime( $time )
{
    if( is_int( $time ) )
        return date( 'H:i', $time );
    return date( 'H:i', strtotime( $time ) );
}

// Return the name of the day for given date.
function nameOfTheDay( $date )
{
    return date( 'l', strtotime( $date ) );
}

function getNumDaysInBetween( $startDate, $endDate )
{
    $start = new DateTime( $startDate );
    $end = new DateTime( $endDate );
    return intval($start->diff( $end )->format( "%R%a" ));
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Convert given date and time to google time. We substract the
    * timezone time to make sure google gets the right time.
    *
    * @Param $date
    * @Param $time
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function dateTimeToGOOGLE( $date, $time )
{
    $offset = 5.5 * 3600;
    $format = 'Ymd\\THi00\\Z';
    $timestamp = strtotime( $date . ' ' . $time ) - $offset;
    $date = date($format, $timestamp);
    return $date;
}


/**
    * @brief Construct a repeat pattern out of user queries.
    *
    * @param $daypat
    * @param $weekpat
    * @param $monthpat
    *
    * @return
 */
function constructRepeatPattern( string $daypat, string $weekpat, string $durationInMonths):string
{

   $daypat = trim( $daypat );
   if( strlen( $daypat ) == 0 )
       return '';

   $repeatPat = '';

   // Day pattern.
   $daypat = trim( str_replace( ",", " ", $daypat ));
   $daysArr = array( );
   foreach( explode( " ", $daypat ) as $day )
   {
       $day = substr( $day, 0, 3 ); // Trim to first 3 letters.
       if( strlen( $day ) == 3 )
           $daysArr[] = $day;
       else
           echo alertUser( "Day $day is not 3 letter long. Ignored .. " );
   }

   $days = array_map( function( $day ) { return date('D', strtotime( $day ) ); }
                            , $daysArr );

   $days = implode( "/", $days );

   // Week pattern.
   if( strlen( trim( $weekpat ) ) < 1 )
       $weeks = 'first/second/third/fourth/fifth';
   else
   {
       $weekpat = str_replace( ",", " ", trim( $weekpat ) );
       $weeks = str_replace( ' ', '/', $weekpat );
   }

   return "$days,$weeks,$durationInMonths";
}

/**
    * @brief Base function to compute diff in two strings.
    *
    * @param $from
    * @param $to
    *
    * @return
 */
function computeDiff($from, $to)
{
    $diffValues = array();
    $diffMask = array();

    $dm = array();
    $n1 = count($from);
    $n2 = count($to);

    for ($j = -1; $j < $n2; $j++) $dm[-1][$j] = 0;
    for ($i = -1; $i < $n1; $i++) $dm[$i][-1] = 0;
    for ($i = 0; $i < $n1; $i++)
    {
        for ($j = 0; $j < $n2; $j++)
        {
            if ($from[$i] == $to[$j])
            {
                $ad = $dm[$i - 1][$j - 1];
                $dm[$i][$j] = $ad + 1;
            }
            else
            {
                $a1 = $dm[$i - 1][$j];
                $a2 = $dm[$i][$j - 1];
                $dm[$i][$j] = max($a1, $a2);
            }
        }
    }

    $i = $n1 - 1;
    $j = $n2 - 1;
    while (($i > -1) || ($j > -1))
    {
        if ($j > -1)
        {
            if ($dm[$i][$j - 1] == $dm[$i][$j])
            {
                $diffValues[] = $to[$j];
                $diffMask[] = 1;
                $j--;
                continue;
            }
        }
        if ($i > -1)
        {
            if ($dm[$i - 1][$j] == $dm[$i][$j])
            {
                $diffValues[] = $from[$i];
                $diffMask[] = -1;
                $i--;
                continue;
            }
        }
        {
            $diffValues[] = $from[$i];
            $diffMask[] = 0;
            $i--;
            $j--;
        }
    }

    $diffValues = array_reverse($diffValues);
    $diffMask = array_reverse($diffMask);

    return array('values' => $diffValues, 'mask' => $diffMask);
}

/**
    * @brief Compute diff of two lines.
    *
    * @param $line1
    * @param $line2
    *
    * @return
 */
function diffline($line1, $line2)
{
    $diff = computeDiff(str_split($line1), str_split($line2));
    $diffval = $diff['values'];
    $diffmask = $diff['mask'];

    $n = count($diffval);
    $pmc = 0;
    $result = '';
    for ($i = 0; $i < $n; $i++)
    {
        $mc = $diffmask[$i];
        if ($mc != $pmc)
        {
            switch ($pmc)
            {
                case -1: $result .= '</del>'; break;
                case 1: $result .= '</ins>'; break;
            }
            switch ($mc)
            {
                case -1: $result .= '<del>'; break;
                case 1: $result .= '<ins>'; break;
            }
        }
        $result .= $diffval[$i];

        $pmc = $mc;
    }
    switch ($pmc)
    {
        case -1: $result .= '</del>'; break;
        case 1: $result .= '</ins>'; break;
    }

    return $result;
}

/**
    * @brief Check if given string a date.
    *  http://php.net/manual/en/function.checkdate.php#113205
    * @param $date
    *
    * @return True if string is a date.
 */
function isStringAValidDate( $date )
{
    $d = date_create_from_format( 'Y-m-d', $date );
    if( ! $d )
        return false;
    return (strcasecmp( $d->format( 'Y-m-d' ), $date ) == 0);
}

function isMobile()
{
    return preg_match(
        "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i"
        , $_SERVER["HTTP_USER_AGENT"]
    );
}

/**
    * @brief Store image a PNG. Reduce the resolution of image.
    *
    * @param $originalImage
    * @param $ext
    * @param $outputImage
    * @param $quality
    *
    * @return
 */
function saveImageAsPNG($originalImage, $ext, $outputImage, $quality = 9 )
{
    // jpg, png, gif or bmp?
    if (preg_match('/jpg|jpeg/i',$ext))
        $imageTmp=imagecreatefromjpeg($originalImage);
    else if (preg_match('/png/i',$ext))
        $imageTmp=imagecreatefrompng($originalImage);
    else if (preg_match('/gif/i',$ext))
        $imageTmp=imagecreatefromgif($originalImage);
    else if (preg_match('/bmp/i',$ext))
        $imageTmp=imagecreatefrombmp($originalImage);
    else
        return false;

    // quality is a value from 0 (worst) to 10 (best)
    $x = imagesx( $imageTmp );
    $y = imagesy( $imageTmp );

    echo "$x and $y";

    // Keep the scaling.
    $newW = 200; $newH = (int)( $newW * $y / $x );
    $newImg = imagecreatetruecolor( $newW, $newH );
    imagecopyresampled( $newImg, $imageTmp, 0, 0, 0, 0, $newW, $newH, $x, $y );

    imagepng($newImg, $outputImage, $quality);
    imagedestroy($imageTmp);
    return true;
}

function saveImageAsJPEG($originalImage, $ext, $outputImage, $quality = 90 )
{
    // Keep the scaling factor of original image. User ImageMagick.
    $img = new Imagick( $originalImage );
    $w = $img->getImageWidth( );
    $h = $img->getImageHeight( );
    $newW = 200; $newH = (int)( $newW * $h / $w );
    $img->resizeImage( $newW, $newH, Imagick::FILTER_GAUSSIAN, 1);

    // Remove the old one.
    if( file_exists( $outputImage ) )
        unlink( $outputImage );

    $img->writeImage( $outputImage );
    if( file_exists( $outputImage ) )
    {
        $img->clear( );
        $img->destroy( );
        return true;
    }
    else
        return false;
}

/**
    * @brief Get a low resolution image of pdf files.
    *
    * @param $originalImage
    *
    * @return
 */
function getThumbnail( $originalImage )
{
    // Keep the scaling factor of original image. User ImageMagick.

    $img = new Imagick( $originalImage );
    $img->thumbnailImage( 200, 0 );
    $img->setImageBackgroundColor('#ffffff');
    // $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
    $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

    // Create thumbnain in temp dir.
    $outputImage = sys_get_temp_dir() . '/thumb.png';
    // echo "Creating $outputImage ";
    $img->writeImage( $outputImage );
    $img->clear( );
    $img->destroy( );
    return $outputImage;
}


/**
    * @brief Image of login. These are different than speaker.
    *
    * @param $user
    *
    * @return
 */
function getLoginPicturePath( $login, $default = 'hippo' )
{
    $conf = getConf( );
    $picPath = $conf['data']['user_imagedir'] . '/' . $login . '.jpg';
    if( ! file_exists( $picPath ) )
        $picPath = nullPicPath( $default );

    return $picPath;
}

function calendarUrl( )
{
    return getConf()['data']['CALENDAR_URL'];
}

/**
    * @brief Picture path of speakers (not user login).
    *
    * @param $user
    * @param $default
    *
    * @return
 */
function getUserPicture( $user, $default = 'null', $width='200px' )
{
    $picPath = getLoginPicturePath( $user, $default );
    $html ='<img class="login_picture" width="'. $width . '" height="auto"
                src="' . dataURI( $picPath, 'image/jpg' ) . '" >';
    return $html;
}

function getUserPicturePath( $user )
{
    $picPath = getLoginPicturePath( $user, $default );
    return $picPath;
}


function getSpeakerPicturePath( $speaker )
{
    $conf = getConf( );
    $datadir = $conf[ 'data' ]['user_imagedir'];

    if( is_numeric( $speaker ) && intval( $speaker ) > 0 )
    {
        // The speaker may not be inserted in database yet. Just return the
        // image by id.
        return $datadir . '/' . $speaker . '.jpg';
    }

    else if( is_string( $speaker ) )
        $speaker = splitName( $speaker );

    // If image exists by speaker id then return that else go back to old
    // model where emails are saved by name of the speaker.
    if( intval( __get__( $speaker, 'id', 0 ) )  > 0 )
        return $datadir . '/' . $speaker[ 'id' ] . '.jpg';

    $filename = $speaker[ 'first_name' ] . $speaker[ 'middle_name' ] .
                $speaker[ 'last_name' ] . '.jpg' ;

    $filename = str_replace( ' ', '', $filename );
    return $datadir . '/' . $filename;
}

function getSpeakerPicturePathById( $id )
{
    $conf = getConf( );
    $datadir = $conf[ 'data' ]['user_imagedir'];
    return $datadir . '/' . $id . '.jpg';
}

/**
    * @brief Reschedule AWS.
    *
    * @return
 */
function rescheduleAWS( $method = 'reschedule_default' ) : array
{
    if( $method == 'reschedule_default' )
        $scriptPath = FCPATH.'./scripts/schedule_notheme.sh';
    else if( $method == 'reschedule_group_greedy' )
        $scriptPath = FCPATH.'./scripts/schedule_aws_greedy_groupwise.py';
    else if( $method == 'reschedule_group' )
        $scriptPath = FCPATH.'./scripts/schedule_groupbytheme.sh';
    else
        $scriptPath = FCPATH.'./scripts/schedule_notheme.sh';

    // echo("Executing $scriptPath with timeout 60 secs.");
    $command = "timeout 60 $scriptPath";
    $res = @hippo_shell_exec( $command, $output, $err );
    return [ 'status' => $res, 'stdout' => $err . $output ];
}

function html2Markdown( $html, $strip_inline_image = false ) : string
{
    if( $strip_inline_image )
    {
        // remove img tag.
        $html = preg_replace( '/<img[^>]+\>/i', '', $html );
    }

    $outfile = tempnam( "/tmp", "HIPPO" );
    file_put_contents( $outfile, $html );
    if( file_exists( $outfile ) )
    {
        $cmd = FCPATH."scripts/html2other.py $outfile md ";
        // echo printInfo( "Executing $cmd", false );
        hippo_shell_exec( $cmd, $md, $stderr );
        if( $stderr )
        {
            echo printErrorSevere( "Error: $stderr" );
            return '';
        }
        return $md;
    }
    return $html;
}

function html2Tex( $html, $strip_inline_image = false )
{
    // remove img tag if user wants.
    if( $strip_inline_image )
        $html = preg_replace( '/<img[^>]+\>/i', '', $html );

    $outfile = tempnam( '/tmp', 'html2tex' );
    file_put_contents( $outfile, $html );

    if( file_exists( $outfile ) )
    {
        $cmd = FCPATH."scripts/html2other.py $outfile tex ";
        hippo_shell_exec( $cmd, $texfile, $stderr );
        unlink( $outfile );
        $tex = file_get_contents( trim($texfile) );
    }
    else
        $tex = 'FILE NOT FOUND';
    return $tex;
}

function saveDownloadableFile( string $filename, string $content ) : bool
{
    $filepath = sys_get_temp_dir(). "/$filename";
    file_put_contents( $filepath, $content );
    if( file_exists( $filepath ) )
        return true;
    else
        echo printWarning( "Failed to save content to file $filepath" );

    return false;
}

function getConf( )
{
    $inifile = '/etc/hipporc';
    if(! isset($_SESSION))
        return parse_ini_file( $inifile, $process_section = True );

    if(! __get__($_SESSION, 'conf', []))
        $_SESSION['conf'] = parse_ini_file( $inifile, $process_section = True );
    return $_SESSION['conf'];
}

/**
    * @brief Upload a given file. If filename is not absolute path then construct
    * it.
    *
    * @param $pic Array from $_FILE['picture'], usually!
    * @param $filename
    *
    * @return
 */
function uploadImage( $pic, $filename )
{
    if( ! $pic )
        return false;

    $tmpfile = $pic[ 'tmp_name' ];
    if( ! $tmpfile )
        return false;

    $type = explode( '/', $pic[ 'type' ] );
    $ext = $type[1];

    if( strlen( $tmpfile ) < 1 )
        return false;

    $conf = getConf( );
    $datadir = $conf[ 'data' ][ 'user_imagedir' ];
    if( strpos( $filename, $datadir ) !== false )
        $picPath = $filename;
    else
        $picPath = $conf[ 'data' ][ 'user_imagedir' ] . '/' . $filename ;

    return saveImageAsJPEG( $tmpfile, $ext, $picPath );
}

/**
* @brief Check if a booking request is valid.
* NOTE: This function is incomplete.
*
* @param $request
*
* @return
 */
function isBookingRequestValid( $request )
{
    $date = $request[ 'date' ];
    $startT = $request[ 'start_time' ];
    $endT = $request[ 'end_time' ];

    if( strtotime( $endT, strtotime( $date) ) -
        strtotime( $startT, strtotime( $date ) )  < 15
        )
    {
        echo printWarning( "Could not create booking request" );
        echo printInfo( "\tThe duration of this event is less than 15 minutes" );
        return false;
    }

    return true;
}

/**
    * @brief Save data file to datadir and return if saved file exists on not.
    *
    * @param $filename Filename.
    * @param $text  Text to save.
    *
    * @return
 */
function saveDataFile( $filepath, $text )
{
    // $filepath = getDataDir( ) . '/' . $filename;
    file_put_contents( $filepath, $text );
    return file_exists( $filepath );
}

/**
    * @brief Return next monday from given date.
    *
    * @param $date
    *
    * @return
 */
function nextMonday( $date )
{
    if( ! $date )
        return null;

    $date = dbDate( $date );
    // check if dates are monday. If not assign next monday.
    if( $date && date( 'D', strtotime($date) ) !== 'Mon' )
        $date = dbDate(
            strtotime( 'next monday', strtotime( $date ) )
            );
    return $date;
}

function slotGroupId( $id )
{
    // Remove the last character, rest if group id.
    return substr( $id, 0, -1 );
}

/**
    * @brief Get semester name of given date.
    *
    * @param $date
    *
    * @return 'VASANT' or 'MONSOON' depending on date.
 */
function getSemester( $date )
{
    $month = intval( date( 'm', strtotime( $date) ) );
    if( $month > 0 && $month < 7 )
        return 'SPRING';
    else
        return 'AUTUMN';

}


/**
    * @brief Get current year.
    *
    * @param $date
    *
    * @return
 */
function getYear($date)
{
    return date( 'Y', strtotime( $date ) );
}

function getCurrentYear( )
{
    return getYear( 'today' );
}

function getCurrentSemester( )
{
    return getSemester( 'today' );
}

function getCourseInstanceId( $courseId, $sem = null, $year = null )
{
    if( ! $sem )
        $sem = getCurrentSemester( );
    if( ! $year )
        $year = getCurrentYear( );
    return "$courseId-$sem-$year";
}

/**
    * @brief Check if given event is a public event.
    *
    * @param $event
    *
    * @return
 */
function isPublicEvent( $event )
{
    return ( $event[ 'is_public_event' ] == 'YES' );
}

function splitName( $name )
{
    $result = array();
    $name = preg_replace( '/^(Dr|Prof|Mr|Mrs)\s*/', '', $name );

    $name = explode( ' ', $name );
    $result[ 'first_name' ] = $name[ 0 ];
    $result[ 'last_name' ] = end( $name );

    if( count( $name ) == 3 )
        $result[ 'middle_name' ] = $name[1];
    else
        $result[ 'middle_name' ] = '';

    return $result;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Tested locally.
    *
    * @Param $name
    *
    * @Returns   
    * 
    * TEST CASES.
 */
/* ----------------------------------------------------------------------------*/
function splitNameIntoParts(string $name) : array
{
    $name = trim($name);
    preg_match( '/^((?P<honorific>(Dr|Prof|Mr|Mrs))\.?\s+)?(?P<first_name>\S+)(\s+(?P<middle_name>\S+))?\s+(?P<last_name>\S+)?$/', $name, $matches);
    return $matches;
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Verify the booking request.
    *
    * @Param $request
    *
    * @Returns  "OK" if booking request if fine. A detailed message otherwise.
 */
/* ----------------------------------------------------------------------------*/
function verifyRequest( array $request ) : string
{
    if( ! isset( $request ) )
        return "Empty request";

    foreach( array( "start_time", "end_time", "date", "venue" ) as $k )
        if( ! isset( $request[$k] ) ) 
            return "Neccessary value $k not found";

    // Check the end_time must be later than start_time .
    // At least 15 minutes event
    if( strtotime( $request['end_time'] ) - strtotime( $request['start_time'] ) < 900 )
    {
        $msg = "$symbClock The event must be at least 15 minute long";
        $msg .= " Start time " . $request[ 'start_time' ] . " to end time " .
            $request[ 'end_time' ];
        return $msg;
    }
    if( ! isset( $request['venue'] ) )
    {
        return "No venue found in your request. If you think this is a bug,
           please write to hippo@lists.ncbs.res.in " ;
    }

    if( strlen( $request[ 'title' ] ) < 8 )
    {
        return "Request 'TITLE' must have at least 8 characters. Got <pre>"
            . $request[ 'title' ] . "</pre>";
    }

    // Let admin override this condition.
    if( ! anyOfTheseRoles(array('BOOKMYVENUE_ADMIN', 'ACAD_ADMIN', 'MEETINGS')))
    {
        if( strtotime( $request[ 'date' ] ) >= strtotime( 'now' ) + 60 * 24 * 3600 )
        {
            return "You can not book more than 60 days in advance";
        }
    }

    if( strtotime( $request[ 'date' ]. ' ' . $request[ 'start_time'] ) < strtotime( 'now' ) )
    {
        $error = "You can not create request in the past";
        $error .= ". Got " . $request[ 'date' ] . ' ' . $request[ 'start_time' ];
        $error .=  ", time now is "  . dbDateTime( 'now' );
        return $error;
    }

    if( "UNKNOWN" == $request[ 'class' ] )
    {
        $error = "
            You did not select appropriate <tt>CLASS</tt> for your booking
            request. By default it is set to <tt>UNKNOWN</tt> which is not
            acceptable. If you are not sure, select <tt>OTHER</tt>.
            ";
        return $error;
    }

    return "OK";
}

/**
    * @brief Detect host institute from Email id. It could be either NCBS or
    * INSTEM.
    *
    * @param $email
    *
    * @return
 */
function emailInstitute( $email, $email2='', $format='html')
{

    $ncbs = 'National Center For Biological Sciences (TIFR)';
    $instem = 'Institute for Stem Cell Science and Regenerative Medicine';
    $ccamp =  'Center for Cellular and Molecular Platforms';

    if( $format == 'html' )
        $break = '<br>';
    else if( $format == 'latex' )
        $break = '\linebreak ';
    else
        $break = '\n';

    $res = $ncbs;
    if( strpos( $email, 'instem.res.in' ) !== false )
        $res = $instem;
    else if( strpos( $email, 'ccamp.res.in' ) !== false )
        $res = $ncbs . $break . $ccamp;

    if($email2)
    {
        $res .= $break;
        if( strpos( $email2, 'instem.res.in' ) !== false )
            $res .= $instem;
        else if( strpos($email2, 'ccamp.res.in' ) !== false )
            $res .= $ncbs . $break . $ccamp;
        else
            $res .= $ncbs;
    }
    return $res;
}

/**
    * @brief Removed duplicates and turn into UCWord.
    *
    * @param $name
    *
    * @return
 */
function fixName( $name )
{
    $arrName = array_unique( explode( ' ', $name ) );
    $name = implode( ' ', $arrName );
    return __ucwords__( $name );
}


/**
    * @brief Following functions generates event title based on talk.
    *
    * @param $talk
    *
    * @return
 */
function talkToEventTitle( $talk )
{
    $title = __ucwords__( $talk[ 'class' ] ) . " by " . fixName( $talk[ 'speaker' ] );
    $title .= " on '" . $talk[ 'title' ] . "'";
    return $title;
}

function talkToShortEventTitle( $talk )
{
    $title = __ucwords__( $talk[ 'class' ] ) . " by " . fixName( $talk[ 'speaker' ] );
    return $title;
}

/**
    * @brief Fix tags.
    *
    * @param $tags
    *
    * @return
 */
function fixTags( $tags )
{
    $tags = preg_replace( '/([;]+\s*|[,]+\s*|\s+)/', ',', $tags );
    return $tags;
}

/**
    * @brief Check if two time interval are overalloing.
    *
    * @param $s1 Start time 1.
    * @param $e1 End time 1.
    * @param $s2 Start time 2.
    * @param $e2 End time 2.
    *
    * @return  Return true if there is overlap.
 */
function isOverlappingTimeInterval( $start1, $end1, $start2, $end2 )
{
    $s1 = strtotime( $start1 );
    $e1 = strtotime( $end1 );
    $s2 = strtotime( $start2 );
    $e2 = strtotime( $end2 );

    assert( $s1 < $e1 );
    assert( $s2 < $e2 );

    $res = true;
    if( $s1 < $s2 && $e1 <= $s2 )
        $res = false;

    if( $s2 < $s1 && $e2 <= $s1 )
        $res = false;

    return $res;
}


/**
    * @brief Convert array to name.
    *
    * @param $name
    *
    * @return
 */
function nameArrayToText( $name )
{
    $txt = __get__( $name, 'first_name', '' ) . ' '
                . __get__( $name, 'middle_name', '' ) . ' '
                . __get__( $name, 'last_name', '' );
    return $txt;
}

/**
    * @brief Return a external_id of talk for booking.
    *
    * @param $talk
    *
    * @return "talks.$id"
 */
function getTalkExternalId( $talk )
{
    $id = '';
    if( is_array( $talk ) )
        $id = $talk[ 'id' ];
    else if( is_string( $talk ) )
        $id = $talk;

    return "talks.$id";
}

// returns true if $needle is a substring of $haystack
function contains($needle, $haystack)
{
    return strpos($haystack, $needle) !== false;
}

/**
    * @brief Check if a given request or event belong to a talk.
    *
    * @param $event Given request of talk.
    *
    * @return
 */
function isEventOfTalk( $event )
{
    $externalId = __get__( $event, 'external_id', 'SELF.-1' );

    // This is valid id if there is an external talk.
    if( preg_match( '/talks\.\d+/', $externalId ) )
        return true;

    return false;

}

function getSlotIdOfTile( $tile )
{
    preg_match_all( '!\d+!', $tile, $matches );
    return implode( '', $matches[0] );
}


function getSlotsAtThisDay( $day, $slots = null )
{
    if( ! $slots )
        $slots = getTableEntries( 'slots' );

    $res = array( );
    foreach( $slots as $s )
        if( strcasecmp( $s[ 'day' ], $day ) == 0 )
            $res[] = $s;

    return $res;

}

function getSlotAtThisTime( $day, $slot_time, $slots = null )
{
    if( ! $slots )
        $slots = getTableEntries( 'slots' );

    $slot = null;
    foreach( $slots as $s )
    {
        if( strcasecmp( $s[ 'day' ], $day ) == 0 )
        {
            if( dbTime( $s[ 'start_time' ]) == $slot_time )
                return $s;
        }
    }

    return $slot;
}

function getNextSemester( $sem = null, $year = null )
{
    $nextSem = array( );
    if( ! $sem )
        $sem = getCurrentSemester( );
    if( ! $year )
        $year = getCurrentYear( );

    $nextSem[ 'semester' ] = 'AUTUMN';
    $nextSem[ 'year' ] = $year;

    if( $sem == 'AUTUMN' )
    {
        $nextSem[ 'semester' ] = 'SPRING';
        $nextSem[ 'year' ] = date( 'Y', strtotime( 'next year' ));
    }
    return $nextSem;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  See if substring is found in given string.
    *
    * @Param $needle
    * @Param $haystack
    * @Param $case_insensitive
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function __substr__( string $needle, string $haystack, bool $case_insensitive = false ) 
{
    if( ! $needle )
        return false;
    if( ! $haystack )
        return false;

    if( $case_insensitive )
    {
        $needle = strtolower( $needle );
        $haystack = strtolower( $haystack );
    }
    return ( strpos( strtolower( $haystack ), strtolower( $needle) ) !== false );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Is given URL an image.
    *
    * @Param $url
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function isImage( $url )
{
     $params = array('http' => array(
                  'method' => 'HEAD'
               ));
     $ctx = stream_context_create($params);
     $fp = @fopen($url, 'rb', false, $ctx);
     if (!$fp)
        return false;  // Problem with url

    $meta = stream_get_meta_data($fp);
    if ($meta === false)
    {
        fclose($fp);
        return false;  // Problem reading data from url
    }

    $wrapper_data = $meta["wrapper_data"];
    if(is_array($wrapper_data)){
      foreach(array_keys($wrapper_data) as $hh){
          if (substr($wrapper_data[$hh], 0, 19) == "Content-Type: image") // strlen("Content-Type: image") == 19
          {
            fclose($fp);
            return true;
          }
      }
    }

    fclose($fp);
    return false;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Select random JPEG from directory.
    *
    * @Param $dir
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function random_jpeg( $dir )
{
    $files = glob( "$dir/*.jpg" );
    if( $files )
    {
        $idx = array_rand( $files );
        return $files[ $idx ];
    }
    return '';
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Is the given course active on given date.
    *
    * @Param $course
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function isCourseActive( $course, $day = 'today' )
{
    $date = strtotime( $day );

    $start = strtotime( $course[ 'start_date' ] );
    $end = strtotime( $course[ 'end_date' ] );

    if( $date >= $start && $date <= $end )
        return true;

    return false;
}


function cancelEventAndNotifyBookingParty( $ev )
{
    echo printInfo( "Cancelling and notifying the booking party" );

    $res = changeStatusOfEvent( $ev[ 'gid' ]
        , $ev[ 'eid' ], $ev[ 'created_by' ], 'CANCELLED'
    );

    $login = $ev[ 'created_by' ];
    if( $res )
    {
        // Nofity the user.
        $to = getLoginEmail( $ev[ 'created_by' ] );
        $cc = 'hippo@lists.ncbs.res.in';
        $subject = 'ATTN: Your booked event has been cancelled by Hippo';
        $msg = "<p> Greetings " . loginToHTML( $login ) . '</p>';

        $msg .= "<p> Following events has been cancelled because it was on a
            lecture hall and an  upcoming course has been scheduled here.
            Lecture Halls are given preference for courses. </p>";

        $msg .= arrayToTableHTML( $ev, 'event' );

        $msg .= "<p>Kindly find another venue for your event. </p>";
        sendHTMLEmail($msg, $subject, $to, $cc );
    }
}

function cancelRequesttAndNotifyBookingParty( $request )
{
    echo printInfo( "Cancelling and notifying the booking party" );
    $res = changeRequestStatus( $request[ 'gid' ]
        , $request[ 'eid' ], $request[ 'created_by' ], 'CANCELLED'
    );

    $login = $request[ 'created_by' ];
    if( $res )
    {
        // Nofity the user.
        $to = getLoginEmail( $request[ 'created_by' ] );
        $cc = 'hippo@lists.ncbs.res.in';
        $subject = 'ATTN: Your booked event has been cancelled by Hippo';
        $msg = "<p> Greetings " . loginToHTML( $login ) . '</p>';

        $msg .= "<p> Following events has been cancelled because it was on a
            lecture hall and an  upcoming course has been scheduled here.
            Lecture Halls are given preference for courses. </p>";

        $msg .= arrayToTableHTML( $request, 'event' );

        $msg .= "<p>Kindly find another venue for your event. </p>";
        sendHTMLEmail($msg, $subject, $to, $cc );
    }
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Insert given key => value at given index.
    *
    * @Param $arr
    * @Param $index
    * @Param $key
    * @Param $value
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function array_insert_at( $arr, $index, $key, $value )
{
    $newarr = array( );
    $i = 0;
    foreach( $arr as $k => $v )
    {
        if( $i == $index )
        {
            $newarr[ $key ] = $value;
            $newarr[ $k ] = $v;
        }
        $newarr[ $k ] = $v;
    }
    return $newarr;
}

/**
* @name Get the course code from a html string. It is usually in the front
* of the string.
*/
function getCourseCode( $cc, $delim = ':' )
{
    if( strlen( trim( $cc ) ) < 1 )
        return '';
    return trim( explode( $delim, $cc )[0] );
}

function getValuesByKey( $arr, $key )
{
    $res = array( );
    foreach( $arr as $row )
        $res[] = $row[ $key ];

    return $res;
}

function diffDates( $date1, $date2, $unit = 'second' )
{
    $d = abs(strtotime( $date1 ) - strtotime( $date2 ));
    if( $unit == 'second' )
        return $d;
    if( $unit == 'minute' )
        return $d / 60;
    if( $unit == 'hour' )
        return $d / 3600;
    if( $unit == 'day' )
        return $d / (24*3600);
    if( $unit == 'week' )
        return $d / (7*24*3600);
    if( $unit == 'month' )
        return intval( $d / (30.41*24*3600));
    return -1;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Check if given string is HTML.
    *
    * @Param $string
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function isHTML( $text )
{
    $processed = htmlentities($text);
    if( $processed == $text ) return false;

    return true;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Split text on any white space (space, tab, comma, newline, * etc.)
    *
    * @Param $text
    * @Param $ext : extra delimiters
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function splitAtCommonDelimeters( $text, $ext = '')
{
    $res = preg_split( "/[\s,$ext]+/", $text );
    return $res;
}

function splitAt( string $text, string $delim ) : array
{
    return explode( $delim, $text );
}

function removeAWSSpeakerFromList(string $speaker, $reason=''): array
{
    $ret = ['success'=>false, 'msg'=>''];

    if(! $reason)
        $reason = p("No reason is given. So rude!");

    $data = ['eligible_for_aws' => 'NO', 'login' => $speaker];
    $res = updateTable( 'logins', 'login', 'eligible_for_aws', $data );
    if( $res )
    {
        $ret['success'] = true;
        $ret['msg'] .= "Successfully removed user $speaker from AWS list.";

        // Send email to speaker.
        $subject = "Your name has been removed from AWS list";
        $msg = "<p>Dear " . loginToText( $speaker ) . " </p>";
        $msg .= p("Your name has been removed from the Annual Work Seminar (AWS) 
            roster. $reason. If this is a mistake, please inform Academic 
            Office.");

        $to = getLoginEmail($speaker);
        $emailRes = sendHTMLEmail($msg, $subject, $to);
        if(! $emailRes)
            $ret['msg'] .=  p( "Could not notify user" );
    }
    return $ret;
}

function addAWSSpeakerToList(string $speaker): array
{
    $ret = ['success'=>false, 'msg'=>''];
    $data = ['eligible_for_aws' => 'YES', 'login' => $speaker];
    $res = updateTable( 'logins', 'login', 'eligible_for_aws', $data );
    if( $res )
    {
        $ret['success'] = true;
        $ret['msg'] .= "Successfully added user $speaker to AWS list.";

        // Send email to speaker.
        $subject = "Your name has been added to AWS roster";
        $msg = "<p>Dear " . loginToText( $speaker ) . " </p>";
        $msg .= p("Your name has been added to Annual Work Seminar (AWS) 
            roster. If this is a mistake, please inform Academic 
            Office.");

        $to = getLoginEmail($speaker);
        $emailRes = sendHTMLEmail($msg, $subject, $to);
        if(! $emailRes)
            $ret['msg'] .=  p( "Could not notify user" );
    }
    return $ret;
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Find a personal by name.
    *
    * @Param name
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getLoginInfoByName( string $name ) : array
{
    $name = trim( $name );
    if( ! $name )
        return array();

    preg_match( 
        '/^((Mr|Ms|Mrs|Dr|Prof)\.?\s+)?(?P<first_name>\S+)\s+(?P<middle_name>\S+)?\s+(?P<last_name>\S+)$/i'
        , $name 
        , $match
    );

    if( __get__($match, 'first_name','') )
        $info = getTableEntry( 'logins', 'first_name,last_name', $match );
    else
        return array();

    $login = __get__( $info, 'login', '' );
    if( ! $login )
        return array();

    return getLoginInfo( $login, true );
}

function isDateAndTimeIsInPast( $date, $time )
{
    if( strtotime( "$date $time" ) < strtotime( 'now' ) )
        return true;
    return false;
}

function findIncompleteEntries( array $arr, string $keys ) : string
{
    $missing = array();
    $keys = explode( ',', $keys );
    foreach( $keys as $key )
    {
        if( ! __get__( $arr, $key, '') )
            $missing[] = $key;
    }
    return implode( ',', $missing );
}

function generateAWSAbstractUsingAI() : string
{
    hippo_shell_exec( "cd " . FCPATH . "/hippo-ai && TERM=xterm make -s", $out, $err );

    if( $out )
    {
        $txtArr = explode( '.', $out);
        $txtArr = array_slice( $txtArr, 1, count($txtArr)-2 );
        $out = implode( '.', $txtArr);
    }
    else
        $out = $err;

    // Now prepare a good looking HTML.
    $out = html_purify( $out );

    return $out;
}

function isDate( string $date, string $format = 'Y-m-d' ) : bool
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}

/*
 * Inserts a new key/value before the key in the array.
 *
 * @param $key
 *   The key to insert before.
 * @param $array
 *   An array to insert in to.
 * @param $new_key
 *   The key to insert.
 * @param $new_value
 *   An value to insert.
 *
 * @return
 *   The new array if the key exists, FALSE otherwise.
 *
 * @see array_insert_after()
 */
function array_insert_before($key, array &$array, $new_key, $new_value) 
{
  if (array_key_exists($key, $array)) {
    $new = array();
    foreach ($array as $k => $value) {
      if ($k === $key) {
        $new[$new_key] = $new_value;
      }
      $new[$k] = $value;
    }
    return $new;
  }
  return FALSE;
}

/*
 * Inserts a new key/value after the key in the array.
 *
 * @param $key
 *   The key to insert after.
 * @param $array
 *   An array to insert in to.
 * @param $new_key
 *   The key to insert.
 * @param $new_value
 *   An value to insert.
 *
 * @return
 *   The new array if the key exists, FALSE otherwise.
 *
 * @see array_insert_before()
 */
function array_insert_after($key, array &$array, $new_key, $new_value) 
{
  if (array_key_exists($key, $array)) {
    $new = array();
    foreach ($array as $k => $value) {
      $new[$k] = $value;
      if ($k === $key) {
        $new[$new_key] = $new_value;
      }
    }
    return $new;
  }
  return FALSE;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get a default venue for a given date.
    *
    * @Param $date
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getDefaultAWSVenue( string $date ) : string
{
    // Even of odd week of the year.
    $day = intval( date('W', strtotime($date)) );
    if( $day  % 2 == 1 )
        return 'Haapus (LH1)';
    else
        return 'InstemAuditorium100Seater';
}

function getAWSVenue( string $date ) : string
{
    $res = getTableEntry( 'upcoming_aws', 'date', ['date'=>$date]);
    return $res['venue'];
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Find a venue of upcoming AWS if it is not assigned.
    *
    * @Param $date
    * @Param $defaultVenue
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getAWSVenueForm( string $date, string $defaultVenue = '' ) : string
{
    $form = '<form action="'.site_url("adminacad/assign_aws_venue/$date").'" method="post" accept-charset="utf-8">';

    $defaultVenue = trim( $defaultVenue );
    if( strlen($defaultVenue)==0)
        $defaultVenue = getDefaultAWSVenue( $date );

    $venues = getVenuesByTypes( 'LECTURE HALL,AUDITORIUM' );
    $form .= venuesToHTMLSelect( $venues, false, 'venue', [$defaultVenue] );
    $form .= "<button>Change</button>";
    $form .= '</form> ';
    return $form;
}

function bookVenue( string $venue, string $date, string $startTime, string $endTime
    , string $class = 'UNKNOWN', string $title = '', string $desc = '')
{

    $gid = 1 + intval(getUniqueFieldValue( 'bookmyvenue_requests', 'gid' ));
    $rid = 0;
    $data = array(
        'gid' => $gid, 'rid' => $rid
        , 'date' => dbDate( $date )
        , 'start_time' => $startTime
        , 'end_time' => $endTime
        , 'venue' => $venue
        , 'title' => $title
        , 'class' => $class
        , 'description' => 'AUTO BOOKED BY Hippo'
        , 'created_by' => 'HIPPO'
        , 'last_modified_on' => dbDateTime( 'now' )
    );
    $gid = submitRequest( $data );
    if( $gid > 0 )
        approveRequest( $gid, $rid, 'APPROVED' );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Inactivate a given account.
    *
    * @Param $id
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function inactiveAccounts( array $ids ) : bool
{
    if( !$ids)
        return false;

    // Else remove the user.
    foreach( $ids as $id )
    {
        if(! $id )
            continue;
        updateTable( 'logins', 'login', 'status,eligible_for_aws'
            , ['login'=>$id, 'status'=>'INACTIVE', 'eligible_for_aws'=>'NO']
        );
    }

    $body = implode( "<br/>", $ids);
    sendHTMLEmail( $body, "Some accounts have been deactivated.", "hippo@lists.ncbs.res.in");
    return true;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Find email id from the given string.
    *
    * @Param $str
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function findEmailIdInParanthesis( string $str ) : string
{
    preg_match("/\((.+?)\)/", $str, $res);
    return $res?$res[1]:"NA";
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Return user keys.
    *
    * @Param $user Name of the users.
    * @Param $level (default -1, return all) else return key of that level.
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getUserKeys(string $login, int $level = -1) : array
{
    $where = "login = '$login' OR login LIKE '$login@%'";
    if($level > 0)
        $where .= " AND level='$level'";
    return getTableEntries('apikeys', 'timestamp', $where);
}

function getUserKey(string $login, int $level = -1) : array
{
    $where = "login = '$login' OR login LIKE '$login@%'";
    if($level > 0)
        $where .= " AND level='$level'";
    return getTableEntry('apikeys', 'login', ['login'=>$login]);
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Generate a new key for given user.
    *
    * @Param $user
    * @Param $level
    *
    * @Returns  
 */
/* ----------------------------------------------------------------------------*/
function genererateNewKey(string $user, int $level = 0)
{
    $newKey = generateRandomString(20);
    $res = insertIntoTable( 'apikeys'
        , "login,apikey,level"
        , ['login'=> $user, 'apikey'=>$newKey, 'level'=>$level]
    );

    if($res)
        return $newKey;
    return '';
}

function cmp( $a, $b )
{
    return strtotime( $a[ 'date' ] ) > strtotime( $b['date'] );
}

function cmp_datetime( $a, $b )
{
    $fmt = 'Y-m-d H:i:s';
    $aa = DateTime::createFromFormat($fmt, $a['date'] . ' ' . $b['start_time']);
    $bb = DateTime::createFromFormat($fmt, $b['date'] . ' ' . $b['start_time']);
    return ($aa->getTimestamp() - $bb->getTimestamp());
}

function getUpcomingPublicEventsFormatted(string $startDate, int $limit = 20, int $offset=0)
{
    // Today's event.
    $events = getPublicEventsNum($startDate, $limit, $offset);

    $all = [];
    foreach( $events as $e )
    {
        $ev = [];
        $date =  dbDate($e['date']);
        $ev['title'] = $e['title'];
        $ev['class'] = $e['class'];
        $ev['date'] = humanReadableDate($e['date']);
        $ev['start_time'] = humanReadableTime($e['start_time']);
        $ev['end_time'] = humanReadableTime($e['end_time']);
        $ev['url'] = "https://ncbs.res.in/hippo/events.php?date=" . $e['date'];
        $ev['start'] = dbDateTime(strtotime($e['date'] . ' '. $e['start_time']));
        $ev['end'] = dbDateTime(strtotime($e['date'] . ' '. $e['end_time']));
        $ev['venue'] = venueToShortText($e['venue'], false);
        $ev['description'] = __get__($e, 'description','');

        // This is an abuse of RSS protocol.
        $ev['datetime'] = date('r', strtotime($e['date'] . ' ' . $e['start_time']));
        $all[] = $ev;
    }
    return $all;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Genearte base64 image out of a path.
    *
    * @Param $filepath
    * @Param $width
    * @Param $height
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getBase64JPEG(string $filepath, int $width=0, int $height = 0) : string
{
    $i = new Imagick( $filepath );
    $i->setImageFormat( "jpeg" );
    if( $width > 0 || $height > 0)
        $i->adaptiveResizeImage($width, $height);
    $bytes = $i->getImageBlob();
    return 'data:image/jpeg;base64, '.base64_encode($bytes);
}

function cancelThisJCRequest($data)
{
    $data[ 'status' ] = 'CANCELLED';
    $res = updateTable( 'jc_requests', 'id', 'status', $data);
    if( $res )
    {
        $entry = getTableEntry( 'jc_requests', 'id', $_POST );
        $presenter = getLoginInfo( $entry[ 'presenter' ] );
        $entryHTML = arrayToVerticalTableHTML($entry, 'info');
        $msg = "<p>Dear " . arrayToName( $presenter ) . "</p>";
        $msg .= "<p>Your presentation request has been cancelled by admin.
            the latest entry is following. </p>";
        $msg .= $entryHTML;

        $subject = 'Your presentation request is CANCELLED by JC admin';
        $to = $presenter['email'];
        $cclist = 'jccoords@ncbs.res.in,hippo@lists.ncbs.res.in';
        $res = sendHTMLEmail( $msg, $subject, $to, $cclist );
    }
    return $res;
}

