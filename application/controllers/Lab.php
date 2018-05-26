<?php

require_once BASEPATH. 'autoload.php';

trait Lab 
{
    // VIEWS
    public function equipments( )
    {
        $this->load_user_view( "user_manages_equipments");
    }

    public function browse_equipments( )
    {
        $this->load_user_view( "user_browse_equipments" );
    }

    public function add_equipment( $arg = '' )
    {
        $_POST['edited_by'] = whoAmI();
        $_POST['last_modified_on'] = dbDateTime( 'now' );

        $personInCharge = $_POST['person_in_charge'];
        if( ! findAnyoneWithEmail( $personInCharge) )
        {
            printWarning( "I could not locate <tt>PERSON IN CHARGE</tt> '$personInCharge' in my
                database. I won't allow this entry. Use a valid email." 
                );
            redirect( "user/equipments");
            return;
        }

        $updatable =  'name,vendor,description,last_modified_on,edited_by,status,person_in_charge';
        $res = insertOrUpdateTable('equipments', 'id,faculty_in_charge,'.$updatable
            , $updatable, $_POST);
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

    public function book_equipment( )
    {
        $errorMsg = '';
        foreach( array("equipment_id", "date", "start_time", "end_time") as $k )
            if( ! __get__($_POST, $k, null) )
                $errorMsg .= "Error: No $k is selected. <br />";

        if( isDateAndTimeIsInPast( $_POST['date'], $_POST['start_time'] ) )
        {
            $errorMsg .= 'You are trying to book in the past. Date ' . $_POST['date'] 
                . ' and time ' . $_POST['start_time'] . '.';
        }

        if( $errorMsg )
        {
            printWarning( $errorMsg );
            redirect( 'user/browse_equipments');
            return;
        }

        // Everything is fine. Just book it.
        $res = insertIntoTable( 'equipment_bookings'
                , 'id,equipment_id,date,start_time,end_time,booked_by,comment'
                , $_POST 
            );

        if($res)
            flashMessage( "Booked succesfully but I did not send any email.");

        redirect( "user/browse_equipments");
    }

}

?>
