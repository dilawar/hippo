<?php

require_once BASEPATH. 'autoload.php';

trait Lab 
{
    public function add_equipment( $arg = '' )
    {
        $_POST['edited_by'] = whoAmI();
        $_POST['last_modified_on'] = dbDateTime( 'now' );

        $res = insertIntoTable('equipments'
            , 'id,name,vendor,description,faculty_in_charge,person_in_charge,last_modified_on,edited_by,status'
            , $_POST 
        );
        if( !$res )
            echo printWarning( "Failed to add equipment.");
        else
            flashMessage( "Successfully added equipment." );

        redirect( "user/equipments" );
    }

}

?>
