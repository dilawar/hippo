<?php

require_once BASEPATH . 'autoload.php';

trait AWS
{
    public function aws(string $arg = '', string $arg2 = '')
    {
        $user = whoAmI();
        $scheduledAWS = scheduledAWSInFuture($user);
        $tempScheduleAWS = temporaryAwsSchedule($user);
        $userAWSData = ['cUser' => whoAmI(), 'cScheduledAWS' => $scheduledAWS, 'cTempScheduleAWS' => $tempScheduleAWS,
        ];

        if ('schedulingrequest' == strtolower($arg)) {
            if ('create' == $arg2) {
                $this->template->load('header.php');
                $this->template->load('user_aws_scheduling_request');
            } elseif ('submit' == $arg2) {
                // Submit the given request.
                $_POST['speaker'] = whoAmI();
                $login = whoAmI();

                // Check if preferences are available.
                $firstPref = __get__($_POST, 'first_preference', '');
                $secondPref = __get__($_POST, 'second_preference', '');
                $keys = 'id,speaker,reason,created_on';
                $updateKeys = 'created_on,reason';

                // check if dates are monday. If not assign next monday.
                $firstPref = nextMonday($firstPref);
                $secondPref = nextMonday($secondPref);

                if ($firstPref) {
                    $prefDate = dbDate($firstPref);
                    if (strtotime('next monday') >= strtotime($prefDate)) {
                        echo printInfo('I can not change the past without Time Machine. 
                        Ignoring ' . humanReadableDate($prefDate));
                    } else {
                        $upcomingAWSs = getTableEntries(
                            'upcoming_aws', 'date', "date='$prefDate'"
                        );
                        if (3 == count($upcomingAWSs)) {
                            echo printInfo("Date $prefDate is not available. Ignoring ...");
                        } else {
                            $keys .= ',first_preference';
                            $updateKeys .= ',first_preference';
                        }
                    }
                }

                if ($secondPref) {
                    $prefDate = dbDate($secondPref);
                    if (strtotime('next monday') >= strtotime($prefDate)) {
                        echo printInfo('I can not change the past without Time Machine. 
                        Ignoring ' . humanReadableDate($prefDate));
                    } else {
                        $upcomingAWSs = getTableEntries(
                            'upcoming_aws', 'date', "date='$prefDate'"
                        );

                        if (3 == count($upcomingAWSs)) {
                            echo printInfo("Date $prefDate is not available. Ignoring ...");
                        } else {
                            $keys .= ',second_preference';
                            $updateKeys .= ',second_preference';
                        }
                    }
                }

                $updateKeys .= ',status';
                $_POST['status'] = 'PENDING';
                $_POST['created_on'] = dbDateTime('now');
                $res = insertOrUpdateTable('aws_scheduling_request', $keys, $updateKeys, $_POST);

                if ($res) {
                    $_POST['id'] = $res['id'];
                } else {
                    $sendEmail = false;
                }

                // Create subject for email
                $subject = 'Your preferences for AWS schedule has been received';
                $msg = '<p>Dear ' . loginToText($login) . '</p>';
                $msg .= '<p>Your scheduling request has been logged. </p>';
                $msg .= arrayToVerticalTableHTML($_POST, 'info', null, 'response');
                $email = getLoginEmail($login);
                sendHTMLEmail($msg, $subject, $email, 'hippo@lists.ncbs.res.in');
                $this->load_user_view('user_aws', $userAWSData);
            } elseif ('delete' == strtolower(trim($arg2))) {
                // Cancel the scheduling request.
                $table = getTableEntry('aws_scheduling_request', 'id', $_POST);
                if ($table) {
                    $_POST = array_merge($_POST, $table);
                } else {
                    echo 'No entry found';
                }

                $_POST['status'] = 'CANCELLED';
                $res = updateTable('aws_scheduling_request', 'id', 'status', $_POST);
                if ($res) {
                    $subject = 'You have cancelled your AWS preference';
                    $this->session->set_flashdata('success', 'Successfully cancelled');
                }
                $this->load_user_view('user_aws', $userAWSData);
            } elseif ($arg2) {
                // Unknown action 2.
                echo "Unknown action $arg2";
            } else {
                $this->load_user_view('user_aws', $userAWSData);
            }
        } elseif ('update_upcoming_aws' == strtolower(trim($arg))) {
            if ('submit' == strtolower(trim($arg2))) {
                $res = updateTable('upcoming_aws', 'id', 'supervisor_1,supervisor_2,tcm_member_1,tcm_member_2,tcm_member_3' .
                    ',tcm_member_4,title,abstract,is_presynopsis_seminar', $_POST
                );

                if ($res) {
                    echo flashMessage('Successfully updated entry');
                }
                redirect('user/aws');
            } else {
                // By default go to view.
                $this->load_user_view('user_aws_update_upcoming_aws.php');
            }
        } elseif ('edit_request' == strtolower(trim($arg))) {
            $this->load_user_view('user_aws_edit_request');

            return;
        } elseif ('edit_request_create' === strtolower($arg)) {
            $_POST['speaker'] = whoAmI();
            $res = insertIntoTable('aws_requests', 'speaker,title,abstract,supervisor_1,supervisor_2'
                 . 'tcm_member_1,tcm_member_2,tcm_member_3,tcm_member_4'
                 . 'is_presynopsis_seminar,date,time', $_POST
            );
            if ($res) {
                echo flashMessage('Successfully created a request to edit AWS details ');
            } else {
                echo minionEmbarrassed('I could not create a request to edit your AWS');
            }

            $this->load_user_view('user_aws', $userAWSData);
        } elseif ('edit_request_cancel' === strtolower($arg)) {
            $_POST['speaker'] = whoAmI();
            $_POST['status'] = 'CANCELLED';
            $res = updateTable('aws_requests', 'id', 'status', $_POST);
            if ($res) {
                echo flashMessage('Successfully cancelled edit request.');
                $this->load_user_view('user_aws', $userAWSData);
            }
        } else {
            if ($arg) {
                flashMessage("Unnown action $arg", 'error');
            }
            $this->load_user_view('user_aws', $userAWSData);
        }
    }

    public function aws_acknowledge()
    {
        $user = whoAmI();
        $data = ['speaker' => $user];
        $data = array_merge($_POST, $data);
        echo  'Sending your acknowledgment to database ';
        $res = updateTable('upcoming_aws', 'id,speaker', 'acknowledged', $data);

        $msg = '';
        if ($res) {
            $msg .= p(
                'You have successfully acknowledged your AWS schedule. 
                Please mark your calendar as well.'
            );

            $email = '<p>' . loginToHTML($user) . ' has just acknowledged his/her AWS date. </p>';
            $email .= '<p>' . humanReadableDate('now') . '</p>';
            $subject = loginToText($user) . ' has acknowledged his/her AWS date';

            $to = 'acadoffice@ncbs.res.in';
            $cc = 'hippo@lists.ncbs.res.in';
            sendHTMLEmail($email, $subject, $to, $cc);
        } else {
            printWarning('Failed to update database ...');
        }

        redirect('user/aws');
    }
}
