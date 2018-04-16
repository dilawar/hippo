<?php

trait AWS 
{
    public function aws( $args = '' )
    {
        $this->template->load( 'header.php' );
        $this->template->load( 'user_aws' );
    }

}

?>
