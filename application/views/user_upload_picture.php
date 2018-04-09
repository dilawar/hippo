<?php 

include_once 'methods.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( "USER" ) );

echo userHTML( );

$conf = getConf( );

$picPath = $conf['data']['user_imagedir'] . '/' . $_SESSION[ 'user' ] . '.jpg';
if( $_POST[ 'Response' ] == 'upload' )
{
    $img = $_FILES[ 'picture' ];

    if( $img[ 'error' ] != UPLOAD_ERR_OK )
    {
        $errCode = $img[ 'error' ];
        echo minionEmbarrassed( "This file could not be uploaded", $img['error'] );
        echo printWarning( $phpFileUploadErrors[ $errCode ] );
        exit;
    }

    $ext = explode( "/", $img['type'] )[1];
    $tmppath = $img[ 'tmp_name' ];

    if( $img['size'] > 1024 * 1024 )
        echo printWarning( "Picture is too big. Maximum size allowed is 1MB" );
    else
    {
        // Convert to png file and tave to $picPath
        try {
            $res = saveImageAsJPEG( $tmppath, $ext, $picPath );
            if( ! $res )
                echo minionEmbarrassed( 
                    "I could not upload your image (allowed formats: png, jpg, bmp)!" 
                    );
            else
            {
                echo printInfo( "File is uploaded sucessfully" );
                goBack( "user_info.php", 1 );
            }
        } catch (Exception $e ) {
            echo minionEmbarrassed( 
                "I could not upload your image. Error was "
                , $e->getMessage( ) );
        }
    }
}

echo goBackToPageLink( 'user_info.php' );

?>
