<?php

require_once BASEPATH . 'autoload.php';

function icalStamp(string $date, string $time) : string
{
    $dt = new \DateTime("$date $time");
    return $dt->format("Ymd\THis");
}

class Pub extends CI_Controller
{
    // no need for authentication (see HippoHooks.php)
    public function background()
    {
        // Return a random image.
        $filename = random_jpeg('temp/_backgrounds/');
        return $this->servepath($filename);
    }

    // no need for authentication (see HippoHooks.php)
    public function photographyclub_image($name)
    {
        $filename = getUploadDir() . '/' . $name;
        return $this->servepath($filename);
    }

    public function servepath($filename)
    {
        // Return a random image.
        if (file_exists($filename)) {
            $mime = mime_content_type($filename); //<-- detect file type
            header('Content-Length: ' . filesize($filename)); //<-- sends filesize header
            header("Content-Type: $mime"); //<-- send mime-type header
            header('Content-Disposition: inline; filename="' . $filename . '";'); //<-- sends filename header
            readfile($filename); //<--reads and outputs the file onto the output buffer
            die(); //<--cleanup
            exit; //and exit
        }
    }

    // Download the data of photographyclub.
    public function photographyclub_data(int $id = 0)
    {
        $where = "status='VALID'";

        $competitions = getTableEntries("photography_club_competition", "id", "status='VALID'");
if($id)  
            $where .= " AND competition_id='$id'";

        $entries = getTableEntries("photography_club_entry"
            , "competition_id,login"
            , $where);

        foreach($entries as &$entry) {
            $eid = $entry['id'];
            $entry['votes'] = getTableEntries('photography_club_rating', 'id'
                , "status='VALID' AND entry_id='$eid'", "star");
            $entry['url'] = site_url() . '/pub/photographyclub_image/' . basename($entry['filepath']);


        }

        header("Content-Type: application/json"); //<-- send mime-type header
        echo json_encode(["competitions"=>$competitions, "entries"=>$entries]);
        die();
        exit;
    }

    public function phpinfo()
    {
        echo phpinfo();
    }

    /**
        * @brief Generate calendar ical
        *
        * @return 
     */
    public function ical($gid, $eid)
    {
        // Get the event.
        if(! $gid) {
            return;
        }

        $event = getEventsById($gid, $eid);
        if(! $event) {
            $event = getRequestById($gid, $eid);
        }

        if(! $event) {
            echo "Nothing found for this URL";
            return;
        }

        $title = $event['title'];
        $description = $event['description'];
        $evStartDatetime = icalStamp($event['date'], $event['start_time']);
        $evEndDatetime = icalStamp($event['date'], $event['end_time']);
        $info = getLoginInfo($event['created_by']);
        $email = $info['email'];
        $cn = arrayToName($info);
        $location = venueSummary($event['venue']);

        $filename ="calendar-$gid-$eid.ics";

        header("Content-Type: text/calendar");
        header("Content-Disposition: inline; filename=$filename");
        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//NCBS Banglaore//NONSGML NCBS Hippo//EN\r\n";
        echo "METHOD:REQUEST\r\n"; // requied by Outlook
        echo "BEGIN:VTIMEZONE\r\n";
        echo "TZID:Asia/Bengaluru\r\n";
        echo "BEGIN:STANDARD\r\n";
        echo "TZOFFSETFROM:+0530\r\n";
        echo "TZOFFSETTO:+0530\r\n";
        echo "TZNAME:IST\r\n";
        echo "DTSTART:19700101T000000\r\n";
        echo "END:STANDARD\r\n";
        echo "END:VTIMEZONE\r\n";
        echo "BEGIN:VEVENT\r\n";
        echo "UID:".date('Ymd').'T'.date('His')."-$gid.$eid-hippo.ncbs.res.in\r\n"; // required by Outlok
        echo "DTSTAMP:".date('Ymd').'T'.date('His')."\r\n"; // required by Outlook
        echo "ORGANIZER;CN:$cn:MAILTO:$email\r\n"; // required by Outlook
        echo "DTSTART;TZID=Asia/Bengaluru:$evStartDatetime\r\n"; 
        echo "DTEND;TZID=Asia/Bengaluru:$evEndDatetime\r\n"; 
        echo "SUMMARY:$title\r\n";
        echo "DESCRIPTION:$description\r\n";
        echo "LOCATION:$location\r\n";
        echo "END:VEVENT\r\n";
        echo "END:VCALENDAR\r\n";
    }
}
