<?php
require_once BASEPATH."autoload.php";
echo userHTML( );

$myJCS = getMyJCs( );
$myJCIds = getValuesByKey( $myJCS, 'jc_id' );
$jcSelect = arrayToSelectList( 'jc_id', $myJCIds, array(), false, $myJCIds[0] );

$default = array( 
        'presenter' => whoAmI()
        , 'jc_id' => $jcSelect
        , 'id' => __get__( $_POST, 'id', getUniqueID( 'jc_requests' ) )
    );


// On this page below, we let user edit the entry here only.
if( __get__( $_POST, 'response', '' ) == 'Edit' )
{
    $default = array_merge( $default, $_POST );
}


echo '<h1> Submit a presentation request </h1>';

echo printInfo( "Make sure to add 'Why paper is interesting to be presented to
        community?' in <tt>DESCRIPTION</tt> field.  When done editing, press " . 
        goBackToPageLinkInline( "user/jc", 'Go Back' ) . " button."
    );

// Make a form.
$editables = 'jc_id,title,date,description,url';
echo ' <form action="" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable( 'jc_requests', $default, $editables );
echo '</form>';

if( __get__( $_POST, 'response', '' ) == 'submit' )
{
    $_POST[ 'status' ] = 'VALID';
    $res = insertOrUpdateTable( 'jc_requests'
        , 'id,jc_id,presenter,date,title,description,url'
        , 'jc_id,title,description,date,status,url'
        , $_POST
    );

    if( $res )
        echo printInfo( 'Successfully added/updated your entry' );
}
else if( __get__( $_POST, 'response', '' ) == 'delete' )
{
    $id = $_POST[ 'id' ];
    $data[ 'id' ] = $id;
    $data[ 'status' ] = 'CANCELLED';
    $res = updateTable( 'jc_requests', 'id', 'status', $data);
    if( $res )
        echo printInfo( "Your request has been cancelled/invalidated." );
}
echo goBackToPageLink( 'user/jc', 'Go Back' );

echo '<h1>My presentation requests </h1>';
$me = whoAmI( );

$requests = getTableEntries( 'jc_requests', 'date'
    , "status='VALID' AND presenter='$me'"
);

echo '<table class="info">';
foreach( $requests as $i => $req )
{
    echo '<tr>';
    echo '<td colspan="2">';
    $req['votes'] = count( getVotes( 'jc_requests.' . $req['id'] ) );

    echo ' <form action="" method="post" accept-charset="utf-8">';
    // set last argument to true to has hidden value in table.
    echo arrayToVerticalTableHTML( $req, 'info', '', '', true );
    echo '</tr><tr>';
    echo "<td><button name='response' onclick='AreYouSure(this)'
            title='Cancel this request'>Cancel</button> </td>";
    echo ' <td> <button name="response" value="Edit">Edit</button> </td>';
    echo '</tr>';
    echo '</form>';

}
echo '</table>';
echo ' <br /> ';

echo goBackToPageLink( 'user/jc', 'Go Back' );

?>
