<?php

/** 
 * This does not require authentication. See HippoHooks.php
 */
class Confirm extends CI_Controller
{
    public function hash($arg)
    {
        $query = getTableEntry('queries', 'hash', ['hash'=>$arg]);
        if(! $query) {
            echo p("Invalid link.");
        }

        if($query['status'] !== 'PENDING') {
            echo p("This link has expired");
        }

        try {
            $r1 = executeQuery($query['query']);
        } catch (Exception $e) {
            echo p("Failed to execute. <br> Technical note: <br>" . $e->getMessage());
            return;
        }

        if($r1) {
            $query['status'] = 'EXECUTED';
            $r2 = updateThisTalk('queries', 'id', 'status', $query);
            if($r2) {
                echo p("Success");
            }
            else
                echo p("Failed");
        }
        else
            echo p("Failed to confirm.");
    }
}

?>
