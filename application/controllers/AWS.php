<?php

trait AWS 
{
    public function aws( $args = '' )
    {
        echo $args;
        $this->template->load( 'header.php' );
        $this->template->load( 'user_aws' );
    }

}

?>
