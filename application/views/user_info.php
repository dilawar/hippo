<?php

require_once __DIR__ . '/header.php';
require_once BASEPATH . 'extra/tohtml.php' ;
require_once BASEPATH . 'extra/check_access_permissions.php';

echo userHTML( );

$conf = getConf( );
$picPath = $conf['data']['user_imagedir'] . '/' . $_SESSION['user'] . '.jpg';

///////////////////////////////////////////////////////////////////////////
// PICTURE OF SPEAKER
///////////////////////////////////////////////////////////////////////////
$tab = '<table class="">';
$tab .= '<tr><td>';

if( file_exists( $picPath ) )
    $tab .= showImage( $picPath );
else
    $tab .= printInfo( "I could not find your picture in my database.  Please upload one.");

$tab .= '</td></tr><tr><td>';

// Form to upload a picture
$picAction = '<form action="' . site_url( "/user/info/upload_picture" ) . '" 
    method="post" enctype="multipart/form-data">';

$picAction .=  '<p><small>
    This picture will be used in AWS notifications. It will be
    rescaled to fit 5 cm x 5 cm space. I will not accept any picture bigger
    than 1MB in size. Allowed formats (PNG/JPG/GIF/BMP).
    </small></p>
    ';
$picAction .=  '<input type="file" name="picture" id="picture" value="" />';
$picAction .=  '<button name="Response" title="Upload your picture" value="upload">Upload</button>';
$picAction .=  '</form>';
$picAction .=  '</td></tr>';
$picAction .=  '</table>';
$picAction .=  '<br>';

$tab .= $picAction;
echo $tab;

$info = getUserInfo( whoAmI() );

echo '<h1>Your profile </h1>';

$editables = 'title,first_name,last_name,alternative_email,institute' . 
    ',valid_until,joined_on,pi_or_host,specialization';

$specializations = array_map(
    function( $x ) { return $x['specialization']; }, getAllSpecialization( )
);

$info[ 'specialization' ] = arrayToSelectList( 'specialization'
    , $specializations, array(), false, __get__($info, 'specialization', '')
    );

// Prepare select list of faculty.
$faculty = getTableEntries( 'faculty', 'email', "status='ACTIVE'" );
$facultyEmails = array( );
$facMap = array( );
foreach( $faculty as $fac )
{
    $facultyEmails[] = $fac[ 'email'];
    $facMap[ $fac['email'] ] = arrayToName( $fac, $with_email = true );
}

$info[ 'pi_or_host' ] = arrayToSelectList('pi_or_host'
    , $facultyEmails, $facMap, false
    , __get__($info, 'pi_or_host', '' )
);

echo '<form method="post" action="' . site_url('/user/info/action') . '" >';
echo dbTableToHTMLTable( 'logins', $info, $editables );
echo "</form>";

if(__get__($info, 'eligible_for_aws', 'NO') =="NO")
{
    echo alertUser( 
        "If you are <tt>ELIGIBLE FOR AWS</tt>, please write to academic office to include 
        your name."
    );
}

// TODO: ENABLE IS LATER.
//echo '<h3>Submit request to academic office</h3>';
//$form = ' <form method="post" action="user_aws_request.php">';
//if( strtoupper( $info['eligible_for_aws'] ) == "YES" )
//    $form .= ' <button type="submit" name="request_to_academic_office"
//        value="remove_me_from_aws_list">Remove me from AWS list</button> ';
//else
//    $form .= ' <button type="submit" name="request_to_academic_office"
//        value="add_me_to_aws_list">Add me to AWS list</button> ';
//
//$form .= '</form>';
//echo $form;

echo goBackToPageLink( "/user/home", "Go back" );

?>
