<?php

require_once FCPATH . './cron/helper.php';
require_once FCPATH . './cron/update_database.php';
require_once FCPATH . './cron/aws_annoy.php';
require_once FCPATH . './cron/aws_friday_notification.php';
require_once FCPATH . './cron/aws_friday_notify_faculty.php';
require_once FCPATH . './cron/aws_monday.php';
require_once FCPATH . './cron/aws_schedule_fac_student.php';
require_once FCPATH . './cron/booking_expiring_notice.php';
require_once FCPATH . './cron/events_everyday_morning.php';
require_once FCPATH . './cron/events_weekly_summary.php';
require_once FCPATH . './cron/jc_assign_n_weeks_in_advance.php';
require_once FCPATH . './cron/jc.php';
require_once FCPATH . './cron/lablist_every_two_months.php';
require_once FCPATH . './cron/sync_calendar.php';
require_once FCPATH . './cron/remove_old_aws_schedule.php';

class Cron extends CI_Controller
{
    public function run()
    {
        // Execute all scripts in ./views/controller/cron folder.
        $tasks = [
             'update_database', 'aws_annoy', 'aws_friday_notification', 'aws_friday_notify_faculty', 'aws_monday', 'aws_schedule_fac_student', 'booking_expiring_notice', 'events_everyday_morning', 'events_weekly_summary', 'jc_assign_n_weeks_in_advance', 'jc', 'lablist_every_two_months', 'sync_calendar',
        ];

        foreach ($tasks as $i => $t) {
            echo printInfo("Running cron job for task $t");

            try {
                hippo_shell_exec("php index.php cron $t", $stdout, $stderr);
                echo printInfo($stderr);
                echo printInfo($stdout);
            } catch (Exception $e) {
                $body = p(" Hippo could not finish a scheduled task '$t' successfully.");
                $body .= p('Error was ' . $e->getMessage());
                sendHTMLEmail(
                    $body,
                    'WARN! Hippo failed to do a routine task (cron)',
                    'hippo@lists.ncbs.res.in'
                );
            }
        }
    }

    public function update_database()
    {
        update_database_cron();
        update_publishing_database();
    }

    public function aws_annoy()
    {
        aws_annoy_cron();
    }

    public function aws_friday_notification()
    {
        aws_friday_notification_cron();
    }

    public function aws_friday_notify_faculty()
    {
        aws_friday_notify_faculty_cron();
    }

    public function aws_monday()
    {
        if (trueOnGivenDayAndTime('this monday', '10:00')) {
            aws_monday_morning_cron();
        }

        if (trueOnGivenDayAndTime('this monday', '11:00')) {
            notifyAcadOfficeUnassignedSlot();
        }

        // Try at 6pm.
        if (trueOnGivenDayAndTime('this monday', '18:00')) {
            bookVenueForAWS();
        }
    }

    public function aws_schedule_fac_student()
    {
        aws_schedule_fac_student_cron();
    }

    public function booking_expiring_notice()
    {
        booking_expiring_notice_cron();
    }

    public function events_everyday_morning()
    {
        events_everyday_morning_cron();
    }

    public function events_weekly_summary()
    {
        events_weekly_summary_cron();
    }

    public function jc_assign_n_weeks_in_advance()
    {
        jc_assign_n_weeks_in_advance_cron();
    }

    public function remove_old_aws_prefs()
    {
        if (trueOnGivenDayAndTime('this sunday', '14:15')) {
            remove_old_aws_scheduling_prefs();
        }
    }

    public function jc()
    {
        jc_cron();
    }

    public function lablist_every_two_months()
    {
        lablist_every_two_months_cron();
    }

    public function sync_calendar()
    {
        sync_calendar_cron();
    }
}
