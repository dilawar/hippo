<?php

require_once __DIR__ . '/methods.php';

function addUpdateSpeaker(array $data): array
{
    $ret = ['msg'=>'', 'success'=>true];
    if(! __get__($data, 'first_name', ''))
    {
        return ['msg'=> 'Could not parse speaker.', 'success'=>false,  'data'=>[]];
    }

    // If there is not speaker id, then  create a new speaker.
    $sid = __get__( $data, 'id', -1 );
    $res = null;
    $warning = '';
    $speaker = null;

    if($sid < 0)  // Insert a new enetry.
    {
        // Insert a new entry.
        $speakerId = getUniqueFieldValue( 'speakers', 'id' );
        $data[ 'id' ] = intval( $speakerId ) + 1;
        $data['email'] = trim($data['email']);
        $sid = $data[ 'id' ];
        $res = insertIntoTable( 'speakers'
            , 'id,honorific,email,first_name,middle_name,last_name,' .
            'designation,department,homepage,institute'
            , $data
        );
        $speaker = getTableEntry('speakers', 'id', $data);
    }
    else // Update the speaker.
    {
        if( __get__( $data, 'id', 0 ) > 0 )
            $whereKey = 'id';
        else
            $whereKey = 'first_name,middle_name,last_name';

        $speaker = getTableEntry( 'speakers', $whereKey, $data );
        if( $speaker )
        {
            // Update the entry
            $res = updateTable( 'speakers', $whereKey
                , 'honorific,email,first_name,middle_name,last_name,' .
                'designation,department,homepage,institute'
                , $data
            );

            // Update all talks related to  this speaker..
            try 
            {
                $sname =  speakerName( $sid );
                $res = updateTable( 'talks', 'speaker_id', 'speaker'
                    , array( 'speaker_id' => $sid, 'speaker' => $sname )
                );

            } 
            catch (Exception $e) 
            {
                $ret['msg'] .= "Failed to update some talks by this speaker:". $e->getMessage();
            }

            if($res)
                $ret['msg'] .= " .. updated related talks as well.";
        }
    }

    // After inserting new speaker, upload his/her image.
    if( array_key_exists( 'picture', $_FILES ) && $_FILES[ 'picture' ]['name'] )
    {
        $imgpath = getSpeakerPicturePath( $sid );
        $ret['msg'] .= printInfo( "Uploading speaker image to $imgpath .. " );
        $res = uploadImage( $_FILES[ 'picture' ], $imgpath );
        if( ! $res )
            $ret['msg'] .= minionEmbarrassed( "Could not upload speaker image to $imgpath" );
    }

    if( $res )
        $ret['msg'] .= 'Updated/Inserted speaker. <br />' . $warning;
    else
    {
        $ret['success'] = false;
        $ret['msg'] .= printInfo( "Failed to update/insert speaker" );
    }

    // Send back speaker as well.
    $ret['data'] = $speaker;
    return $ret;
}

?>
