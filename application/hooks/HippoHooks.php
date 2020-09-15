<?php

require_once BASEPATH . 'extra/check_access_permissions.php';

class HippoHooks
{
    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function PreController()
    {
        $class = $this->CI->router->fetch_class();
        if ('api' === $class || 'pub' === $class) {
            return;
        }
        if ('info' === $class) {
            $page = basename($_SERVER['PHP_SELF']);
            $page = str_replace('.php', '', $page);
            if ('photography_club' === $page || 'photographyclub_image') {
                return;
            }

            // Just check we are inside intranet.
            if (!(isAuthenticated() || isIntranet())) {
                echo flashMessage("To access this page ($page), either login first or use intranet.");
                redirect('welcome');

                return;
            }

            return;
        }

        // If user is already is authenticated but somehow come to welcome page
        // etc. Move this to home.
        if ($this->CI->session->AUTHENTICATED) {
            $page = basename($_SERVER['PHP_SELF']);
            if ('index.php' === $page || 'welcome' === $page) {
                // Already authenticated. Send to user
                redirect('user/home');
            } elseif ('login' === $page) {
                return;
            }
        } else {
            $page = basename($_SERVER['PHP_SELF']);
            $page = str_replace('.php', '', $page);

            if(! in_array($page, ['index', 'rss', 'welcome', 'login', 'aws', 'confirm'])){
                $this->CI->session->set_flashdata(
                    'error',
                    'You are not authenticated yet. Please login and try again'
                );
                redirect('welcome');

                return;
            }
        }
    }
}
