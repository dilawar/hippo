<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once BASEPATH . 'autoload.php';

class Welcome extends CI_Controller
{
    public function loadview($view, $data = [])
    {
        $data['controller'] = 'welcome';
        $this->template->set('header', 'header.php');
        $this->template->load($view, $data);
    }

    public function index()
    {
        // Empty URI e.g. https://ncbs.res.in/hippo will redirect to
        // https://ncbs.res.in/hippo/welcome .
        if (!uri_string()) {
            redirect('welcome');
        }

        $this->loadview('index.php');
    }

    public function login()
    {
        $login = __get__($_POST, 'username', '');
        $pass = __get__($_POST, 'pass');

        if (!($login && $pass)) {
            echo flashMessage('Empty username or password!', 'error');
            redirect('welcome');

            return;
        }

        // If user use @instem.ncbs.res.in or @ncbs.res.in, ignore it.
        $ldap = explode('@', $login)[0];

        $this->session->set_userdata('AUTHENTICATED', false);
        $this->session->set_userdata('WHOAMI', $login);

        // Check if ldap is available. If it is use LDAP else fallback to imap based
        // authentication.
        $auth = authenticateUser($ldap, $pass);
        if (!$auth) {
            $this->session->set_flashdata('error', 'Loging unsucessful. Try again!');
        //redirect( "welcome" );
        } else {
            $this->session->set_userdata('AUTHENTICATED', true);
            $this->session->set_userdata('WHOAMI', $login);
            $ldapInfo = getUserInfoFromLdap($ldap);

            $email = '';
            $type = 'UNKNOWN';
            if ($ldapInfo) {
                $email = $ldapInfo['email'];
                $this->session->set_userdata('email', $email);
                $type = __get__($ldapInfo, 'title', 'UNKNOWN');
            }

            // In any case, create a entry in database.
            $this->createUserOrUpdateLogin($ldap, $ldapInfo, $type);

            // Update email id.
            $res = updateTable('logins', 'login', 'email', ['login' => $ldap, 'email' => $email]
            );

            $this->session->set_flashdata('success', 'Loging sucessful.!');
            redirect('user/home');
        }
    }

    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis  Create a new login on successful login. If login is already
     * found then update its details from LDAP.
     *
     * @Param $ldap
     * @Param $ldapInfo
     * @Param $type
     *
     * @Returns
     */
    /* ----------------------------------------------------------------------------*/
    private function createUserOrUpdateLogin($ldap, array $ldapInfo, $type = '')
    {
        if (!$ldapInfo) {
            $ldapInfo = @getUserInfoFromLdap($userid);
        }

        if ('NA' === __get__($ldapInfo, 'last_name', 'NA')) {
            $ldapInfo['last_name'] = '';
        }

        $data = [];
        foreach ($this->db->list_fields('logins') as $f) {
            // If ldap has this information then override it.
            // NOTE: If you don't want any other field to be updated then put
            // then ignore them here.
            if ('na' !== strtolower(__get__($ldapInfo, $f, 'na'))) {
                $data[$f] = $ldapInfo[$f];
            }
        }

        if (!$this->db->replace('logins', $data)) {
            flashMessage('Failed to update user. ' . $this->db->error());

            return false;
        }

        // update last login time.
        $this->db->set(['last_login' => dbDateTime('now')]);
        $this->db->where('login', $ldap);
        $this->db->update('logins');

        return true;
    }
}
