<?php 
include_once( "header.php" );
include_once( "sqlite.php" );
include_once( "methods.php" );

$venues = getVenues( );

?>

<h3>Manage venues</h3>

<form action="edit_venues.php" method="post" accept-charset="utf-8">

<table id="table_input" border="0">
<tr>
    <td>Name</td><td><input name="name", type="text" ></td>
</tr>
<tr>
    <td>Location</td>
    <td> 
        <input name="location" , type="text">
    </td>
</tr>
<tr>
    <td>Strength</td><td><input type="text" name="strength" ></td>
</tr>
<tr>
    <td>Suitable for skype?</td>
    <td>
            <input name="hasConference" type="radio" value="Yes" >Yes
            <input name="hasConference" type="radio" value="No" checked>No
    </td>
</tr>
<tr>
    <td>Has projector?</td>
    <td>
        <input name="hasProjector" type="radio" value="Yes" >Yes
        <input name="hasProjector" type="radio" value="No" checked>No
    </td>
</tr>
<tr>
    <td>Type </td>
    <td><input type="text" value="" ></td>
</tr>
</table>

<tr>
<br><br>
<button name="response" type="submit" class="goback" value="Go back">Go back</button>
<button name="response" type="submit" class="update" value="Update">Update</button> 
</tr>

</form>
