<?php

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis  Send email to user on successful booking.
 *
 * @Param $gid  group id of request.
 * @Param $login login of user.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function sendBookingEmail(string $gid, array $request, string $login): array
{
    $repeatPat = trim(__get__($request, 'repeat_pat', ''));
    $userInfo = getLoginInfo($login);
    $userEmail = $userInfo['email'];

    $data = ['USER' => loginToText($login)];
    $rgroup = getRequestByGroupId($gid);
    $data['BOOKING_REQUEST'] = arrayToVerticalTableHTML($rgroup[0], 'request');

    // add the recurrent pattern to table recurrent_pattern.
    $patData = ['id' => getUniqueID('recurrent_pattern'), 'request_gid' => $gid, 'pattern' => $repeatPat,
    ];
    $res = insertIntoTable('recurrent_pattern', 'id,request_gid,pattern', $patData);

    if (count($rgroup) > 0) {
        $data['NUMBER_OF_REQUESTS'] = count($rgroup);
        $subject = "Your booking request (id-$gid) has been received";
        $template = emailFromTemplate('BOOKING_NOTIFICATION', $data);

        $body = $template['email_body'];

        // Now check if some bookings are not made.
        $res = areThereAMissingRequestsAssociatedWithThisGID($gid);
        if ($res['are_some_missing']) {
            $body = printWarning('I failed to book on some dates.');
            $body .= $res['html'];
            $body .= $warn;
        }
        sendHTMLEmail($body, $subject, $userEmail, $template['cc']);
        $msg = p('Your booking request has been submitted.');
    } else {
        $msg = p(
            'Something went wrong. No event could be generated OR
            I could not register your booking request. 
            Please write to hippo@lists.ncbs.res.in if this is my mistake!'
        );
    }

    return ['success' => true, 'msg' => $msg];
}

/**
 * @Synopsis  Same as submitRequest but slighly modernised for API class. We
 * did not touched the submitRequest function because it might break the
 * working interface in old website.
 *
 * TODO: Retire submitRequest function and use this one.
 *
 * @Param $request Array containing all params.
 *
 * @Returns  An array containing status and error message if any.
 */
/* ----------------------------------------------------------------------------*/
function submitBookingRequest(array $request, string $login): array
{
    $ret = ['success' => true, 'msg' => 'ok'];

    $hippoDB = initDB();
    $collision = false;

    if ('HIPPO' === __get__($request, 'created_by', $login)) {
        $res['success'] = false;
        $res['msg'] .= p('Could not figure out who is trying to create request.');

        return $res;
    }

    // Check if $request contains 'dates'. If not then check 'repeat_pat'
    $days = __get__($request, 'dates', []);
    if ((!$days) || 0 == count($days)) {
        $repeatPat = trim(__get__($request, 'repeat_pat', ''));
        if (isRepeatPatternValid($repeatPat) > 0) {
            $days = repeatPatToDays($repeatPat, $request['date']);
        } else {
            $days = [$request['date']];
        }
    }

    // Add current date to days. In case, the repeat pattern was bad, we might
    // miss the current date.clean-webpack-plugin
    if (!in_array($request['date'], $days)) {
        $days[] = $request['date'];
    }

    if (count($days) < 1) {
        $ret['msg'] .= p('I could not find any date in your request.');
        $ret['success'] = false;

        return $ret;
    }

    $rid = 0;
    $res = $hippoDB->query('SELECT MAX(gid) AS gid FROM bookmyvenue_requests');
    $prevGid = $res->fetch(PDO::FETCH_ASSOC);
    $gid = intval($prevGid['gid']) + 1;

    $errorMsg = '';
    $nBookings = 0;
    foreach ($days as $day) {
        ++$rid;
        $request['gid'] = $gid;
        $request['rid'] = $rid;
        $request['date'] = $day;

        $collideWith = checkCollision($request);
        $hide = 'rid,external_id,description,is_public_event,url,modified_by';

        if ($collideWith) {
            $errorMsg .= 'Collision with following event/request';
            foreach ($collideWith as $ev) {
                $errorMsg .= arrayToTableHTML($ev, 'events', $hide);
            }
            $collision = true;

            continue;
        }

        $request['timestamp'] = dbDateTime('now');
        $res = insertIntoTable('bookmyvenue_requests', 'gid,rid,external_id,created_by,venue,title,vc_url,description' .
                ',date,start_time,end_time,timestamp,is_public_event,class', $request
        );

        if (!$res) {
            $errorMsg .= "Could not submit request id $gid";
            $ret['msg'] .= p($errorMsg);
            $ret['success'] = false;

            return $ret;
        }

        ++$nBookings;
    }
    $ret['success'] = true;
    $ret['msg'] .= p($errorMsg);
    $ret['num_bookings'] = $nBookings;
    $ret['gid'] = $gid;

    // On success, send email to the user.
    try {
        sendBookingEmail($gid, $request, $login);
    } catch (Exception $e) {
        $ret['msg'] .= p('Failed to send emai. Error: ' . $e->getMessage());
    }

    return $ret;
}
