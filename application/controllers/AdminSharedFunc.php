<?php

require_once BASEPATH . 'autoload.php';

function admin_update_talk($data)
{
    $res = updateTable(
        'talks',
        'id',
        'class,host,host_extra,coordinator,title,description',
        $data
    );

    if ($res) {
        // TODO: Update the request or event associated with this entry as well.
        $externalId = getTalkExternalId($data);

        $talk = getTableEntry('talks', 'id', $data);
        assert($talk);

        $success = true;

        $event = getEventsOfTalkId($data['id']);
        $request = getBookingRequestOfTalkId($data['id']);

        if ($event) {
            echo printInfo('Updating event related to this talk');
            $event['title'] = talkToEventTitle($talk);
            $event['description'] = $talk['description'];
            $res = updateTable('events', 'gid,eid', 'title,description', $event);
            if ($res) {
                echo printInfo('... Updated successfully');
            } else {
                $success = false;
            }
        } elseif ($request) {
            echo printInfo('Updating booking request related to this talk');
            $request['title'] = talkToEventTitle($talk);
            $request['description'] = $talk['description'];
            $res = updateTable('bookmyvenue_requests', 'gid,rid', 'title,description', $request);
        }
    }

    if (!$res) {
        printErrorSevere('Failed to update talk');

        return true;
    }
    flashMessage('Successfully updated entry');

    return true;
}

function admin_send_email(array $data): array
{
    $res = ['error' => '', 'message' => ''];

    $to = $data['recipients'];
    $msg = $data['email_body'];
    $cclist = $data['cc'];
    $subject = $data['subject'];

    $res['message'] = '<h2>Email content are following</h2>';
    $res['message'] .= $msg;
    $res['message'] .= printInfo("Sending email to $to ($cclist ) with subject $subject");

    $attachments = __get__($data, 'attachments', '');
    sendHTMLEmail($msg, $subject, $to, $cclist, $attachments);

    return $res;
}

function admin_update_speaker(array $data): array
{
    $final = ['message' => '', 'error' => ''];

    if ('DO_NOTHING' == $data['response']) {
        $final['error'] = 'User said do nothing.';

        return $final;
    }

    if ('delete' == $data['response']) {
        // We may or may not get email here. Email will be null if autocomplete was
        // used in previous page. In most cases, user is likely to use autocomplete
        // feature.
        if (strlen($data['id']) > 0) {
            $res = deleteFromTable('speakers', 'id', $data);
        } else {
            $res = deleteFromTable('speakers', 'first_name,last_name,institute', $data);
        }

        if ($res) {
            $final['message'] = 'Successfully deleted entry';
        } else {
            $final['error'] = minionEmbarrassed('Failed to delete speaker from database');
        }

        return $final;
    }

    if ('submit' == $data['response']) {
        $ret = addUpdateSpeaker($data);
        if ($ret['success']) {
            $final['message'] .= 'Updated/Inserted speaker. <br />' . $ret['msg'];
        } else {
            $final['error'] .= printInfo('Failed to update/insert speaker') . $ret['msg'];
        }

        return $final;
    }

    $final['error'] .= alertUser('Unknown/unsupported operation ' . $data['response']);

    return $final;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis  venue actions are shared between admin and bmvadmin.
 *
 * @Param $arg
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function admin_venue_actions(array $data, string &$msg): bool
{
    $response = __get__($data, 'response', '');
    $editables = 'name,institute,building_name,floor,location,type,strength,'
        . 'latitude,longitude,'
        . 'has_projector,suitable_for_conference,quota,has_skype'
        . ',allow_booking_on_hippo,note_to_user';

    $data['floor'] = intval(__get__($data, 'floor', '-1'));

    if ('update' == $response) {
        $res = updateTable('venues', 'id', $editables, $data);
        if ($res) {
            $msg = 'Venue ' . $data['id'] . ' is updated successful';

            return true;
        }
        $msg = 'Failed to update venue ' . $data['id '];

        return false;
    } elseif ('add new' == $response) {
        if (strlen(__get__($data, 'id', '')) < 2) {
            $msg = 'The venue id is too short to be legal.';

            return false;
        }
        $res = insertIntoTable('venues', "id,$editables", $data);
        if ($res) {
            $msg = 'Venue ' . $data['id'] . ' is successfully added.';

            return true;
        }
        $msg = 'Failed to add venue ' . $data['id '];

        return false;
    } elseif ('delete' == $response) {
        $res = deleteFromTable('venues', 'id', $data);
        if ($res) {
            $msg = 'Venue ' . $data['id'] . ' is successfully deleted.';

            return true;
        }
        $msg = 'Failed to add venue ' . $data['id '];

        return false;
    } elseif ('DO_NOTHING' == $response) {
        $msg = 'User said DO NOTHING. So going back!';

        return false;
    }
    $msg = "Unknown command from user $response.";

    return false;

    return false;
}

function admin_delete_booking()
{
    // Admin is deleting booking.
}

function admin_faculty_task($data): array
{
    $ret = ['success' => true, 'msg' => ''];
    $response = strtolower($data['response']);
    if ('update' == $response) {
        $data['modified_on'] = date('Y-m-d H:i:s', strtotime('now'));
        $res = updateTable(
            'faculty',
            'email',
            'first_name,middle_name,last_name,status' .
',modified_on,url,specialization,affiliation,institute',
            $data
        );

        if ($res) {
            $ret['success'] = true;
            $ret['msg'] .= 'Successfully updated faculty';
        } else {
            $ret['success'] = false;
            $res['msg'] .= 'I could not update faculty';
        }
    } elseif ('add' == $response) {
        $data['modified_on'] = date('Y-m-d H:i:s', strtotime('now'));
        $res = insertIntoTable(
            'faculty',
            'email,first_name,middle_name,last_name,status'
                . ',modified_on,url,specialization,affiliation,institute',
            $data
        );

        if ($res) {
            $ret['msg'] .= 'Successfully added a new faculty';
        } else {
            $ret['success'] = true;
            $ret['msg'] .= 'I could not edit new faculty.';
        }
    } elseif ('delete' == $response) {
        $res = deleteFromTable('faculty', 'email', $data);
        $ret['success'] = $res ? true : false;
        if ($res) {
            $ret['msg'] .= ('Successfully deteleted faculty.');
        } else {
            $ret['msg'] .= 'Failed to delete entry from table.';
        }
    } else {
        $ret['success'] = false;
        $ret['msg'] .= "Not implemented yet $response.";
    }

    return $ret;
}
