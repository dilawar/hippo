<?php

trait AdminacadJC
{

    public function jc_action( )
    {
        $response = strtolower($_POST['response']);
        if( $response == 'add' )
        {
            $res = insertIntoTable( 'journal_clubs'
                , 'id,title,day,status,time,venue,description'
                , $_POST
            );

            if( $res )
                flashMessage( "Added JC successfully" );
        }
        else if($response == 'update' )
        {
            $res = updateTable( 'journal_clubs'
                , 'id'
                , 'title,day,status,time,venue,description'
                , $_POST
            );

            if( $res )
                flashMessage( "Updated successfully" );
        }
        else if( $response == 'delete' )
        {
            $res = deleteFromTable( 'journal_clubs' , 'id' , $_POST );
            if( $res )
                flashMessage( "Updated deleted entry" );
        }
        else
            printWarning( "$response is not implemented yet.");

        redirect( "adminacad/jc");
    }

}

?>
