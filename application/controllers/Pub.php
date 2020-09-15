<?php

require_once BASEPATH . 'autoload.php';

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

    // Download the data.
    public function photographyclub_data(int $id = 0)
    {
        $where = "status='VALID'";

        $competitions = getTableEntries("photography_club_competition", "id", "status='VALID'");
if($id)  
            $where .= " AND competition_id='$id'";

        $entries = getTableEntries("photography_club_entry"
            , "competition_id,login"
            , $where);

        foreach($entries as &$entry) 
            $entry['url'] = site_url() . '/pub/photographyclub_image/' . basename($entry['filepath']);

        header("Content-Type: application/json"); //<-- send mime-type header
        echo json_encode(["competitions"=>$competitions, "entries"=>$entries]);
        die();
        exit;
    }

    public function phpinfo()
    {
        echo phpinfo();
    }
}
