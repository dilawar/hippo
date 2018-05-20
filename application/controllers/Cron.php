<?php

require_once BASEPATH.'autoload.php';

class Cron extends CI_Controller {

    public function run( )
    {
        echo "Running cron";

        // Execute all scripts in ./views/controller/cron folder.
        $files = array( 'cron/aws_annoy.php'
            , 'cron/aws_annoy.php'
            , 'cron/aws_friday_notification.php'
            , 'cron/aws_friday_notify_faculty.php'
            , 'cron/aws_monday_morning.php'
            , 'cron/aws_schedule_fac_student.php'
            , 'cron/booking_expiring_notice.php'
            , 'cron/cleanup_database.php'
            , 'cron/events_everyday_morning.php'
            , 'cron/events_weekly_summary.php'
            , 'cron/jc_assign_n_weeks_in_advance.php'
            , 'cron/jc.php'
            , 'cron/lablist_every_two_months.php'
            , 'cron/sync_calendar.php'
        );

        foreach( $files as $i => $f )
        {
            echo printInfo("Executing $f");
            hippo_shell_exec( "php index.php cron $f" );
        }
    }

}

?>
