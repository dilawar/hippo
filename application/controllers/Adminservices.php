<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

class Adminservices extends CI_Controller
{

    function index()
    {
        $this->home();
    }

    public function load_admin_view( $view, $data = array() )
    {
        $data['controller'] = 'adminservices';



        $this->template->set( 'header', 'header.php' );
        $this->template->load( $view, $data );
    }

    public function home()
    {
        $this->load_admin_view( "user");
    }

    /************************* CANTEEN ****************************/
    public function canteen(string $action='', string $id='')
    {
        if( ! $action )
        {
            $data['cMealHtml'] = arrayToSelectList('which_meal', getTableColumnTypes('canteen_menu', 'which_meal'));
            $data['today'] = date('D', strtotime('now'));

            // Now show the menu.
            $items = getTableEntries('canteen_menu'
                , 'canteen_name,which_meal,available_from'
                , "status='VALID'");

            $itemGroupedByCanteen = [];
            $itemGroupedByDay = [];
            foreach( $items as $item)
            {
                $itemGroupedByCanteen[$item['canteen_name']][$item['day']][] = $item;
                $itemGroupedByDay[$item['day']][$item['canteen_name']][] = $item;
            }
            $data['cItemGroupedByCanteen'] = $itemGroupedByCanteen;
            $data['cItemGroupedByDay'] = $itemGroupedByDay;
            $this->load_admin_view( 'adminservices_manages_canteen', $data);
            return;
        }
        $this->manage_canteen($action, $id);
        return;
    }

    // Called from Api controller as well.
    public static function addToCanteenMenu($data)
    {
        $id = getUniqueID( 'canteen_menu' );
        $data['id'] = $id;
        $data['status'] = 'VALID';
        $data['modified_on'] = dbDateTime( 'now' );
        $data['description'] = __get__( $data, 'description', '' );
        $data['modified_by'] = implode(',', 
            array_slice(array_unique(explode(',', $data['modified_by'])), -10, 10)
            );

        $success = insertOrUpdateTable( 'canteen_menu'
            , 'id,name,description,price,which_meal,available_from,available_upto' 
                .  ',canteen_name,day,modified_by'
            , 'price,description,available_from,available_upto,modified_by,status'
                , $data);
        return $id;
    }

    public static function updateCanteenItem($data)
    {
        $success = updateTable( 'canteen_menu', 'id'
            , 'name,description,price,which_meal,available_from,available_upto' 
                . ',canteen_name,day,modified_by'
            , $data);
        return $success;
    }

    public static function deleteCanteenItem(int $id)
    {
        $success = updateTable('canteen_menu', 'id', 'status', ['id'=>$id, 'status'=> 'INVALID']);
        return $success;
    }

    public function manage_canteen(string $action, string $id='')
    {
        $success = null;
        if( $action === 'add' )
        {
            $days = explode(',', $_POST['days_csv']);
            $_POST['days'] = '';
            foreach( $days as $day )
            {
                $_POST['day'] = $day;
                $id = getUniqueID( 'canteen_menu', 'id');
                $_POST['id'] = $id;

                $modifiedBy = __get__($_POST, 'modified_by', '');
                if(strpos($modifiedBy, whoAmI()) === false)
                    $modifiedBy .= "," . whoAmI();

                $_POST['modified_by'] = $modifiedBy;
                $success = $this->addToCanteenMenu($_POST);
            }
            if($success)
                flashMessage("Successfully added entry");
        }
        else if($action === 'quickadd')
        {
            $menuItems = explode(';', $_POST['menu']);

            // First invalidate all items on the given meal, canteen_name.
            // $_POST['status'] = 'INVALID';
            // updateTable('canteen_menu', 'canteen_name,day,which_meal','status', $_POST);

            $_POST['status'] = 'VALID';
            $_POST['modified_by'] = __get__($_POST, 'modified_by', whoAmI());
            foreach( $menuItems as $item)
            {
                if( ! trim($item) )
                    continue;

                $item = explode('=', $item);
                if(count($item) != 2 )
                    continue;
                $_POST['name'] = trim($item[0]);
                $_POST['price'] = trim($item[1]);
                $success = $this->addToCanteenMenu($_POST);
            }
        }
        else if( $action === 'update' )
        {
            foreach( $days as $day )
            {
                $_POST['modified_by'] = implode(','
                    , array_unique(
                        explode(',', __get__($_POST, 'modified_by', ''))
                    )
                );
                $success = $this->updateCanteenItem($_POST);
            }
        }
        else if($action === 'delete')
        {
            $success = $this->deleteCanteenItem(intval($id));
        }
        else
            flashMessage( "$action is not implemented yet.");

        if($success)
            redirect("adminservices/canteen");
        else
            echo "Something went wrong?";
        return;
    }

    /************************* TRANSPORT ****************************/
    public function transport($action = '')
    {
        if( ! $action )
        {
            $this->load_admin_view( 'adminservices_manages_transport' );
            return;
        }
        $this->manage_transport($action);
        return;
    }

    public function manage_transport( $action )
    {
        $action = trim(strtolower($action));
        $goback = false;
        if( $action == 'quickupdate' )
        {
            $goback = true;
            $days = explode(',', $_POST['days']);
            $times = explode( ',', $_POST['trip_start_times']);
            $duration = __get__($_POST, 'duration', 30);
            $_POST['status'] = 'VALID';
            foreach( $days as $day)
            {
                foreach($times as $time)
                {
                    $_POST['trip_end_time'] = dbTime(strtotime($time)+60*intval($duration));
                    $_POST['day'] = $day;
                    $_POST['trip_start_time'] = $time;
                    $_POST['id'] = getUniqueID('transport');
                    $updatable = 'vehicle,pickup_point,drop_point,day,trip_start_time,trip_end_time,url,status';
                    $res = insertOrUpdateTable('transport', 'id,'.$updatable, $updatable, $_POST );
                    if(! $res )
                        $goback = false;
                }
            }
        }
        if( $action == 'quickdelete' )
        {
            $res = deleteFromTable('transport', 'vehicle,pickup_point,drop_point', $_POST);
            if($res)
                $goback = true;
        }
        elseif( $action === 'add')
        {
            if($this->add_transport())
                $goback = true;
        }
        else if($action === 'update')
            if( $this->update_transport() )
                $goback = true;

        if( $goback )
            redirect('/adminservices/transport');
    }

    public function add_transport( )
    {
        $id = getUniqueID('transport');
        $_POST['id'] = $id;
        $res = insertIntoTable('transport'
                    , 'id,vehicle,vehicle_no,pickup_point,drop_point,day,trip_start_time'
                    . ',trip_end_time,comment,status'
                    , $_POST
                );
        if($res)
        {
            flashMessage( "Successfully added new entry");
            return true;
        }
        else
        {
            flashMessage( "Failed to add new entry");
            return false;
        }
    }

    public function update_transport( )
    {
        var_dump($_POST);
        $res = updateTable('transport', 'id'
            , 'vehicle,vehicle_no,pickup_point,drop_point,day,trip_start_time'
            . ',trip_end_time,comment,status', $_POST);
        return $res;
    }

    public function delete_transport($id)
    {
        $res = deleteFromTable('transport', 'id', ['id'=>$id]);
        if($res)
        {
            flashMessage( "Deleted successfully" );
            redirect('/admin/transport');
        }
        else
            flashMessage( "Could not delete");
    }


}

?>
