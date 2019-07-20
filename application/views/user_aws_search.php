<?php

require_once BASEPATH.'autoload.php';

// Show it only if accessed from intranet or user have logged in.
if( ! (isIntranet( ) || isAuthenticated( ) ) )
{
    echo printWarning( "To access this page, either use Intranet or log-in first" );
    echo closePage( );
    exit;
}


echo '<strong>Multiple keywords can be separated by , </strong>';
echo '<form action="" method="get" accept-charset="utf-8">
    <input type="text" name="query" value="" >
    <button type="submit" name="response" value="Search">Search</button>
    </form>
    ';

if( isset( $_GET[ 'query' ] ) )
{
    $query = $_GET['query' ];
    $query = implode( '%', explode( ',', $query ));

    $awses = queryAWS( $query );
    echo printInfo( "Total matches " .  count( $awses ) );
    foreach( $awses as $aws )
    {
        // Add user info to table.
        $aws['speaker'] = loginToHTML( $aws[ 'speaker' ], true );
        $aws['date'] = humanReadableDate( $aws[ 'date' ] );
        echo arrayToVerticalTableHTML( $aws, 'aws', ''
            , array( 'id', 'time', 'supervisor_2'
            , 'tcm_member_1', 'tcm_member_2', 'tcm_member_3', 'tcm_member_4'
        )
    );
        echo '<br>';
    }
}


echo goBackToPageLink( "info/aws", "Go back" );

?>
