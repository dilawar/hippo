<?php

require_once BASEPATH . 'autoload.php';

class Errors extends CI_Controller
{
    // NOTE: Checking for permission is in pre-hook.

    public function loadview($view, $data = [])
    {
        $data['controller'] = 'info';
        $this->template->set('header', 'header.php');
        $this->template->load($view);
    }

    public function page_missing($arg = '')
    {
        $req = $_SERVER['REDIRECT_URL'];
        $uri = explode('/', $req);
        $page = end($uri);
        if ('allevents.php' == $page) {
            redirect('info/booking');

            return;
        } elseif ('rss.php' == $page) {
            redirect('feed/rss');

            return;
        } elseif ('info.php' == $page) {
            redirect('info/info');

            return;
        } elseif ('execute.php' == $page) {
            $id = $_GET['id'];
            redirect("user/execute/$id");
        }

        flashMessage("Page you have requested is not found <tt>$req</tt>.");
        redirect('welcome');
    }
}
