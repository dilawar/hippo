<?php

require_once BASEPATH . 'autoload.php';
require_once BASEPATH . 'extra/jc.php';

trait JC
{
    // VIEWS
    public function jc(string $arg = '', string $arg2 = '')
    {
        $this->load_user_view('user_jc');
    }

    public function jc_presentation_requests()
    {
        $this->load_user_view('user_manages_jc_presentation_requests');
    }

    public function jc_update_presentation()
    {
        if ('Acknowledge' == __get__($_POST, 'response', '')) {
            $_POST['acknowledged'] = 'YES';
            $res = updateTable('jc_presentations', 'id', 'acknowledged', $_POST);
            if ($res) {
                flashMessage('Successfully acknowleged  your JC presentation.');
            }
            redirect('user/jc');

            return;
        }

        $this->load_user_view('user_manages_jc_update_presentation');
    }

    // ACTION.
    public function jc_action(string $action)
    {
        if ('Unsubscribe' == $action) {
            $_POST['status'] = 'UNSUBSCRIBED';
            $res = updateTable(
                'jc_subscriptions', 'login,jc_id', 'status', $_POST
            );
            if ($res) {
                // Send email to jc-admins.
                $jcAdmins = getJCAdmins($_POST['jc_id']);
                $tos = implode(',', array_map(
                        function ($x) { return getLoginEmail($x['login']); }, $jcAdmins)
                    );
                $user = whoAmI();
                $subject = $_POST['jc_id'] . " | $user has unsubscribed ";
                $body = '<p> Above user has unsubscribed from your JC. </p>';
                sendHTMLEmail($body, $subject, $tos, 'jccoords@ncbs.res.in');
                flashMessage('Successfully unsubscribed from ' . $_POST['jc_id']);
            } else {
                flashMessage('Failed to unsubscribe from JC.');
            }
        } elseif ('Subscribe' == $action) {
            $_POST['status'] = 'VALID';
            $res = insertOrUpdateTable('jc_subscriptions', 'login,jc_id', 'status', $_POST);
            if ($res) {
                flashMessage('Successfully subscribed to ' . $_POST['jc_id']);
            }
        } else {
            flashMessage("unknown action $action.");
        }

        redirect('user/jc');
    }

    public function jc_update_action()
    {
        if ('Add My Vote' == __get__($_POST, 'response', '')) {
            $_POST['status'] = 'VALID';
            $_POST['voted_on'] = dbDate('today');
            $res = insertOrUpdateTable('votes', 'id,voter,voted_on', 'status,voted_on', $_POST);
            if ($res) {
                echo printInfo('Successfully voted.');
            }
        } elseif ('Remove My Vote' == __get__($_POST, 'response', '')) {
            $_POST['status'] = 'CANCELLED';
            $res = updateTable('votes', 'id,voter', 'status', $_POST);
            if ($res) {
                echo printInfo('Successfully removed  your vote.');
            }
        } elseif ('Save' == __get__($_POST, 'response', '')) {
            $res = updateJCPresentaiton($_POST);
            if ($res['success']) {
                echo printInfo('Successfully edited  your JC presentation.');
            }
        } else {
            echo alertUser('This action ' . $_POST['response'] . ' is not supported yet');
        }

        redirect('user/jc');
    }
}
