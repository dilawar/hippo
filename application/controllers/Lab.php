<?php

require_once BASEPATH. 'autoload.php';

function checkEquipmentBookingRequest( array &$request )
{
    $errorMsg = '';
    foreach( array("equipment_id", "date", "start_time", "end_time") as $k )
        if( ! __get__($request, $k, null) )
            $errorMsg .= "Error: No $k is selected. <br />";

    if( isDateAndTimeIsInPast( $request['date'], $request['start_time'] ) )
    {
        $errorMsg .= 'You are trying to book in the past. Date ' . $request['date'] 
            . ' and time ' . $request['start_time'] . '.';
    }
    return $errorMsg;
}

function checkEquipmentMultibookingRequest( array &$request )
{
    $errorMsg = '';
    foreach( array("equipment_id", "day_pattern", "start_time", "end_time") as $k )
        if( ! __get__($request, $k, null) )
            $errorMsg .= "Error: No $k is selected. <br />";

    return $errorMsg;
}


function isEquipmentAlreadyBooked( $eid, $date, $start_time, $end_time ) : array
{
    $res = executeQuery( "SELECT * FROM inventory_bookings WHERE
            date='$date' AND NOT (start_time >= '$end_time' OR end_time <= '$start_time')
            AND status='VALID' AND equipment_id='$eid'" );
    if($res)
       return $res[0];
    return array();
}

trait Lab 
{
    // VIEWS
    public function inventory_manage( )
    {
        $this->load_user_view( "user_inventory_manage");
    }

    public function inventory_browse( )
    {
        $this->load_user_view( "user_inventory_browse" );
    }

    public static function add_inventory_item_helper($data) : array
    {
        $res = ['status'=>true, 'msg'=>''];

        // $_POST['edited_by'] = whoAmI();
        $data['last_modified_on'] = dbDateTime( 'now' );
        $personInCharge = $data['person_in_charge'];

        if(! findAnyoneWithLoginOrEmail($personInCharge))
        {
            $res['msg'] = printWarning( "I could not locate <tt>PERSON IN CHARGE</tt> '$personInCharge' in my
                database. I won't allow this entry. Use a valid email." 
                );
            $res['status'] =false;
            return $res;
        }

        $data['faculty_in_charge'] = getPIOrHost($data['person_in_charge']);

        $updatable =  'name,scientific_name,vendor,description,last_modified_on,edited_by,';
        $updatable .= 'status,item_condition,location,person_in_charge,requires_booking';

        $res['status'] = insertOrUpdateTable('inventory'
            , 'id,faculty_in_charge,'.$updatable
            , $updatable, $data
        );
        return $res;
    }

    public static function lend_inventory(array $data) : array
    {
        $data['inventory_id'] = $data['id'];
        $borrower = $data['borrower'];
        if( ! findAnyoneWithLoginOrEmail($borrower) )
            return ['status'=>false, 'msg'=>"I could not find anyone with login/email $borrower"];

        $id = getUniqueID('borrowing');
        $data['id'] = $id;
        $data['borrowed_on'] = dbDateTime('now');
        $data['status'] = 'VALID';
        $data['created_on'] = dbDateTime('now');

        $res = insertOrUpdateTable( 'borrowing'
            , 'id,inventory_id,borrower,borrowed_on,lender,status,created_on'
            , 'inventory_id,borrower,borrowed_on,lender,status,created_on'
            , $data);

        if($res)
            return ['status'=>true, 'msg'=>'Success'];

        return ['status'=>false, 'msg'=>'Failed to lend'];
    }

    public function add_inventory_item( $arg = '' )
    {
        $res = $this->add_inventory_item_helper($_POST);
        if( ! $res['status'] )
            echo printWarning( "Failed to add inventory intem." + $res['msg']);
        else
            flashMessage( "Successfully added inventory item.." );
        redirect( "user/inventory_manage" );
    }

    // ACTION
    public function delete_inventory( $itemID )
    {
        if( $_POST['response'] == 'DO_NOTHING' )
        {
            flashMessage( "User cancelled previous action." );
            redirect( "user/inventory_manage");
            return;
        }

        $res = deleteFromTable( "inventory", 'id', array('id' => $itemID));
        if( $res )
            flashMessage( "Successfully deleted equipment id $itemID." );
        else
            printWarning( "Failed to delete equipment id $itemID.");

        redirect( "user/inventory_manage");
    }

    public function book_equipment( )
    {
        $errorMsg = checkEquipmentBookingRequest( $_POST );
        $eid = $_POST['equipment_id'];
        $date = $_POST['date'];
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];

        $eq = isEquipmentAlreadyBooked( $eid, $date, $startTime, $endTime );
        if( $eq )
            $errorMsg = "This equipment has already been 
                booked by " . $eq['booked_by'] . '. <br />' . arrayToTableHTML( $eq, 'info' );

        if( $errorMsg )
        {
            printWarning( $errorMsg );
            redirect( 'user/browse_equipments');
            return;
        }

        // Everything is fine. Just book it.
        $res = insertIntoTable( 'inventory_bookings'
                , 'id,equipment_id,date,start_time,end_time,booked_by,comment'
                , $_POST 
            );

        if($res)
            flashMessage( "Booked succesfully but I did not send any email.");

        redirect( "user/browse_equipments");
    }

    public function multibook_equipment()
    {
        $errorMsg = checkEquipmentMultibookingRequest( $_POST );
        if( $errorMsg )
        {
            printWarning( $errorMsg );
            redirect( 'user/browse_equipments');
            return;
        }

        $dayPat = splitAtCommonDelimeters( $_POST['day_pattern'] );
        $dates = array();
        for ($i = 0; $i < intval($_POST['num_repeat']); $i++) 
        {
            foreach( $dayPat as $day )
                $dates[] = dbDate( strtotime("this $day +$i week") );
        }

        $msg = '';
        foreach( $dates as $date )
        {
            $eid = $_POST['equipment_id'];
            $startTime = $_POST['start_time'];
            $endTime = $_POST['end_time'];

            $eq = isEquipmentAlreadyBooked( $eid, $date, $startTime, $endTime );
            if( $eq )
                $msg .= "This equipment is already taken by " . $eq['booked_by'] . '. <br />' 
                    . arrayToTableHTML( $eq, 'info' ) . ' <br />';
            else
            {
                $id = getUniqueID( 'inventory_bookings');
                $_POST['date'] = $date;
                $_POST['id'] = $id;
                $_POST['booked_by'] = whoAmI();
                $res = insertIntoTable( "inventory_bookings"
                        , 'id,equipment_id,vendor,date,start_time,end_time,booked_by,comment'
                        , $_POST 
                    );
                if( $res )
                    $msg .= "Successfully booked for $date with id $id. <br />";
            }
        }
        flashMessage( $msg );
        redirect( 'user/inventory_browse');
    }

    public function cancel_equipment_booking( $id )
    {
        $res = updateTable( 'inventory_bookings', 'id', 'status'
            , array( 'id' => $id, 'status' => 'CACELLED' )
        );
        if($res)
            flashMessage( "Successfully cancelled booking.");

        redirect('user/inventory_browse');
    }

    public function cancel_equipment_bookings( $eid )
    {
        $res = updateTable( 'inventory_bookings', 'equipment_id,booked_by'
            , 'status'
            , array( 'equipment_id' => $eid, 'status' => 'CANCELLED', 'booked_by' => whoAmI())
        );

        if($res)
            flashMessage( "Successfully removed all bookings equipment with id $eid." );

        redirect( "user/inventory_browse" );
    }

}

?>
