<?php 

include_once( "header.php" );
include_once( "methods.php" );
include_once( 'tohtml.php' );
include_once( "check_access_permissions.php" );

mustHaveAnyOfTheseRoles( Array( 'USER' ) );

echo userHTML( );

$default = Array( );

if( $_POST['response'] == 'edit' )
{
    $rid = $_POST['id'];
    $default = getAwsRequestById( $rid );

    echo "<h3>Edit your AWS entry</h3>";

    echo "<p>
        NOTICE: If you can't find your supervior(s) and/or thesis committee member(s) 
        in selection list, please go back to your HOME and click on 
       <pre>Update TCM Members/Supervisors</pre> link.
    </p>";

    // Now create an entry
    $supervisors = getSupervisors( );
    $supervisorIds = Array( );
    $supervisorText = Array( );
    foreach( $supervisors as $supervisor )
    {
        array_push( $supervisorIds, $supervisor['email'] );
        $supervisorText[ $supervisor['email'] ] = $supervisor['first_name']
            .  ' ' . $supervisor[ 'last_name' ] ;
    }

    echo "<form method=\"post\" action=\"user_aws_request_edit_submit.php\">";
    echo "<table class=\"input\">";

    $abstract = sanitiesForTinyMCE( __get__( $default, 'abstract', '' ) );
    echo '
        <tr>
            <td>Title</td>
            <td><input type="text" class="long" name="title" value="' 
                . __get__( $default, 'title', '') . '" />
            </td>
        </tr>
        <tr>
            <td>Abstract </td>
            <td><textarea id="abstract" name="abstract" rows="10" cols="40">' 
                . $abstract . '</textarea>
                <script>
                    tinymce.init( { selector : "#abstract"
                            , init_instance_callback: "insert_content"
                        } );
                    function insert_content( inst ) {
                        inst.setContent( \'' . $abstract . '\');
                    }
                </script>
            </td>
        </tr>';

    for( $i = 1; $i <= 2; $i++ )
    {
        $name = "supervisor_$i";
        $selected = __get__( $default, $name, "" );
        echo '
        <tr>
            <td>Supervisor ' . $i . '<br></td>
            <td>' . arrayToSelectList( $name, $supervisorIds , $supervisorText, FALSE, $selected ) 
            .  '</td>
        </tr>';
    }
    for( $i = 1; $i <= 4; $i++ )
    {
        $name = "tcm_member_$i";
        $selected = __get__( $default, $name, "" );
        echo '
        <tr>
            <td>Thesis Committee Member ' . $i . '<br></td>
            <td>' . arrayToSelectList( $name, $supervisorIds , $supervisorText, FALSE, $selected) 
            .  '</td>
        </tr>';
    }

    echo '
        <tr>
            <td>Is Presynopsis Seminar?</td>
            <td>' .  arrayToSelectList( 'is_presynopsis_seminar', array( 'YES', 'NO') ) 
            . '</td>
        </tr>';

    echo '
        <tr>
            <td>Date</td>
            <td><input class="datepicker"  name="date" id="" value="' . 
                __get__($default, 'date', '' ) . '" /></td>
        </tr>
        <tr>
            <td>Time</td>
            <td><input class="timepicker" name="time" id="" value="16:00" /></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input  name="rid" type="hidden" value="' . $rid . '"  />
                <button class="submit" name=\"response\" value="submit">Submit</button>
            </td>
        </tr>
        ';
    echo "</table>";
    echo "</form>";
}
else if( $_POST['response'] == 'cancel' )
{
    $id = $_POST['id'];
    // Delete this request.
    $res = deleteFromTable( 'aws_requests', 'id', array( 'id' => $id ) );
    if( $res )
    {
        echo printInfo( 'Your request has been deleted successfully.' );
        goBack( );
        exit;
    }
    else
    {
        echo minionEmbarrassed( 'I could not cancel your request.' );
    }
}
else
{
    echo "Unknown request " . $_POST[ 'response' ];
}

echo goBackToPageLink( "user.php", "Go back" );

?>
