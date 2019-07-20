<?php
require_once BASEPATH."autoload.php";
echo userHTML( );

echo "<h2>You are updating your upcoming AWS </h2>";

echo alertUser( "If you can't find supervisors/TCM members in drop down list,
    you have to go back and add them. Visit <a class=\"clickable\" 
    href=\"" . site_url("user/update_supervisors") . "\">this link</a>
    to add them.", false );

echo alertUser( '<strong>DO NOT COPY/PASTE from
    Office/Word/Webpage/etc.</strong> They often contain non-standard special
    characters. They can break my PDF convertor.  If you paste from other
    application, be sure to ckick on <tt>Tools -> Source code</tt> in editor
    below to see what has been pasted. Remove as much formatting as you can.
    Ideally, you should paste plain text and format it in the editor below to
    your heart desire.'
    , false
);

echo "<br>";

if( $_POST[ 'response' ] == 'update' )
{
    $awsId = $_POST[ 'id' ];
    $aws = getUpcomingAWSById( $awsId );

    echo '<form method="post" action="' . site_url("user/aws/update_upcoming_aws/submit").'">';
    echo editableAWSTable( -1, $aws );
    echo '<input type="hidden", name="id" value="' . $awsId . '">';
    echo '</form>';
}

echo goBackToPageLink( 'user/aws', 'Go back' );

?>
