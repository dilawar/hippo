<?php

require_once BASEPATH.'autoload.php';
require_once FCPATH.'/cron/cleanup_database.php';

class Cron extends CI_Controller {

    public function run( )
    {
        echo "Running cron";

        // Execute all scripts in ./views/controller/cron folder.
        $tasks = array( 'aws_annoy'
            , 'aws_annoy'
            , 'aws_friday_notification'
            , 'aws_friday_notify_faculty'
            , 'aws_monday_morning'
            , 'aws_schedule_fac_student'
            , 'booking_expiring_notice'
            , 'cleanup_database'
            , 'events_everyday_morning'
            , 'events_weekly_summary'
            , 'jc_assign_n_weeks_in_advance'
            , 'jc'
            , 'lablist_every_two_months'
            , 'sync_calendar'
        );

        foreach( $files as $i => $f )
        {
            hippo_shell_exec( "php index.php $f", $stdout, $stderr );
            echo printInfo($stderr);
            echo printInfo( $stdout );
        }
    }

    public function cleanup_database( )
    {
        cleanup_database_cron();
    }

}

?>
