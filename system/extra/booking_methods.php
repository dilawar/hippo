<?php
require BASEPATH . 'database.php';

function getRecurrentPatternOfThisRequest( string $gid ): string
{
    // If this request has any recurrent pattern associated with it in the
    // table 'recurrent_pattern', we process them here.
    $recurrentPat = getTableEntry( 'recurrent_pattern'
                        , 'request_gid', [ 'request_gid' => $gid ]
                );
    return __get__( $recurrentPat, 'pattern', '');
}

function getAssociatedRecurrentBooking( string $gid ): array
{
    return getTableEntries( 'bookmyvenue_requests', 'rid'
        , "gid='$gid' AND status='PENDING'"
    );
}

function generateSubRequestsTable( string $gid, string $repeat_pattern = '' ): array
{
    $someMissing = false;
    $subreqs = getAssociatedRecurrentBooking( $gid );
    if( count( $subreqs ) <= 1 )
        return [ 'are_some_missing' => $someMissing, 'html' => '' ];

    // Interesting part. We check the recurrent pattern and find out the date we
    // may have missed.
    $days = repeatPatToDays( $repeat_pattern );
    $subreqsMap = [];
    foreach( $subreqs as $req )
        $subreqsMap[ $req['date'] ] = $req;

    $table = '<table class="tiles"><tr>' ;
    foreach( $days as $i => $day ) 
    {
        $date = dbDate( $day );
        $r = __get__( $subreqsMap, $date, null );
        $status = '<i class="fa fa-check">BOOKED</i>';

        if( ! $r )
        {
            $status = colored( '<i class="fa fa-times">NOT BOOKED</i>', 'red' );
            $someMissing = true;
        }
        
        $row = "$day $status";
        $table .= "<td> $row </td> ";

        if( ($i + 1) % 7 == 0 )
            $table .= '</tr><tr>';
    }

    $table .= '</tr></table>';
    return [ 'are_some_missing' => $someMissing, 'html' => $table ];
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Find if there is any missing entry with this request.
    *
    * @Param $gid
    *
    * @Returns  array with two keys: are_some_missing set to true or false and a
    * table showing the summary of status.
 */
/* ----------------------------------------------------------------------------*/
function areThereAMissingRequestsAssociatedWithThisGID( string $gid ) : array
{
    return generateSubRequestsTable( $gid );
}

?>
