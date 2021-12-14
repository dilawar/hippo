<?php

require_once BASEPATH . 'extra/methods.php';

function lablist_every_two_months_cron()
{
    /* --------------------------------------------------------------------------*/
    /**
     * @Synopsis  Every two months, first Saturday, 10a.m.; notify each faculty
     * about their AWS candidates.
     */
    /* ----------------------------------------------------------------------------*/
    $intMonth = intval(date('m', strtotime('today')));
    __log__("lablist_every_two_months");

    // Nothing to do on odd months.
    if (0 == $intMonth % 2) 
    {
        $year = getCurrentYear();
        $month = date('M', strtotime('today'));

       if (trueOnGivenDayAndTime("Second Saturday of $month", '15:00')) 
        {
            echo('Second saturday of even month. Update PIs about AWS list');

            $speakers = getAWSSpeakers();
            $facultyMap = [];
            foreach ($speakers as $speaker) {
                $login = $speaker['login'];
                $pi = getPIOrHost($login);
                if ($pi) {
                    $facultyMap[$pi] = __get__($facultyMap, $pi, '') . ',' . $login;
                }
            }

            // Now print the names.
            foreach ($facultyMap as $fac => $speakers) {
                if (count($speakers) < 1) {
                    continue;
                }

                $table = '<table border="1">';
                foreach (explode(',', $speakers) as $login) {
                    if (!trim($login)) {
                        continue;
                    }

                    $speaker = loginToHTML($login, true);
                    $table .= " <tr> <td>$speaker</td> </tr>";
                }
                $table .= '</table>';

                $faculty = arrayToName(findAnyoneWithEmail($fac));
                $email = emailFromTemplate('NOTIFY_SUPERVISOR_AWS_CANDIDATES', ['FACULTY' => $faculty, 'LIST_OF_AWS_SPEAKERS' => $table, 'TIMESTAMP' => dbDateTime('now')]
                );

                $body = $email['email_body'];
                $cc = $email['cc'];
                $subject = 'List of AWS speakers from your lab';
                $to = $fac;
                sendHTMLEmail($body, $subject, $to, $cc, '', ['acadoffice@ncbs.res.in', 'Academic Office']);
            }
        }
    }
}
