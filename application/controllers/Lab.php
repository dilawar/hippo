<?php

require_once BASEPATH. 'autoload.php';

trait Lab 
{
    public function add_equipment( $arg = '' )
    {
        $_POST['edited_by'] = whoAmI();
        $_POST['last_modified_on'] = dbDateTime( 'now' );

        $updatable =  'name,vendor,description,last_modified_on,edited_by,status,person_in_charge';
        $res = insertOrUpdateTable('equipments', 'id,'.$updatable, $updatable, $_POST);
        if( !$res )
            echo printWarning( "Failed to add equipment.");
        else
            flashMessage( "Successfully added equipment." );
        redirect( "user/equipments" );
    }

    // ACTION
    public function delete_equipment( $equipmentID )
    {
        if( $_POST['response'] == 'DO_NOTHING' )
        {
            flashMessage( "User cancelled previous action." );
            redirect( "user/equipments");
            return;
        }

        $res = deleteFromTable( "equipments", 'id', array('id' => $equipmentID));
        if( $res )
            flashMessage( "Successfully deleted equipment id $equipmentID." );
        else
            printWarning( "Failed to delete equipment id $equipmentID.");

        redirect( "user/equipments");
    }

}

?>
