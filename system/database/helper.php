<?php

require_once BASEPATH. "extra/methods.php";
require_once BASEPATH. 'extra/ldap.php';
require_once BASEPATH. 'database/base.php';
require_once BASEPATH. 'extra/me.php';
require_once BASEPATH. 'extra/courses.php';

// Construct the PDO
$hippoDB = new BMVPDO("localhost");
$hippoDB->initialize();


/**
 * Replaces any parameter placeholders in a query with the value of that
 * parameter. Useful for debugging. Assumes anonymous parameters from
 * $params are are in the same order as specified in $query
 *
 * @param  string $query  The sql query with parameter placeholders
 * @param  array  $params The array of substitution parameters
 * @return string The interpolated query
 *
 * This is from https://stackoverflow.com/a/8403150/1805129
 */
function interpolateQuery($query, $params)
{
    $keys = array();

    // build a regular expression for each parameter
    foreach ($params as $key => $value) {
        if (is_string($key)) {
            $keys[] = '/:'.$key.'/';
        } else {
            $keys[] = '/[?]/';
        }
    }

    $query = preg_replace($keys, $params, $query, 1, $count);

    // trigger_error('replaced '.$count.' keys');

    return $query;
}


function initDB()
{
    global $hippoDB;
    if (! $hippoDB) {
        // Construct the PDO
        $hippoDB = new BMVPDO();
        $hippoDB->initialize();
    }
    return $hippoDB;
}

/**
 * @brief Return a sorted array out of choices.
 *
 * @param $choices
 * @param $key
 * @param $default
 * @param $sorted
 *
 * @return
 */
function getChoicesFromGlobalArray($choices, $key, $default = 'UNKNOWN', $sorted = true)
{
    $choicesSplit = array_filter(explode(',', __get__($choices, $key, '')));

    if ($sorted) {
        sort($choicesSplit);
    }

    // Remove the default one and add the default at the front.
    $results = array_diff($choicesSplit, array( $default ));
    array_unshift($results, $default);

    return array_unique($results);
}

function getEventsOfTalkId($talkId)
{
    $externalId = getTalkExternalId($talkId);
    $entry = getTableEntry(
        'events',
        'external_id,status',
        array( 'external_id' => "$externalId" , 'status' => 'VALID' )
    );
    return $entry;
}

function getBookingRequestOfTalkId($talkId)
{
    $externalId = getTalkExternalId($talkId);
    $entry = getTableEntry(
        'bookmyvenue_requests',
        'external_id,status',
        ['external_id'=>"$externalId", 'status'=>'PENDING']
    );
    return $entry;
}

function getTalkIDs($start_date, $end_date)
{
    return executeQuery(
        "SELECT external_id, date, start_time, end_time, venue FROM events WHERE
            status='VALID' AND date>'$start_date' AND date<'$end_date'
            AND external_id LIKE 'talks.%'
        "
    );
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Find talks on a given date.
 *
 * @Param $date
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getTalksOnThisDay($date)
{
    $talkIDS = executeQuery(
        "SELECT external_id, date, start_time, end_time, venue FROM events WHERE
            status='VALID' AND date='$date' AND external_id LIKE 'talks.%'
        "
    );
    $results = array();
    foreach ($talkIDS as $i => $tev) {
        $row = array();
        $exID = explode('.', $tev['external_id'])[1];
        $talk = getTableEntry('talks', 'id', array( 'id' => $exID ));
        $results[] = ['talk'=>$talk, 'booking'=>$tev];
    }
    return $results;
}

function getTalksWithEvent($start_date, $end_date)
{
    $talkIDS = getTalkIDs($start_date, $end_date);
    $results = array();
    foreach ($talkIDS as $i => $tev) {
        $row = array();
        $exID = explode('.', $tev['external_id'])[1];
        $talk = getTableEntry('talks', 'id', array( 'id' => $exID ));
        $row = array_merge($row, $talk);
        $row['date'] = $tev['date'];
        $row['start_time'] = $tev['start_time'];
        $row['venue'] = $tev['venue'];
        $results[] = $row;
    }
    return $results;
}

/**
 * @brief It does the following tasks.
 *  1. Move the entruies from upcoming_aws to annual_work_seminars lists.
 *
 * @return
 */
function doAWSHouseKeeping()
{
    $oldAws = getTableEntries('upcoming_aws', 'date', "status='VALID' AND date < CURDATE()");
    $badEntries = array( );
    foreach ($oldAws as $aws) {
        if (strlen($aws[ 'title' ]) < 1 || strlen($aws[ 'abstract' ]) < 1) {
            array_push($badEntries, $aws);
            continue;
        }

        $res1 = insertIntoTable(
            'annual_work_seminars',
            'speaker,date,time,supervisor_1,supervisor_2' .
                ',tcm_member_1,tcm_member_2,tcm_member_3,tcm_member_4' .
                ',title,abstract,is_presynopsis_seminar',
            $aws
        );

        if ($res1) {
            $res2 = deleteFromTable('upcoming_aws', 'id', $aws);
            if (! $res2) {
                array_push($badEntries, $aws);
            }
        } else {
            $badEntries[] =  $aws;
            echo printWarning("Could not move entry to main AWS list");
        }
    }
    return $badEntries;
}

function getVenues($sortby = 'total_events DESC, id')
{
    // Sort according to total_events hosted by venue
    $res = executeQuery("SELECT * FROM venues ORDER BY $sortby");
    return $res;
}

function getVenuesNames($type='')
{
    $query = "SELECT id FROM venues ORDER BY id";
    if ($type) {
        $query .= " WHERE type='$type'";
    }

    $res = executeQuery($query);
    $names = array();
    foreach ($res as $v) {
        $names[] = $v['id'];
    }
    return $names;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Execute given query.
 *
 * @Param $query
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function executeQuery(string $query, $onlyOne = false)
{
    if (strlen(trim($query)) < 1) {
        return [];
    }

    $hippoDB = initDB();
    $res = $hippoDB->query($query);
    $n = -1;
    if ($onlyOne) {
        $n = 1;
    }
    return fetchEntries($res, $n);
}

function executeQueryReadonly($query)
{
    $hippoDB = initDB();
    return $hippoDB->query($query);
}

function executeURlQueries($query)
{
    $hippoDB = initDB();
    $res = $hippoDB->query($query);
    return $res;
}

function getVenuesByType($type)
{
    $where = "type='$type'";
    if ($type === 'all') {
        $where = '';
    }
    $venues = getTableEntries('venues', 'id', $where);
    $res = [];
    foreach ($venues as $v) {
        $v['summary'] = venueSummary($v, false);
        $res[$v['id']] = $v;
    }
    return $res;
}

function getVenuesByTypes(string $csvtypes) : array
{
    $res = [];
    foreach (explode(',', $csvtypes) as $vtype) {
        $res = array_merge($res, getVenuesByType($vtype));
    }
    return $res;
}

function getTableSchema($tableName)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare("DESCRIBE $tableName");
    $stmt->execute();
    return fetchEntries($stmt);
}

function getVenuesGroupsByType()
{
    // Sort according to total_events hosted by venue
    $venues = getVenues();
    $newVenues = array( );
    foreach ($venues as $venue) {
        $vtype = $venue['type'];
        if (! array_key_exists($vtype, $newVenues)) {
            $newVenues[ $vtype ] = array();
        }
        array_push($newVenues[$vtype], $venue);
    }
    return $newVenues;
}

// Return the row representing venue for given venue id.
function getVenueById($venueid)
{
    $hippoDB = initDB();
    ;
    $venueid = trim($venueid);
    $stmt = $hippoDB->prepare("SELECT * FROM venues WHERE id=:id");
    $stmt->bindValue(':id', $venueid);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPendingRequestsOnThisDay($date)
{
    $requests = getTableEntries(
        'bookmyvenue_requests',
        'date,start_time',
        "date='$date' AND status='PENDING'"
    );
    return $requests;
}

// Get all requests which are pending for review.
function getPendingRequestsGroupedByGID()
{
    return getRequestsGroupedByGID('PENDING');
}

// Get all requests with given status.
function getRequestsGroupedByGID($status='PENDING')
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        'SELECT * FROM bookmyvenue_requests
        WHERE status=:status AND date>=CURDATE() GROUP BY gid ORDER BY date,start_time'
    );
    $stmt->bindValue(':status', $status);
    $stmt->execute();
    return fetchEntries($stmt);
}

// Get all events with given status.
function getEventsByGroupId($gid, $status = null, $from='')
{
    $hippoDB = initDB();
    ;
    $query = "SELECT * FROM events WHERE gid=:gid";
    if ($status) {
        $query .= " AND status=:status ";
    }
    if ($from) {
        $query .= " AND date>=:date ";
    }
    $query .= " ORDER BY date, start_time ";

    $stmt = $hippoDB->prepare($query);
    $stmt->bindValue(':gid', $gid);
    if ($status) {
        $stmt->bindValue(':status', $status);
    }
    if ($from) {
        $stmt->bindValue(':date', dbDate($from));
    }
    $stmt->execute();
    return fetchEntries($stmt);
}

//  Get a event of given gid and eid. There is only one such event.
function getEventsById($gid, $eid)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare('SELECT * FROM events WHERE gid=:gid AND eid=:eid');
    $stmt->bindValue(':gid', $gid);
    $stmt->bindValue(':eid', $eid);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * @brief Get list of requests made by this users. These requests must be
 * newer than the current date minus 2 days and time else they won't show up.
 *
 * @param $userid
 * @param $status
 *
 * @return
 */
function getRequestOfUser($userid, $status = 'PENDING')
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        'SELECT * FROM bookmyvenue_requests WHERE created_by=:created_by
        AND status=:status AND date >= CURDATE() - INTERVAL 2 DAY
        ORDER BY date,start_time'
    );
    $stmt->bindValue(':created_by', $userid);
    $stmt->bindValue(':status', $status);
    $stmt->execute();
    return fetchEntries($stmt);
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Return request grouped by gid and count as well.
 *
 * @Param $userid
 * @Param $status
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getRequestOfUserGroupedAndWithCount($userid, $status = 'PENDING')
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        'SELECT *, COUNT(*) AS total_events FROM bookmyvenue_requests WHERE created_by=:created_by
        AND status=:status AND date >= CURDATE() - INTERVAL 2 DAY
        GROUP BY gid ORDER BY date,start_time'
    );
    $stmt->bindValue(':created_by', $userid);
    $stmt->bindValue(':status', $status);
    $stmt->execute();
    return fetchEntries($stmt);
}


function getEventsOfUserGrouped($userid, $from = 'today', $status = 'VALID')
{
    $hippoDB = initDB();
    ;
    $from = dbDate($from);
    $stmt = $hippoDB->prepare(
        "SELECT * FROM events WHERE 
        (created_by='$userid' OR created_by LIKE '$userid@%')
        AND date >= '$from'
        AND status='$status'
        GROUP BY gid
        ORDER BY date,start_time"
    );
    $stmt->execute();
    return fetchEntries($stmt);
}


function getEventsOfUser($userid, $from = 'today', $status = 'VALID')
{
    $hippoDB = initDB();
    ;
    $from = dbDate($from);
    $stmt = $hippoDB->prepare(
        "SELECT * FROM events WHERE 
        (created_by='$userid' OR created_by LIKE '$userid@%')
        AND date >= '$from'
        AND status='$status'
        ORDER BY date,start_time"
    );
    $stmt->execute();
    return fetchEntries($stmt);
}

/**
 * @brief Get all approved events starting from given date and duration.
 *
 * @param $from
 * @param $duration
 *
 * @return
 */
function getEventsBetween($from, $duration)
{
    $startDate = dbDate($from);
    $endDate = dbDate(strtotime($duration, strtotime($from)));
    $whereExpr = "date >= '$startDate' AND date <= '$endDate'";
    $whereExpr .= " AND status='VALID' ";
    return getTableEntries('events', 'date,start_time', $whereExpr);
}

function getEventsBetweenDates($fromDate, $endDate)
{
    $startDate = dbDate($fromDate);
    $endDate = dbDate($endDate);
    $whereExpr = "date >= '$startDate' AND date <= '$endDate'";
    $whereExpr .= " AND status='VALID' ";
    return getTableEntries('events', 'date,start_time', $whereExpr);
}


// Fetch entries from database response object
function fetchEntries($res, int $n = -1, $how = PDO::FETCH_ASSOC) : array
{
    $array = array( );
    if ($res) {
        while ($row = $res->fetch($how)) {
            $row = array_map('utf8_encode', $row);
            $array[] = $row;
            if (count($array) == $n) {
                break;
            }
        }
    }
    return $array;
}

// Get the request when group id and request id is given.
function getRequestById(string $gid, string $rid) : array
{
    return getTableEntry('bookmyvenue_requests', 'gid,rid', ['gid'=>$gid, 'rid'=>$rid]);
}

// Return a list of requested with same group id.
function getRequestByGroupId($gid)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare('SELECT * FROM bookmyvenue_requests WHERE gid=:gid');
    $stmt->bindValue(':gid', $gid);
    $stmt->execute();
    return fetchEntries($stmt);
}

// Return a list of requested with same group id and status
function getRequestByGroupIdAndStatus($gid, $status)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare('SELECT * FROM bookmyvenue_requests WHERE gid=:gid AND status=:status');
    $stmt->bindValue(':gid', $gid);
    $stmt->bindValue(':status', $status);
    $stmt->execute();
    return fetchEntries($stmt);
}

/**
 * @brief Change the status of request.
 *
 * @param $requestId
 * @param $status
 *
 * @return true on success, false otherwise.
 */
function changeRequestStatus($gid, $rid, string $status)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "UPDATE bookmyvenue_requests SET
        status=:status,last_modified_on=NOW() WHERE gid=:gid AND rid=:rid"
    );
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':gid', $gid);
    $stmt->bindValue(':rid', $rid);
    return $stmt->execute();
}

/**
 * @brief Change status of all request identified by group id.
 *
 * @param $gid
 * @param $status
 *
 * @return
 */
function changeStatusOfRequests($gid, $status)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare("UPDATE bookmyvenue_requests SET status=:status WHERE gid=:gid");
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':gid', $gid);
    return $stmt->execute();
}

function changeStatusOfEventGroup($gid, $user, $status)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "UPDATE events SET status=:status WHERE
        gid=:gid AND created_by=:created_by"
    );
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':gid', $gid);
    $stmt->bindValue(':created_by', $user);
    return $stmt->execute();
}

function changeStatusOfEvent($gid, $eid, $status)
{
    $res = updateTable(
        'events',
        'gid,eid',
        'status',
        array( 'gid' => $gid, 'eid' => $eid, 'status' => $status )
    );
    return $res;
}

/**
 * @brief Get the list of upcoming events.
 */
function getEvents($from = 'today', $status = 'VALID', int $limit=-1, int $offset=-1)
{
    $date = dbDate($from);
    return getTableEntries(
        'events',
        'date,start_time',
        "date >= '$date' AND status='$status'",
        '*',
        $limit,
        $offset
    );
}


/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Find events by GID.
 *
 * @Param $from
 * @Param $status
 * @Param $limit
 * @Param $offset
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getEventsGID(string $from='today', string $status='VALID', int $limit=-1, int $offset=-1)
{
    $date = dbDate($from);
    $query = "SELECT *,count(eid) as total
        FROM events 
        WHERE status='$status' AND date>='$date' 
            GROUP BY gid
            ORDER BY date,start_time
        ";
    if ($limit > 0) {
        $query .= " LIMIT $limit";
    }
    if ($offset >= 0) {
        $query .= " OFFSET $offset";
    }
    return executeQuery($query);
}

/**
 * @brief Get the list of upcoming events grouped by gid.
 */
function getEventsGrouped($sortby = '', $from = 'today', $status = 'VALID')
{
    $hippoDB = initDB();
    ;
    $sortExpr = '';

    $sortby = explode(',', $sortby);
    if (count($sortby) > 0) {
        $sortExpr = 'ORDER BY ' . implode(', ', $sortby);
    }

    $nowTime = dbTime($from);
    $stmt = $hippoDB->prepare(
        "SELECT * COUNT(eid) as TOTAL FROM events WHERE date >= :date
            AND status=:status GROUP BY gid $sortExpr"
    );
    $stmt->bindValue(':date', $nowTime);
    $stmt->bindValue(':status', $status);
    $stmt->execute();
    return fetchEntries($stmt);
}

/**
 * @brief Get the list of upcoming events.
 */
function getPublicEvents($from = 'today', $status = 'VALID', $ndays = 1)
{
    $hippoDB = initDB();
    ;
    $from = dbDate($from);
    $end = dbDate(strtotime($from . " +$ndays day"));
    $stmt = $hippoDB->prepare(
        "SELECT * FROM events WHERE date >= :date AND
        date <= :end_date AND
        status=:status AND is_public_event='YES' ORDER BY date,start_time"
    );
    $stmt->bindValue(':date', $from);
    $stmt->bindValue(':end_date', $end);
    $stmt->bindValue(':status', $status);
    $stmt->execute();
    return fetchEntries($stmt);
}

function getPublicEventsNum(string $from, int $limit=10, int $offset=0)
{
    $hippoDB = initDB();
    return getTableEntries(
        'events',
        'date,start_time',
        "date >= '$from' AND status='VALID' AND is_public_event='YES'",
        '*',
        $limit,
        $offset
    );
}

/**
 * @brief Get list of public event on given day.
 *
 * @param $date
 * @param $status
 *
 * @return
 */
function getPublicEventsOnThisDay($date = 'today', $status = 'VALID')
{
    $hippoDB = initDB();
    ;
    $date = dbDate($date);
    $stmt = $hippoDB->prepare(
        "SELECT * FROM events WHERE date = :date AND
        status=:status AND is_public_event='YES' ORDER BY date,start_time"
    );
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':status', $status);
    $stmt->execute();
    return fetchEntries($stmt);
}

function getEventsOn($day, $status = 'VALID')
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM events
        WHERE status=:status AND date = :date ORDER BY date,start_time"
    );
    $stmt->bindValue(':date', $day);
    $stmt->bindValue(':status', $status);
    $stmt->execute();
    return fetchEntries($stmt);
}

function getEventsOnThisVenueOnThisday($venue, $date, $status = 'VALID')
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM events
        WHERE venue=:venue AND status=:status AND date=:date ORDER
            BY date,start_time"
    );
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':venue', $venue);
    $stmt->execute();
    return fetchEntries($stmt);
}

/**
 * @brief get overlapping requests or events.
 *
 * @param $venue
 * @param
 * @param $start_time
 * @param
 * @param $status
 *
 * @return
 */
function getEventsOnThisVenueBetweenTime($venue, $date, $start_time, $end_time, $status = 'VALID'): array
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM events
        WHERE venue=:venue AND status=:status AND date=:date AND status='VALID'
        AND ( (start_time < :start_time AND end_time > :start_time )
              OR ( start_time < :end_time AND end_time > :end_time )
              OR ( start_time >= :start_time AND end_time <= :end_time )
            )
        "
    );
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':start_time', $start_time);
    $stmt->bindValue(':end_time', $end_time);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':venue', $venue);
    $stmt->execute();
    return fetchEntries($stmt);
}

function getRequestsOnThisVenueOnThisday($venue, $date, $status = 'PENDING')
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM bookmyvenue_requests
        WHERE venue=:venue AND status=:status AND date=:date"
    );
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':venue', $venue);
    $stmt->execute();
    return fetchEntries($stmt);
}

function getRequestsOnThisVenueBetweenTime($venue, $date, $start_time, $end_time, $status='PENDING'): array
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM bookmyvenue_requests
        WHERE venue=:venue AND status=:status AND date=:date
        AND ( (start_time < :start_time AND end_time > :start_time )
              OR ( start_time < :end_time AND end_time > :end_time )
              OR ( start_time >= :start_time AND end_time <= :end_time )
            )
        "
    );
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':start_time', $start_time);
    $stmt->bindValue(':end_time', $end_time);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':venue', $venue);
    $stmt->execute();
    return fetchEntries($stmt);
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get requests + confirmed events between two dates. Optionally
 * filter by created_by and venue.
 *
 * @Param $from Starting date.
 * @Param $to End date.
 * @Param $createdBy (optional) who created it.
 * @Param $venue  (optional) venue.
 *
 * @Returns List of requests and events.
 */
/* ----------------------------------------------------------------------------*/
function getAllBookingsBetweenTheseDays(string $from, string $to, $createdBy='', $venue='')
{
    $extra = '';
    if (trim($venue)) {
        $extra .= " AND venue='$venue' ";
    }
    if (trim($createdBy)) {
        $extra .= " AND created_by='$createdBy' ";
    }

    $events = getTableEntries(
        'events',
        'date',
        "status='VALID' AND date >= '$from' AND date <= '$to'" . $extra,
        "class,title,description,date,venue,created_by,start_time,end_time,url"
    );

    $requests = getTableEntries(
        'bookmyvenue_requests',
        'date',
        "status='PENDING' AND date >= '$from' AND date <= '$to'" . $extra,
        "class,title,status,description,date,venue,created_by,start_time,end_time,url"
    );
    $events = array_merge($events, $requests);
    usort($events, 'cmp_datetime');
    return $events;
}

function getNumBookings(int $num, int $limit)
{
    $from = dbDate('today');
    $now = dbTime(strtotime('now'));
    $events = getTableEntries(
        'events',
        'date,end_time', // we gonna usort is later
        "status='VALID' AND TIMESTAMP(date, end_time) >= NOW()",
        "class,title,status,description,date,venue,created_by,start_time,end_time,url,vc_url",
        $num,
        $limit
    );
    $requests = getTableEntries(
        'bookmyvenue_requests',
        'date,end_time', // we gonna usort it later.
        "status='PENDING' AND TIMESTAMP(date, end_time) >= NOW()",
        "class,title,status,description,date,venue,created_by,start_time,end_time,url,vc_url",
        $num,
        $limit
    );
    $events = array_merge($events, $requests);
    usort($events, 'cmp_datetime');
    return $events;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Returns all requests and requests on this venue on the given
 * day/time.
 *
 * @Param $venue
 * @Param $date
 * @Param $time
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getVenueBookingsOnDateTime($venue, $date, $time, $endTime)
{
    $req = getRequestsOnThisVenueBetweenTime($venue, $date, $time, $endTime);
    $evs = getEventsOnThisVenueBetweenTime($venue, $date, $time, $endTime);
    return array_merge($req, $evs);
}

/**
 * @brief Get number of entries of a given column.
 *
 * @param $tablename
 * @param $column
 *
 * @return
 */
function getNumberOfEntries($tablename, $column = 'id')
{
    $hippoDB = initDB();
    ;
    $res = $hippoDB->query("SELECT MAX($column) AS $column FROM $tablename");
    return $res->fetch(PDO::FETCH_ASSOC);
}

function getUniqueFieldValue($tablename, $column='id'): int
{
    $hippoDB = initDB();
    ;
    $res = $hippoDB->query("SELECT MAX($column) AS $column FROM $tablename");
    $res = $res->fetch(PDO::FETCH_ASSOC);
    return intval(__get__($res, $column, -1))+1;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get unique ID for a table.
 *
 * @Param $tablename
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getUniqueID($tablename)
{
    $column = 'id';
    $hippoDB = initDB();
    $res = $hippoDB->query("SELECT MAX($column) AS $column FROM $tablename");
    $res = $res->fetch(PDO::FETCH_ASSOC);
    return intval(__get__($res, $column, 0)) + 1;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Submit a booking request. Optionally remove any collision.
 *
 * @Param $request. Array representing request.
 * @Param $removeCollision. 
 *   If true, remove all collisions and notify the booking party.
 * @Param $reason.
 *    Reason when removing collision. This message will be sent to the 
 *    booking party.
 *
 * @Returns
 *    array: with `success`, `msg`, and `collision` keys.
 */
/* ----------------------------------------------------------------------------*/
function submitRequestImproved(array $request, bool $removeCollision=false, string $reason='') : array
{
    $result = ['msg'=>'', 'success'=>false, 'collision'=>[], 'gid'=>null, 'ridlist'=>[]];

    $request['created_by'] = $request['created_by'] ?? whoAmI();
    $repeatPat = __get__($request, 'repeat_pat', '');
    if (strlen($repeatPat) > 0) {
        $days = repeatPatToDays($repeatPat, $request[ 'date' ]);
    } else {
        $days = [$request['date']];
    }

    if (count($days) < 1) {
        $result['msg'] .= p("I could not generate list of slots for you reuqest");
        return $result;
    }

    $gid = getUniqueFieldValue('bookmyvenue_requests', 'gid');
    $result['gid'] = $gid;
    $result['rid'] = '0';
    $errorMsg = '';
    $rid = 0;
    foreach ($days as $day) {
        $rid += 1;
        $request['gid'] = strval($gid);
        $request['rid'] = strval($rid) ?? '0';
        $request['date'] = $day;

        $collideWith = checkCollision($request);
        $hide = 'rid,external_id,description,is_public_event,url,modified_by';

        if ($collideWith) {
            // If $removeCollision is true then remove this request and send
            // email to the booking party.
            foreach ($collideWith as $ev) {
                $errorMsg .= arrayToTableHTML($ev, 'events', $hide);
                if ($removeCollision) {
                    cancelBookingOrRequestAndNotifyBookingParty($ev, $reason);
                } else {
                    $errorMsg .= p('Collision with following event/request');
                    $result['collision'][] = $ev;
                    continue;
                }
            }
        }

        $request['timestamp'] = dbDateTime('now');
        $request['modified_by'] = getLogin();
        $request['description'] = $request['description'] ?? 'No description was provided.';

        $keys = 'gid,rid,external_id,created_by,venue,title,description' .
                ',date,start_time,end_time,timestamp,is_public_event,class';

        foreach (explode(',', $keys) as $k) {
            if (! strlen($request[$k] ?? '') === 0) {
                $errorMsg .= p("Empty value for '$k'. Fatal error.");
                $result['msg'] .= $errorMsg;
                return $result;
            }
        }

        $res = insertIntoTable('bookmyvenue_requests', $keys, $request);
        if (is_null($res)) {
            $errorMsg .= __FUNCTION__ . ": Could not submit request id $gid.";
            $result['msg'] .= $errorMsg;
            return $result;
        }

        $result['gid'] = $gid;
        $result['ridlist'][] = $rid;
    }

    // Some or all requests were successfully submitted.
    if (count($result['ridlist']) > 0) {
        $result['msg'] .= "Successfully submitted requests $gid.$rid.";
        $result['success'] = true;
        return $result;
    }

    // Failed. No request could be submitted may be due to collision.
    return $result;
}


/**
 * @brief Sunmit a request for review.
 *
 * @param $request
 *
 * @return Group id of request.
 */
function submitRequest(array $request)
{
    $hippoDB = initDB();
    ;
    $collision = false;

    if (! whoAmI()) {
        echo printErrorSevere("Error: I could not determine the name of user");
        return false;
    }

    $request[ 'created_by' ] = whoAmI();
    $repeatPat = __get__($request, 'repeat_pat', '');

    if (strlen($repeatPat) > 0) {
        $days = repeatPatToDays($repeatPat, $request[ 'date' ]);
    } else {
        $days = array( $request['date'] );
    }

    if (count($days) < 1) {
        echo minionEmbarrassed("I could not generate list of slots for you reuqest");
        return false;
    }

    $rid = 0;
    $res = $hippoDB->query('SELECT MAX(gid) AS gid FROM bookmyvenue_requests');
    $prevGid = $res->fetch(PDO::FETCH_ASSOC);
    $gid = intval($prevGid['gid']) + 1;

    $errorMsg = '';
    foreach ($days as $day) {
        $rid += 1;
        $request[ 'gid' ] = $gid;
        $request[ 'rid' ] = $rid;
        $request[ 'date' ] = $day;
        $request[ 'last_modified_on' ] = dbDateTime('now');

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

        $request[ 'timestamp' ] = dbDateTime('now');
        $res = insertIntoTable(
            'bookmyvenue_requests',
            'gid,rid,external_id,created_by,venue,title,description'
            .  ',date,start_time,end_time,timestamp,is_public_event,class'
            .  ',vc_url,url,last_modified_on',
            $request
        );

        if (! $res) {
            $errorMsg .= __FUNCTION__ . ": Could not submit request id $gid.";
            return 0;
        }
    }

    flashMessage($errorMsg, 'warning');
    return $gid;
}

function increaseEventHostedByVenueByOne($venueId)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare('UPDATE venues SET total_events = total_events + 1 WHERE id=:id');
    $stmt->bindValue(':id', $venueId);
    $res = $stmt->execute();
    return $res;
}

/**
 * @brief check for collision.
 *
 * @param $resques
 *
 * @return
 */
function checkCollision($request): array
{
    // Make sure this request is not clashing with another event or request.
    $events = getEventsOnThisVenueBetweenTime(
        $request[ 'venue' ],
        $request[ 'date' ],
        $request[ 'start_time' ],
        $request[ 'end_time' ]
    );

    $reqs = getRequestsOnThisVenueBetweenTime(
        $request[ 'venue' ],
        $request[ 'date' ],
        $request[ 'start_time' ],
        $request[ 'end_time' ]
    );

    $all = array();
    foreach ($events as $ev) {
        $all[] = $ev;
    }
    foreach ($reqs as $r) {
        // Not the our request.
        if (intval($r['gid']) == intval($request['gid'])
            && intval($r['rid']) == intval($request['rid'])
        ) {
            continue;
        }
        $all[] = $r;
    }
    return $all;
}

/**
 * @brief Create a new event in dateabase. The group id and event id of event
 * is same as group id (gid) and rid of request which created it. If there is
 * alreay a event or request pending which collides with this request, REJECT
 * it.
 *
 * @param $gid
 * @param $rid
 *
 * @return
 */
function approveRequest(string $gid, string $rid): array
{
    $request = getRequestById($gid, $rid);
    $msg = '';
    if (! $request) {
        $msg .= printWarning("No request $gid.$rid found in my database.");
        return ['msg'=>$msg, 'success' => false];
    }

    global $hippoDB;
    $collideWith = checkCollision($request);
    if ($collideWith && count($collideWith) > 0) {
        $msg .= "Following request is colliding with another event or request. Rejecting it..";
        $msg .= arrayToTableHTML($collideWith[0], 'request');
        return ['msg'=>$msg, 'success'=>false, 'data'=>$collideWith];
    }

    $stmt = $hippoDB->prepare(
        'INSERT INTO events (
        gid, eid, class, external_id, title, description, date, venue, start_time, end_time
        , url, vc_url, created_by, last_modified_on
    ) VALUES (
        :gid, :eid, :class, :external_id, :title, :description, :date, :venue, :start_time, :end_time,
        :url, :vc_url, :created_by, NOW()
    )'
    );
    $stmt->bindValue(':gid', $gid);
    $stmt->bindValue(':eid', $rid);
    $stmt->bindValue(':class', $request[ 'class' ]);
    $stmt->bindValue(':external_id', $request[ 'external_id']);
    $stmt->bindValue(':title', $request['title']);
    $stmt->bindValue(':description', $request['description']);
    $stmt->bindValue(':date', $request['date']);
    $stmt->bindValue(':venue', $request['venue']);
    $stmt->bindValue(':start_time', $request['start_time']);
    $stmt->bindValue(':end_time', $request['end_time']);
    $stmt->bindValue(':url', __get__($request, 'url', ''));
    $stmt->bindValue(':vc_url', __get__($request, 'vc_url', ''));
    $stmt->bindValue(':created_by', $request['created_by']);
    $res = $stmt->execute();

    if ($res) {
        $res = changeRequestStatus($gid, $rid, 'APPROVED');
        // And update the count of number of events hosted by this venue.
        increaseEventHostedByVenueByOne($request['venue']);
        return ['msg'=>$msg, 'success' => true];
    }
    return ['msg'=>$msg, 'success'=>false];
}

function rejectRequest($gid, $rid)
{
    return changeRequestStatus($gid, $rid, 'REJECTED');
}


/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Act on a  given request.
 *
 * @Param $gid       GID
 * @Param $rid       RID
 * @Param $whatToDo  APPPROVE/REJECT.
 * @Param $notify    SEND EMAIL.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function actOnRequest(
    string $gid,
    string $rid,
    string $whatToDo,
    bool $notify=false,
    array $request=[],
    string $byWhom=''
) : array {
    $status = "";
    if (! $byWhom) {
        $byWhom = whoAmI();
    }

    $success = false;
    if ($whatToDo === 'APPROVE') {
        $res = approveRequest($gid, $rid);
        $success = $res['success'];
        if (! $success) {
            return $res;
        }
        $status = 'APPROVED';
    } elseif ($whatToDo === 'REJECT') {
        $success = rejectRequest($gid, $rid);
        $status = 'REJECTED';
    } else {
        return ['status'=>false
            , 'msg'=>"Unknown request " . $gid . '.' . $rid .  " or command: " . $whatToDo];
    }

    if ($notify && $success) {
        if (! $request) {
            $request = getRequestById($gid, $rid);
        }
        $title = $req['title'];
        $subject = "Your booking request '$title' has been $status.";
        $msg  = '<p>The current status of your booking request is following.</p>';
        $msg .=  arrayToVerticalTableHTML($req, 'info');
        $msg .= "<p>If there is any mistake, please contact Dean's Office. 
            This request was acted upon by '$byWhom'</p>";

        $userEmail = getLoginEmail($request['created_by']);
        if (! $userEmail) {
            $userEmail = 'hippo@lists.ncbs.res.in';
            $msg .=  p("Alert! Could not find any email for " . $req['created_by']);
        }
        $res = sendHTMLEmail($msg, $subject, $userEmail);
    }
    return ['success'=>$success, 'msg'=>"Successfully $status request $gid.$rid."];
}

function changeIfEventIsPublic($gid, $eid, $status)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "UPDATE events SET is_public_event=:status
        WHERE gid=:gid AND eid=:eid"
    );
    $stmt->bindValue(':gid', $gid);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':eid', $eid);
    return $stmt->execute();
}

// Fetch all events at given venue and given day-time.
function eventsAtThisVenue($venue, $date, $time)
{
    $venue = trim($venue);
    $date = trim($date);
    $time = trim($time);

    $hippoDB = initDB();
    ;
    // Database reads in ISO format.
    $hDate = dbDate($date);
    $clockT = date('H:i', $time);

    // NOTE: When people say 5pm to 7pm they usually don't want to keep 7pm slot
    // booked.
    $stmt = $hippoDB->prepare(
        'SELECT * FROM events WHERE
        status=:status AND date=:date AND
        venue=:venue AND start_time <= :time AND end_time > :time'
    );
    $stmt->bindValue(':date', $hDate);
    $stmt->bindValue(':time', $clockT);
    $stmt->bindValue(':venue', $venue);
    $stmt->bindValue(':status', 'VALID');
    $stmt->execute();
    return fetchEntries($stmt);
}

// Fetch all requests for given venue and given day-time.
function requestsForThisVenue($venue, $date, $time)
{
    $venue = trim($venue);
    $date = trim($date);
    $time = trim($time);

    $hippoDB = initDB();

    // Database reads in ISO format.
    $hDate = dbDate($date);
    $clockT = date('H:i', $time);
    //echo "Looking for request at $venue on $hDate at $clockT ";

    // NOTE: When people say 5pm to 7pm they usually don't want to keep 7pm slot
    // booked.
    $stmt = $hippoDB->prepare(
        'SELECT * FROM bookmyvenue_requests WHERE
        status=:status
        AND date=:date AND venue=:venue
        AND start_time <= :time AND end_time > :time'
    );
    $stmt->bindValue(':status', 'PENDING');
    $stmt->bindValue(':date', $hDate);
    $stmt->bindValue(':time', $clockT);
    $stmt->bindValue(':venue', $venue);
    $stmt->execute();
    return fetchEntries($stmt);
}

/**
 * @brief Get all public events at this time.
 *
 * @param $date
 * @param $time
 *
 * @return
 */
function publicEvents($date, $time)
{
    $date = trim($date);
    $time = trim($time);

    $hippoDB = initDB();
    ;
    // Database reads in ISO format.
    $hDate = dbDate($date);
    $clockT = date('H:i', $time);

    // NOTE: When people say 5pm to 7pm they usually don't want to keep 7pm slot
    // booked.
    $stmt = $hippoDB->prepare(
        'SELECT * FROM events WHERE
        date=:date AND start_time <= :time AND end_time > :time'
    );
    $stmt->bindValue(':date', $hDate);
    $stmt->bindValue(':time', $clockT);
    $stmt->execute();
    return fetchEntries($stmt);
}

/**
 * @brief Update a group of requests. It can only modify fields which are set
 * editable in function.
 *
 * @param $gid
 * @param $options Any array as long as it contains fields with name in
 *                 editables.
 *
 * @return On success True, else False.
 */
function updateRequestGroup($gid, $options)
{
    $hippoDB = initDB();
    ;
    $editable = array( "title", "description", "is_public_event" );
    $fields = array( );
    $placeholder = array( );
    foreach (array_keys($options) as $key) {
        if (in_array($key, $editable)) {
            array_push($fields, $key);
            array_push($placeholder, "$key=:$key");
        }
    }

    $placeholder = implode(",", $placeholder);
    $query = "UPDATE bookmyvenue_requests SET $placeholder WHERE gid=:gid";

    $stmt = $hippoDB->prepare($query);

    foreach ($fields as $f) {
        $stmt->bindValue(":$f", $options[ $f ]);
    }

    $stmt->bindValue(':gid', $gid);
    return $stmt->execute();
}

function updateEventGroup($gid, $options)
{
    $events = getEventsByGroupId($gid);
    $results = array( );
    foreach ($events as $event) {
        $res = updateEvent($gid, $event['eid'], $options);
        $eid = $event[ 'eid' ];
        if (! $res) {
            echo printWarning("I could not update sub-event $eid");
        }
        array_push($results, $res);
    }
    return (! in_array(false, $results));
}

function updateEvent($gid, $eid, $options)
{
    $hippoDB = initDB();
    ;
    $editable = array( "title", "description", "is_public_event"
        , "status", "class" );
    $fields = array( );
    $placeholder = array( );
    foreach (array_keys($options) as $key) {
        if (in_array($key, $editable)) {
            array_push($fields, $key);
            array_push($placeholder, "$key=:$key");
        }
    }

    $placeholder = implode(",", $placeholder);
    $query = "UPDATE events SET $placeholder WHERE gid=:gid AND eid=:eid";

    $stmt = $hippoDB->prepare($query);

    foreach ($fields as $f) {
        $stmt->bindValue(":$f", $options[ $f ]);
    }

    $stmt->bindValue(':gid', $gid);
    $stmt->bindValue(':eid', $eid);
    return $stmt->execute();
}

// Create user if does not exists and fill information form LDAP server.
function createUserOrUpdateLogin($userid, $ldapInfo = array(), $db=null)
{
    if (! $db) {
        $db = initDB();
    };

    if (! $ldapInfo) {
        $ldapInfo = @getUserInfoFromLdap($userid);
    }

    if (isset($ldapInfo['last_name'])) {
        if ($ldapInfo[ 'last_name' ] == 'NA') {
            $ldapInfo[ 'last_name' ] = '';
        }
    }

    $stmt = $db->prepare(
        "INSERT IGNORE INTO logins
        (id, login, first_name, last_name, email, created_on, institute, laboffice)
            VALUES
            (:id, :login, :fname, :lname, :email,  NOW(), :institute, :laboffice)
        "
    );

    $institute = null;
    if (count($ldapInfo) > 0) {
        $institute = 'NCBS Bangalore';
    }

    $stmt->bindValue(':login', $userid);
    $stmt->bindValue(':id', __get__($ldapInfo, "uid", null));
    $stmt->bindValue(':fname', __get__($ldapInfo, "first_name", null));
    $stmt->bindValue(':lname', __get__($ldapInfo, "last_name", null));
    $stmt->bindValue(':email', __get__($ldapInfo, 'email', null));
    $stmt->bindValue(':laboffice', __get__($ldapInfo, 'laboffice', null));
    $stmt->bindValue(':institute', $institute);
    $stmt->execute();

    $stmt = $db->prepare("UPDATE logins SET last_login=NOW() WHERE login=:login");
    $stmt->bindValue(':login', $userid);
    return $stmt->execute();
}

/**
 * @brief Get all logins.
 *
 * @return
 */
function getLogins($status = '')
{
    $hippoDB = initDB();
    ;
    $where = '';
    if ($status) {
        $where = " WHERE status='$status' ";
    }
    $query = "SELECT * FROM logins $where ORDER BY joined_on DESC";
    $stmt = $hippoDB->query($query);
    $stmt->execute();
    return  fetchEntries($stmt);
}

function getLoginIds()
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query('SELECT login FROM logins');
    $stmt->execute();
    $results =  fetchEntries($stmt);
    $logins = array();
    foreach (array_values($results) as $val) {
        $logins[] = $val['login'];
    }

    return $logins;
}

/**
 * @brief Get user info from database.
 *
 * @param $user Login id of user.
 *
 * @return Array.
 */
function getUserInfo(string $user, bool $query_ldap = false, bool $search_everywhere = false) : array
{
    $login = explode('@', $user)[0];
    $res = getTableEntry('logins', 'login', array( 'login' => $login));
    $title = '';
    if ($res) {
        $title = __get__($res, 'title', '');
    }

    // Fetch ldap as well.
    $ldap = array( );
    if ($query_ldap) {
        $ldap = @getUserInfoFromLdap($login);
    }

    if (is_array($ldap) && is_array($res) && $ldap) {
        foreach ($ldap as $key => $val) {
            if ($key == 'joined_on' && isDate(__get__($res, 'joined_on', ''))) {
                continue;
            }
            $res[ $key ] = $val;
        }
    }

    // Still not foud, then search speakers.
    if (! $res && $search_everywhere) {
        $res = getTableEntry('speakers', 'email', [ 'email' => $user ]);
    }

    // If title was found in database, overwrite ldap info.
    if ($title) {
        $res[ 'title' ] = $title;
    }

    $res['login'] = $user;

    // If no email found, then it is not a valid userid. What a computer system
    // account without an email.
    if (! __get__($res, 'email', '')) {
        $res = [];
    }
    return $res;
}

function extractLoginName(string $emailOrLogin) : string
{
    return explode('@', $emailOrLogin)[0];
}

function getLoginInfo(string $login_name, bool $query_ldap = false, bool $search_everywhere = false) : array
{
    // Otherwise we may not be find by email.
    if (! $search_everywhere) {
        $login_name = extractLoginName($login_name);
    }
    return getUserInfo($login_name, $query_ldap, $search_everywhere);
}

function getLoginByEmail(string $email) : string
{
    $res = executeQuery("SELECT login FROM logins WHERE email='$email' AND login >''");
    if (count($res) > 0) {
        return $res[0]['login'];
    }
    return '';
}

function getLoginByEmailOrLogin(string $emailOrLogin): string
{
    $email = $emailOrLogin;
    $login = explode('@', $emailOrLogin)[0];
    $res = executeQuery(
        "SELECT login FROM logins WHERE 
        (email='$email' OR login='$login') AND login >''"
    );
    if (count($res) > 0) {
        return $res[0]['login'];
    }
    return '';
}


function getLoginEmail(string $login)
{
    $login = explode('@', $login)[0];
    $res = executeQuery("SELECT email FROM logins WHERE login='$login'");
    if(! $res) {
        $res = ['email'=>''];
    }

    if (strlen(trim($res['email'] ?? '') < 1)) {
        $info = @getUserInfoFromLdap($login);
        if ($info && array_key_exists('email', $info) && $info['email']) {
            // Update user in database.
            createUserOrUpdateLogin($login, $info);
            $alternativeEmail = __get__($info, 'alternative_email', '');
            $res['email'] = __get__($info, 'email', $alternativeEmail);
        }
    }
    return $res['email'];
}

function getRoles(string $user) : array
{
    // Turn email to username.
    $hippoDB = initDB();
    $stmt = $hippoDB->prepare('SELECT roles FROM logins WHERE login=:login OR email=:email');
    $stmt->bindValue(':login', $user);
    $stmt->bindValue(':email', $user);
    $stmt->execute();
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if (! $res) {
        return ['USER'];
    }
    return explode(",", $res['roles']);
}

function getMyAws($user)
{
    $hippoDB = initDB();
    ;

    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker
        ORDER BY date DESC ";
    $stmt = $hippoDB->prepare($query);
    $stmt->bindValue(':speaker', $user);
    $stmt->execute();
    return fetchEntries($stmt);
}


function getMyAwsOn($user, $date)
{
    $hippoDB = initDB();
    ;

    $query = "SELECT * FROM annual_work_seminars
        WHERE speaker=:speaker AND date=:date ORDER BY date DESC ";
    $stmt = $hippoDB->prepare($query);
    $stmt->bindValue(':speaker', $user);
    $stmt->bindValue(':date', $date);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAwsById($awsID)
{
    $hippoDB = initDB();
    ;
    $query = "SELECT * FROM annual_work_seminars WHERE id=:id";
    $stmt = $hippoDB->prepare($query);
    $stmt->bindValue(':id', $awsID);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * @brief Return only recent most AWS given by this speaker.
 *
 * @param $speaker
 *
 * @return
 */
function getLastAwsOfSpeaker($speaker)
{
    $hippoDB = initDB();
    ;
    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker
        ORDER BY date DESC LIMIT 1";
    $stmt = $hippoDB->prepare($query);
    $stmt->bindValue(':speaker', $speaker);
    $stmt->execute();
    // Only return the last one.
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * @brief Return all AWS given by this speaker.
 *
 * @param $speaker
 *
 * @return
 */
function getAwsOfSpeaker(string $speaker): array
{
    $hippoDB = initDB();
    ;
    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker
        ORDER BY date DESC" ;
    $stmt = $hippoDB->prepare($query);
    $stmt->bindValue(':speaker', $speaker);
    $stmt->execute();
    return fetchEntries($stmt);
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get last AWS of speaker.
 *
 * @Param $speaker
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getLatestAWSOfSpeaker(string $speaker): array
{
    $hippoDB = initDB();
    ;
    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker
        ORDER BY date DESC LIMIT 1" ;
    $stmt = $hippoDB->prepare($query);
    $stmt->bindValue(':speaker', $speaker);
    $stmt->execute();
    return fetchEntry($stmt);
}

function getSupervisors()
{
    $hippoDB = initDB();
    ;
    // First get all faculty members
    $faculty = getFaculty('ACTIVE');

    // And then all supervisors.
    $stmt = $hippoDB->query('SELECT * FROM supervisors ORDER BY first_name');
    $stmt->execute();
    $supervisors = fetchEntries($stmt);
    foreach ($supervisors as $super) {
        array_push($faculty, $super);
    }
    return $faculty;
}


/**
 * @brief Find entry in database with given entry.
 *
 * @param $email
 *
 * @return
 */
function findAnyoneWithEmail($email)
{
    $res = getTableEntry('faculty', 'email', array( 'email' => $email ));
    if (! $res) {
        $res = getTableEntry('supervisors', 'email', array('email' => $email));
    }
    if (! $res) {
        $res = getTableEntry('speakers', 'email', array('email' => $email));
    }
    if (! $res) {
        $res = getTableEntry('logins', 'email', array('email' => $email));
    }
    return $res;
}

function findAnyoneWithEmailOrLogin($emailOrLogin)
{
    $res = getTableEntry('logins', 'login', array('login' => $loginOrEmail));
    if (! $res) {
        $res = findAnyoneWithEmail($emailOrLogin);
    }
    return $res;
}


/**
 * @brief Generate a where expression.
 *
 * @param $keys
 * @param $data
 *
 * @return
 */
function whereExpr($keys, $data)
{
    $whereExpr = array( );
    $keys = explode(',', $keys);

    foreach ($keys as $k) {
        $whereExpr[] = "$k='" . $data[ $k] . "'";
    }

    return implode(' AND ', $whereExpr);
}

/**
 * @brief
 *
 * @param $tablename
 * @param $orderby
 * @param $where
 * @param $what
 *
 * @return
 */
function getTableEntries($tablename, $orderby='', $where='', $what='*', int $limit=0, int $offset=0) : array
{
    $hippoDB = initDB();
    ;
    $query = "SELECT $what FROM $tablename";

    if (is_string($where) && strlen($where) > 0) {
        $query .= " WHERE $where ";
    }

    if (strlen($orderby) > 0) {
        $query .= " ORDER BY $orderby ";
    }

    if ($limit > 0) {
        $query .= " LIMIT $offset, $limit";
    }

    $res = $hippoDB->query($query);
    $entries = fetchEntries($res);

    if (! $entries) {
        return array();
    }

    return $entries;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get a single entry from table.
 *
 * @Param $tablename
 * @Param $whereKeys
 * @Param $data
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getTableEntry(string $tablename, $whereKeys, array $data) : array
{
    $hippoDB = initDB();

    if (is_string($whereKeys)) {
        $whereKeys = explode(",", $whereKeys);
    }

    $where = array( );
    foreach ($whereKeys as $key) {
        $where[] = "$key=:$key";
    }

    $where = implode(" AND ", $where);

    $query = "SELECT * FROM $tablename WHERE $where";

    $stmt = $hippoDB->prepare($query);

    foreach ($whereKeys as $key) {
        $stmt->bindValue(":$key", $data[ $key ]);
    }

    try {
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            return $res;
        }
    } catch (Exception $e) {
        echo printWarning("Failed to fetch. Error was " . $e->getMessage());
        return array();
    }
    return array();
}


/**
 * @brief Insert a new entry in table.
 *
 * @param $tablename
 * @param $keys,     Keys to update/insert in table.
 * @param $data
 *
 * @return The id of newly inserted entry on success. Null otherwise.
 */
function insertIntoTable($tablename, $keys, $data)
{
    $hippoDB = initDB();

    if (is_string($keys)) {
        $keys = explode(',', $keys);
    }

    $values = array();
    $cols = array();
    foreach ($keys as $k) {
        if (! is_string($k)) {
            continue;
        }

        // If values for this key in $data is null then don't use it here.
        if (__get__($data, $k, null) !== null) {
            array_push($cols, "$k");
            array_push($values, ":$k");
        }
    }

    $keysT = implode(",", $cols);
    $values = implode(",", $values);

    $query = "INSERT INTO $tablename ( $keysT ) VALUES ( $values )";
    $stmt = $hippoDB->prepare($query);

    foreach ($cols as $k) {
        $value = $data[$k];
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $stmt->bindValue(":$k", $value);
    }

    $res = $stmt->execute();
    if ($res) {
        // When created return the id of table else return null;
        $stmt = $hippoDB->query("SELECT LAST_INSERT_ID() FROM $tablename");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

/**
 * @brief Insert an entry into table. On collision, update the table.
 *
 * @param $tablename
 * @param $keys
 * @param $updatekeys
 * @param $data
 *
 * @return The value of last updated row.
 */
function insertOrUpdateTable($tablename, $keys, $updatekeys, $data)
{
    $hippoDB = initDB();
    ;

    if (is_string($keys)) {
        $keys = explode(',', $keys);
    }

    if (is_string($updatekeys)) {
        $updatekeys = explode(',', $updatekeys);
    }

    $values = array( );
    $cols = array( );
    foreach ($keys as $k) {
        // If values for this key in $data is null then don't use it here.
        if (__get__($data, $k, null) !== null) {
            array_push($cols, "$k");
            array_push($values, ":$k");
        }
    }

    $keysT = implode(",", $cols);
    $values = implode(",", $values);

    $updateExpr = '';
    if (count($updatekeys) > 0) {
        $updateExpr .= ' ON DUPLICATE KEY UPDATE ';
        foreach ($updatekeys as $k) {
            // Update only if the new value is not empty.
            if (strlen($data[ $k ]) > 0) {
                $updateExpr .= "$k=:$k,";
                array_push($cols, $k);
            }
        }

        // Remove last ','
        $updateExpr = rtrim($updateExpr, ",");
    }

    $query = "INSERT INTO $tablename ( $keysT ) VALUES ( $values ) $updateExpr";
    $stmt = $hippoDB->prepare($query);
    foreach ($cols as $k) {
        $value = $data[$k];
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $stmt->bindValue(":$k", $value);
    }

    $res = $stmt->execute();

    // This is MYSQL specific. Only try this if table has an AUTO_INCREMENT
    // id field.
    if (array_key_exists('id', $data) && $res) {
        // When created return the id of table else return null;
        $stmt = $hippoDB->query("SELECT LAST_INSERT_ID() FROM $tablename");
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastInsertId = intval(__get__($res, 'LAST_INSERT_ID()', 0));

        // Store the LAST_INSERT_ID if insertion happened else the id of update
        // execution.
        if ($lastInsertId > 0) {
            $res['id'] = $lastInsertId;
        } else {
            $res['id' ] = $data[ 'id' ];
        }
        return $res;
    }
    return $res;
}

function getTableUniqueIndices($tableName)
{
    $res = executeQuery(
        "SELECT DISTINCT CONSTRAINT_NAME
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE table_name = '$tableName' AND constraint_type = 'UNIQUE'"
    );
    return $res;
}

/**
 * @brief Delete an entry from table.
 *
 * @param $tableName
 * @param $keys
 * @param $data
 *
 * @return Status of execute statement.
 */
function deleteFromTable($tablename, $keys, $data)
{
    $hippoDB = initDB();
    ;

    if (gettype($keys) == "string") {
        $keys = explode(',', $keys);
    }

    $values = array( );
    $cols = array( );
    foreach ($keys as $k) {
        if ($data[$k]) {
            array_push($cols, "$k");
            array_push($values, ":$k");
        }
    }

    $values = implode(",", $values);
    $query = "DELETE FROM $tablename WHERE ";

    $whereClause = array( );
    foreach ($cols as $k) {
        array_push($whereClause, "$k=:$k");
    }

    $query .= implode(" AND ", $whereClause);

    $stmt = $hippoDB->prepare($query);
    foreach ($cols as $k) {
        $value = $data[$k];
        if (gettype($value) == 'array') {
            $value = implode(',', $value);
        }
        $stmt->bindValue(":$k", $value);
    }
    $res = $stmt->execute();
    return $res;
}



/**
 * @brief A generic function to update a table.
 *
 * @param $tablename Name of table.
 * @param $wherekeys WHERE $wherekey=wherekeyval,... etc.
 * @param $keys      Keys to be updated.
 * @param $data      An array having all data.
 * @param $ignoremissing Ignore missing values.
 *
 * @return
 */
function updateTable($tablename, $wherekeys, $keys, array $data, bool $ignoremissing=true)
{
    if (! $data) {
        echo printWarning("Empty data.");
        return false;
    }

    $hippoDB = initDB();
    $hippoDB->beginTransaction();

    $query = "UPDATE $tablename SET ";

    if (is_string($wherekeys))
        $wherekeys = explode(",", $wherekeys);

    if (is_string($keys))
        $keys = explode(",", $keys);

    $whereclause = array( );
    foreach ($wherekeys as $wkey)
        $whereclause[] = "$wkey=:$wkey";

    $whereclause = implode(" AND ", $whereclause);

    $values = array( );
    $cols = array();
    foreach ($keys as $k) {
        if ($ignoremissing && __get__($data, $k, null) === null) 
            continue;

        array_push($cols, $k);
        array_push($values, "$k=:$k");
    }
    $values = implode(",", $values);
    $query .= " $values WHERE $whereclause";

    $stmt = $hippoDB->prepare($query);
    foreach ($cols as $k) {
        $value = $data[$k];
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $stmt->bindValue(":$k", $value);
    }

    foreach ($wherekeys as $wherekey) {
        $stmt->bindValue(":$wherekey", $data[$wherekey]);
    }

    $res = $stmt->execute();
    if ($res) {
        $hippoDB->commit();
    }

    return true;
}


/**
 * @brief Get the AWS scheduled in future for this speaker.
 *
 * @param $speaker The speaker.
 *
 * @return Array.
 */
function scheduledAWSInFuture($speaker)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM upcoming_aws WHERE
        speaker=:speaker AND date > CURDATE()
        "
    );
    $stmt->bindValue(":speaker", $speaker);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * @brief Check if there is a temporary AWS schedule.
 *
 * @param $speaker
 *
 * @return
 */
function temporaryAwsSchedule($speaker)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "SELECT * FROM aws_temp_schedule WHERE
        speaker=:speaker AND date > CURDATE()
        "
    );
    $stmt->bindValue(":speaker", $speaker);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * @brief Fetch faculty from database. 
 *
 * @param $status
 *
 * @return
 */
function getFaculty($status='', $order_by = 'first_name') : array
{
    if(! $status)
        $where = " status!='INVALID'";
    else
        $where = " status='$status'";
    // $where .= " AND affiliation != 'OTHER'";
    return getTableEntries('faculty', 'first_name', $where);
}

/**
 * @brief Get all pending requests for this user.
 *
 * @param $user   Name of the user.
 * @param $status status of the request.
 *
 * @return
 */
function getAwsRequestsByUser($user, $status = 'PENDING')
{
    $hippoDB = initDB();
    ;
    $query = "SELECT * FROM aws_requests WHERE status=:status AND speaker=:speaker";
    $stmt = $hippoDB->prepare($query);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':speaker', $user);
    $stmt->execute();
    return fetchEntries($stmt);
}

function getAwsRequestById($id)
{
    $hippoDB = initDB();
    ;
    $query = "SELECT * FROM aws_requests WHERE id=:id";
    $stmt = $hippoDB->prepare($query);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPendingAWSRequests()
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query("SELECT * FROM aws_requests WHERE status='PENDING'");
    $stmt->execute();
    return fetchEntries($stmt);
}

function getAllAWS()
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query("SELECT * FROM annual_work_seminars ORDER BY date DESC");
    $stmt->execute();
    return fetchEntries($stmt);
}

/**
 * @brief Return AWS from last n years.
 *
 * @param $years
 *
 * @return Array of events.
 */
function getAWSFromPast($from)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query(
        "SELECT * FROM annual_work_seminars
        WHERE date >= '$from' ORDER BY date DESC, speaker
    "
    );
    $stmt->execute();
    return fetchEntries($stmt);
}

function isEligibleForAWS($speaker)
{
    $res = executeQuery("SELECT login FROM logins WHERE login='$speaker' AND eligible_for_aws='YES' AND status='ACTIVE'");
    if (! $res) {
        return false;
    }

    if (count($res) == 0) {
        return false;
    }

    return true;
}


/**
 * @brief Get AWS users.
 *
 * @return Array containing AWS speakers.
 */
function getAWSSpeakers($sortby = '', $where_extra = '')
{
    $hippoDB = initDB();
    ;

    $sortExpr = '';
    if ($sortby) {
        $sortExpr = " ORDER BY '$sortby'";
    }

    $whereExpr = "status='ACTIVE' AND eligible_for_aws='YES'";
    if ($where_extra) {
        $whereExpr .= " AND $where_extra";
    }

    $stmt = $hippoDB->query("SELECT * FROM logins WHERE $whereExpr $sortExpr ");
    $stmt->execute();
    return fetchEntries($stmt);
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Final the total number of AWS given by user.
 *
 * @Param int Number of AWS.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function numberOfAWSGivenBySpeaker($speaker): int
{
    $res = executeQuery(
        "SELECT count(*) as total FROM annual_work_seminars
        WHERE speaker='$speaker'"
    );
    return $res[0]['total'];
}

/**
 * @brief Return AWS entries schedules by my minion..
 *
 * @return
 */
function getTentativeAWSSchedule($awsday = null)
{
    $hippoDB = initDB();
    ;
    $whereExpr = '';
    if ($awsday) {
        $date = dbDate($awsday);
        $whereExpr = " WHERE date='$date' ";
    }
    $stmt = $hippoDB->query("SELECT * FROM aws_temp_schedule $whereExpr ORDER BY date");
    $stmt->execute();
    return fetchEntries($stmt);
}

/**
 * @brief Get all upcoming AWSes. Closest to today first (Ascending date).
 *
 * @return Array of upcming AWS.
 */
function getUpcomingAWS($awsday = null)
{
    global $GLOBAL_AwsDay;

    if (! $awsday) {
        $date = dbDate("this $GLOBAL_AwsDay");
        $whereExpr = "date >= '$date'";
    } else {
        $date = dbDate($awsday);
        $whereExpr = "date >= '$date'";
    }
    $res = executeQuery("SELECT * FROM upcoming_aws WHERE $whereExpr ORDER BY date");
    return $res;
}

function getUpcomingAWSOnThisMonday($awsdate)
{
    $date = dbDate($awsdate);
    $res = executeQuery("SELECT * FROM upcoming_aws WHERE date='$date'");
    return $res;
}

function getTotalUpcomingAWSOnThisMonday(string $awsdata): int
{
    $date = dbDate($awsdata);
    $res = executeQuery(
        "SELECT COUNT(*) as total FROM upcoming_aws 
        WHERE date='$date'", true
    );
    return intval($res[0]['total']);
}

function maxAWSAllowed(): int
{
    return 3;
}

function getUpcomingAWSById($id)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query("SELECT * FROM upcoming_aws WHERE id = $id ");
    $stmt->execute();
    return  $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUpcomingAWSOfSpeaker(string $speaker)
{
    return getTableEntry(
        'upcoming_aws',
        'speaker,status',
        ['speaker'=> $speaker, 'status' => 'VALID']
    );
}

/**
 * @brief Accept a auto generated schedule. We put the entry into table
 * upcoming_aws and delete this entry from aws_temp_schedule tables. In case
 * of any failure, leave everything untouched.
 *
 * @param $speaker
 * @param $date
 *
 * @return
 */
function acceptScheduleOfAWS(string $speaker, string $date, string $venue=''): array
{
    $ret = ['awsid'=>0, 'msg'=>''];
    $hippoDB = initDB();
    if (! $venue) {
        $venue = getDefaultAWSVenue($date);
    }

    // If date is invalid, return.
    if (strtotime($date) < 0  or strtotime($date) < strtotime('-7 day')) {
        return 0;
    }

    // If there is already a schedule for this person.
    $r = getTableEntry(
        'upcoming_aws',
        'speaker,date',
        ['speaker' => $speaker, 'date' => dbDate($date), 'venue'=>$venue]
    );

    if ($r) {
        $ret['msg'] .= "Already assigned for $speaker on $date on venue $venue.";
        $ret['awsid'] = intval($r['id']);
    }

    // Make sure that person is eligible for AWS. Usually she is some sometimes
    // she is not.
    $login = explode('@', $speaker)[0];
    $r = updateTable(
        'logins',
        'login',
        'eligible_for_aws',
        array( 'login' => $login, 'eligible_for_aws' => 'YES')
    );

    // Else add to table.
    $hippoDB->beginTransaction();

    $stmt = $hippoDB->prepare(
        'INSERT INTO upcoming_aws (speaker, date, venue) VALUES (:speaker, :date, :venue)'
    );

    $stmt->bindValue(':speaker', $speaker);
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':venue', $venue);

    $awsID = -1;
    try {
        $r = $stmt->execute();
        // delete this row from temp table.
        $stmt = $hippoDB->prepare(
            'DELETE FROM aws_temp_schedule WHERE
            speaker=:speaker AND date=:date
            '
        );
        $stmt->bindValue(':speaker', $speaker);
        $stmt->bindValue(':date', $date);
        $r = $stmt->execute();

        // If this happens, I must not commit the previous results into table.
        if (! $r) {
            $hippoDB->rollBack();
            $ret['awsid'] = 0;
            $ret['msg'] .= "Failed to remove temp schedule.";
            return $ret;
        }

        // If successful add a query in queries to create a clickable query.
        $aws = getTableEntry(
            'upcoming_aws',
            'speaker,date,venue',
            ['speaker' => $speaker, 'date' => $date, 'venue'=>$venue]
        );
        $awsID = $aws[ 'id' ];
        $clickableQ = "UPDATE upcoming_aws SET acknowledged='YES' WHERE id='$awsID'";
        insertClickableQuery($speaker, "upcoming_aws.$awsID", $clickableQ);
    } catch (Exception $e) {
        $hippoDB->rollBack();
        $ret['awsid'] = 0;
        $ret['msg'] .= minionEmbarrassed(
            "Failed to insert $speaker, $date into database: " . $e->getMessage()
        );
        return $ret;
    }
    $hippoDB->commit();
    $ret['awsid'] = intval($awsID);
    return $ret;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Insert a query which user can execute by clicking on URL.
 *
 * @Param $who_can_execute
 * @Param $external_id
 * @Param $query
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function insertClickableQuery($who_can_execute, $external_id, $query): array
{
    $data =  array(
        'query' => $query
        , 'external_id' => $external_id
        , 'who_can_execute' => $who_can_execute
        , 'last_modified_on' => dbDateTime('now')
        , 'status' => 'PENDING'
        );

    $res = getTableEntry('queries', 'who_can_execute,query,external_id,status', $data);
    if ($res) {
        // printInfo("Clickable URL still unused.");
        return ['id'=>$res['id'], 'hash'=>$res['hash']];
    }

    $data['id'] = getUniqueID('queries');
    $data['hash'] = md5($who_can_execute . $query);
    $res = insertIntoTable(
        'queries',
        'id,who_can_execute,external_id,hash,query,last_modified_on,status',
        $data
    );

    return ['id'=>$data['id'], 'hash'=>$data['hash']];
}


/**
 * @brief Query AWS database of given query.
 *
 * @param $query
 *
 * @return List of AWS with matching query.
 */
function queryAWS($query)
{
    if (strlen($query) == 0) {
        return array( );
    }

    if (strlen($query) < 3) {
        // printWarning("Query is too small");
        return array( );
    }

    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query(
        "SELECT * FROM annual_work_seminars
        WHERE LOWER(abstract) LIKE LOWER('%$query%') ORDER BY date DESC"
    );
    $stmt->execute();
    return fetchEntries($stmt);
}

/**
 * @brief Clear a given AWS from upcoming AWS list.
 *
 * @param $speaker
 * @param $date
 *
 * @return
 */
function clearUpcomingAWS($speaker, $date) : array
{
    $result = [ 'msg'=>'', 'success'=>true];

    $aws = getTableEntry(
        'upcoming_aws', 'speaker,date',
        ['speaker'=>$speaker, 'date'=>$date]
    );

    $res = executeQueryReadonly(
        "DELETE FROM upcoming_aws 
        WHERE speaker='$speaker' AND date='$date'"
    );

    // Remove this AWS related booking from the events..
    if($res) {
        $result['msg'] .= "Successfully deleted upcoming AWS. ";
        $externalID = 'upcoming_aws.' . $aws['id'];
        $res2 = updateTable(
            'events', 'external_id', 'status',
            ['status'=>'INVALID', 'external_id'=>$externalID]
        );
        if($res2) {
            $result['msg'] .= "Successfully removed event $externalID.";
        }
    }
    return $result;
}

/**
 * @brief Delete an entry from annual_work_seminars table.
 *
 * @param $speaker
 * @param $date
 *
 * @return True, on success. False otherwise.
 */
function deleteAWSEntry($speaker, $date)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare(
        "DELETE FROM annual_work_seminars WHERE speaker=:speaker AND date=:date"
    );
    $stmt->bindValue(':speaker', $speaker);
    $stmt->bindValue(':date', $date);
    return $stmt->execute();
}

function getHolidays($from = null)
{
    $hippoDB = initDB();
    if (! $from) {
        $from = date('Y-m-d', strtotime('today'));
    }
    $stmt = $hippoDB->query("SELECT * FROM holidays WHERE date >= '$from' ORDER BY date");
    return fetchEntries($stmt);
}

/**
 * @brief Fetch all existing email templates.
 *
 * @return
 */
function getEmailTemplates()
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query("SELECT * FROM email_templates");
    return fetchEntries($stmt);
}

function getEmailTemplateById($id)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query("SELECT * FROM email_templates where id='$id'");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getEmailsByStatus($status = 'PENDING')
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query(
        "SELECT * FROM emails where status = '$status'
        ORDER BY when_to_send DESC
        "
    );
    return fetchEntries($stmt);
}

function getEmailById($id)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->query("SELECT * FROM emails where id = '$id'");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getEmailByName($name)
{
    $name = preg_replace('#(Drs*|Mrs*|NA).?\s*#i', '', $name);
    if (! $name) {
        return '';
    }

    $nameArr = explode(' ', $name);
    $fname = $nameArr[0];
    $lname = $nameArr[ count($nameArr) - 1 ];
    $data = array( 'first_name' => $fname, 'last_name' => $lname );
    $res = getTableEntry('logins', 'first_name,last_name', $data);
    if (! $res) {
        $res = getTableEntry('faculty', 'first_name,last_name', $data);
    }
    if (! $res) {
        $res = getTableEntry('logins', 'first_name', $data);
    }
    if (! $res) {
        $res = getTableEntry('faculty', 'first_name', $data);
    }

    if (! $res) {
        return '';
    }

    return $res['email'];
}

function getUpcomingEmails($from = null)
{
    $hippoDB = initDB();
    ;
    if (! $from) {
        $from = dbDateTime(strtotime('today'));
    }

    $stmt = $hippoDB->query("SELECT *k FROM emails where when_to_send>='$from'");
    return fetchEntries($stmt);
}

function getSpeakers()
{
    $hippoDB = initDB();
    ;
    $res = $hippoDB->query('SELECT * FROM speakers');
    return fetchEntries($res);
}


/**
 * @brief Add or update the speaker and returns the id.
 *
 * @param $data
 *
 * @return
 */
function addOrUpdateSpeaker($data)
{
    if (__get__($data, 'id', 0) > 0) {
        $speaker = getTableEntry('speakers', 'id', $data);
        if ($speaker) {
            $res = updateTable(
                'speakers',
                'id',
                'honorific,email,first_name,middle_name,last_name'
                    . ',designation,department,institute,homepage',
                $data
            );
            return getTableEntry('speakers', 'id', $speaker);
        }
    }

    // If we are here, then speaker is not found. Construct a new id.
    $data['id'] = getUniqueFieldValue('speakers', 'id');
    $res = insertIntoTable(
        'speakers',
        'id,email,honorific,first_name,middle_name,last_name,'
            . 'designation,department,institute,homepage',
        $data
    );

    return getTableEntry('speakers', 'id', $data);
}



/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Create events for given course.
 * The course id COURSE-SEM-YEAR is used a user for booking. When
 * deleting for course, we delete all events created by COURSE-SEM-YEAR
 *
 * @Param $runningCourseId Course Id of course.
 *
 * @Returns True if successful.
 */
/* ----------------------------------------------------------------------------*/
function addCourseBookings($runningCourseId)
{
    // Fetch the course name.
    $course = getTableEntry('courses', 'id', array( 'id' => $runningCourseId ));
    $cname = getCourseName($course[ 'course_id' ]);

    $bookedby = $runningCourseId;

    $venue = __get__($course, 'venue', '');
    if (! $venue) {
        echo printWarning("No venue selected for this course, so no booking is made.");
        return false;
    }

    $title = "Course $cname";

    $tiles = getSlotTiles($course[ 'slot' ]);
    $ignoreTiles = $course[ 'ignore_tiles' ];

    if ($ignoreTiles) {
        $tiles = array_diff($tiles, explode(',', $ignore_tiles));
    }

    $startDate = $course[ 'start_date' ];
    $endDate = $course[ 'end_date' ];

    // Select unique gid.
    $gid = getUniqueFieldValue('bookmyvenue_requests', 'gid');

    $temp = $startDate;
    $rid = 0;
    while (strtotime($temp) <= strtotime($endDate)) {
        foreach ($tiles as $tile) {
            $rid += 1;
            $day = $tile[ 'day' ];
            $date = dbDate(strtotime("this $day", strtotime($temp)));
            $startTime = $tile[ 'start_time' ];
            $endTime = $tile[ 'end_time' ];
            $msg = "$title at $venue on $date, $startTime, $endTime";

            $data = array(
                'gid' => $gid, 'rid' => $rid
                , 'date' => dbDate($date)
                , 'start_time' => $startTime
                , 'end_time' => $endTime
                , 'venue' => $venue
                , 'title' => $title
                , 'class' => 'CLASS'
                , 'description' => 'AUTO BOOKED BY Hippo'
                , 'created_by' => $bookedby
                , 'last_modified_on' => dbDateTime('now')
            );

            // Check if there is already an event here. If yes, notify the user.
            $events = getEventsOnThisVenueBetweenTime($venue, $date, $startTime, $endTime);
            $reqs = getRequestsOnThisVenueBetweenTime($venue, $date, $startTime, $endTime);

            $reason = p(
                "Your booking request/approved booking was on a 
                LECTURE HALL.  Course '$cname' is assigned this lecture hall just now.
                Courses are always given the higest priority on LECTURE HALLS."
            );

            foreach ($events as $ev) {
                echo arrayToTableHTML($ev, 'event');
                cancelBookingOrRequestAndNotifyBookingParty($ev, $reason);
            }

            foreach ($reqs as $req) {
                echo arrayToTableHTML($req, 'event');
                cancelBookingOrRequestAndNotifyBookingParty($req, $reason);
            }

            // Create request and approve it. Direct entry in event is
            // prohibited because then gid and eid are not synched.
            $res = insertIntoTable('bookmyvenue_requests', array_keys($data), $data);
            $res = approveRequest($gid, $rid);
            if (! $res['success']) {
                echo printWarning("Could not book: $msg");
            }
        }

        // get the next week now.
        $temp = dbDate(strtotime('+1 week', strtotime($temp)));
    }
    return true;
}


/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Update the booking for this course.
 *
 * @Param $course
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function updateBookings($course)
{
    deleteBookings($course);
    $res = addCourseBookings($course);
    return $res;
}
function canIChangeRegistration($c, $login) : bool
{
    $today = strtotime('now');
    $res = [ 'answer' => 'yes', 'why' => '' ];

    // If already registered, say no.
    $cstart = strtotime($c[ 'start_date' ]);

    // Registration and dropping is allowed for 3 weeks from course start date.
    if (($cstart + 21*24*3600) > $today) {
        return false;
    }
    return true;
}


function getMyCourses($sem, $year, $user) : array
{
    $whereExpr = "(status='VALID' OR status='WAITLIST') AND semester='$sem' AND year='$year' 
        AND student_id='$user'";
    $courses = getTableEntries('course_registration', 'course_id', $whereExpr);
    foreach ($courses as &$c) {
        $c['name'] = getCourseName($c['course_id']);
    }
    return $courses;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Return all courses of a user.
 *
 * @Param $user
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getMyAllCourses(string $user) : array
{
    $whereExpr = "(status='VALID' OR status='WAITLIST') AND student_id='$user'";
    $courses = getTableEntries('course_registration', 'year DESC, semester', $whereExpr);
    foreach ($courses as &$course) {
        $course['name'] = getCourseName($course['course_id']);
        $course['instructors'] = getCourseInstructorsList($course['course_id']);
    }
    return $courses;
}

/**
 * @brief Get all active recurrent events from today.
 *
 * @param $day
 *
 * @return
 */
function getActiveRecurrentEvents($day)
{
    $hippoDB = initDB();
    ;

    $from = dbDate($day);

    // We get gid of events which are still valid.
    $res = $hippoDB->query(
        "SELECT gid FROM events WHERE
                date >= '$from' AND status='VALID' ORDER BY date"
    );
    $gids = fetchEntries($res);

    $upcomingRecurrentEvents = array( );
    foreach ($gids as $gid) {
        $gid = $gid[ 'gid' ];

        // Must order by date.
        $gEvents = getTableEntries('events', 'date', "gid='$gid'");

        // Definately there has to be more than 1 event in group to be qualified
        // as group event.
        if (count($gEvents) > 1) {
            $upcomingRecurrentEvents[ $gid ] = $gEvents;
        }
    }

    return $upcomingRecurrentEvents;
}

/**
 * @brief Get login from logins table when name is given.
 *
 * @param $name
 *
 * @return
 */
function getLoginByName($name)
{
    $hippoDB = initDB();
    $name = explode(' ', $name);
    $fname = $name[ 0 ];
    $lname = end($name);
    $res = $hippoDB->query(
        "SELECT * FROM logins WHERE
        first_name='$fname' AND last_name='$lname'"
    );
    return $res->fetch(PDO::FETCH_ASSOC);
}

function getSpeakerByName($name)
{
    $hippoDB = initDB();
    ;

    $name = splitName($name);
    $fname = $name[ 'first_name' ];
    $mname = $name[ 'middle_name' ];
    $lname = $name[ 'last_name' ];

    // WHERE condition.
    $where = array( "first_name='$fname'" );
    if ($lname) {
        $where[] =  "last_name='$lname'";
    }

    if ($mname) {
        $where[] = "middle_name='$mname'";
    }
    $whereExpr = implode(' AND ', $where);

    $res = $hippoDB->query("SELECT * FROM speakers WHERE $whereExpr ");
    return $res->fetch(PDO::FETCH_ASSOC);
}

function getSpeakerByID($id): array
{
    return getTableEntry('speakers', 'id', ['id' => $id]);
}

function getWeeklyEventByClass($classes)
{
    $hippoDB = initDB();
    ;

    $classes = explode(',', $classes);
    $where = array( );
    foreach ($classes as $cls) {
        $whereExp[ ] = "class='$cls'";
    }

    $whereExp = implode(' OR ', $whereExp);

    $today = dbDate('today');
    $query = "SELECT * FROM events WHERE
                ( $whereExp ) AND status='VALID' AND date > '$today' GROUP BY gid";
    $res = $hippoDB->query($query);
    $entries = fetchEntries($res);

    // Add which day these events happening.
    $result = array( );
    foreach ($entries as $entry) {
        $entry[ 'day' ] = date('D', strtotime($entry[ 'date' ]));
        $result[] = $entry;
    }
    return $result;
}


function getLabmeetAndJC()
{
    return getWeeklyEventByClass('JOURNAL CLUB MEETING,LAB MEETING');
}

/**
 * @brief Is there a labmeet or JC on given slot/venue.
 *
 * @param $date
 * @param $starttime
 * @param $endtime
 * @param $entries
 *
 * @return
 */
function isThereAClashOnThisVenueSlot($day, $starttime, $endtime, $venue, $entries)
{
    $clashes = clashesOnThisVenueSlot($day, $starttime, $endtime, $venue, $entries);
    if (count($clashes) > 0) {
        return true;
    }
    return false;
}

function clashesOnThisVenueSlot($day, $starttime, $endtime, $venue, $entries)
{
    $days = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );

    if (! in_array($day, $days)) {
        $day = date('D', strtotime($day));
    }

    $clashes = array( );
    foreach ($entries as $entry) {
        if ($entry['day'] == $day) {
            if (strlen($venue) == 0 || $entry[ 'venue' ] == $venue) {
                $s1 = $entry[ 'start_time' ];
                $e1 = $entry[ 'end_time' ];
                if (isOverlappingTimeInterval($starttime, $endtime, $s1, $e1)) {
                    $clashes[ ] = $entry;
                }
            }
        }
    }
    return $clashes;
}



function labmeetOrJCOnThisVenueSlot($day, $starttime, $endtime, $venue, $entries = null)
{
    if (! $entries) {
        $entries = getLabmeetAndJC();
    }
    return clashesOnThisVenueSlot($day, $starttime, $endtime, $venue, $entries);
}

function getOccupiedSlots($year = null, $sem = null)
{
    if (! $year) {
        $year = getCurrentYear();
    }
    if (! $sem) {
        $sem = getCurrentSemester();
    }

    $res = $hippoDB->query(
        "SELECT slot FROM courses WHERE year='$year' AND semester='$sem'"
    );

    $slots = array_map(
        function ($x) {
            return $x['slot'];
        },
        fetchEntries($res, PDO::FETCH_ASSOC)
    );

    return $slots;
}

function getRunningCoursesOnThisVenue($venue, $date)
{
    $hippoDB = initDB();
    ;

    $year = getYear($date);
    $sem = getSemester($date);
    $courses = getTableEntries(
        'courses',
        'id',
        " ( end_date >= '$date' AND start_date <= '$date' )"
        . " AND venue='$venue' "
    );
    return $courses;
}

function getRunningCourseByID(string $cid, string $year='', string $sem='') : array
{
    $year = $year?$year:getCurrentYear();
    $sem = $sem?$sem:getCurrentSemester();
    return getTableEntry(
        'courses',
        'course_id,year,semester',
        ['course_id'=>$cid, 'year'=>$year, 'semester' => $sem ]
    );
}


function getRunningCoursesOnTheseSlotTiles($date, $tile)
{
    $hippoDB = initDB();
    ;

    $year = getCurrentYear();
    $sem = getCurrentSemester();
    $date = dbDate($date);

    // Slot is integer value.
    $slot = getSlotIdOfTile($tile);

    $courses = getTableEntries(
        'courses',
        'id',
        " ( end_date >= '$date' AND start_date <= '$date' )"
        . " AND slot='$slot' "
    );

    return $courses;
}

/**
 * @brief This function returns running courses on this day, venue, and slot.
 *
 * @param $venue
 * @param $date
 * @param $startTime
 * @param $endTime
 *
 * @return
 */
function runningCoursesOnThisVenueSlot($venue, $date, $startTime, $endTime)
{
    $courses = getRunningCoursesOnThisVenue($venue, $date);

    $day = date('D', strtotime($date));

    if (! $courses) {
        return null;
    }

    // Check if any of these courses slot is clasing with booking.
    $clashes = array( );
    foreach ($courses as $course) {
        $slotId = $course[ 'slot' ];
        $slots = getTableEntries('slots', 'groupid', "groupid='$slotId'");
        foreach ($slots as $sl) {
            // If this slot is on on the same day as of booking request, ignore
            // the course.
            if (strcasecmp($sl[ 'day' ], $day) !== 0) {
                continue;
            }

            $st = $sl[ 'start_time' ];
            $et = $sl[ 'end_time' ];

            if (isOverlappingTimeInterval($startTime, $endTime, $st, $et)) {
                $clashes[ $course[ 'id' ] ] = $course;
            }
        }
    }

    if (count($clashes) > 0) {
        return $clashes;
    }
    return null;
}

function getSlotInfo($id, $ignore = '')
{
    $hippoDB = initDB();
    ;
    $ignore = str_replace(' ', ',', $ignore);
    $ignoreTiles = explode(',', $ignore);
    $slots = getTableEntries('slots', 'id', "groupid='$id'");
    $res = array( );
    foreach ($slots as $sl) {
        // This slot is in ignore tile list i.e. a course is not using its slot
        // fully.
        if (in_array($sl['id'], $ignoreTiles)) {
            continue;
        }
        $res[] = $sl[ 'day' ] . ' ' . dbTime($sl[ 'start_time' ]) . '-'
            . dbTime($sl[ 'end_time' ]);
    }
    return  implode(', ', $res);
}


function getCourseById($cid)
{
    $c =  getTableEntry('courses_metadata', 'id,status', ['id' => $cid,'status'=>'VALID']);
    return $c;
}



/**
 * @brief Check if registration for courses is open.
 *
 * @return
 */
function isRegistrationOpen()
{
    $res = getTableEntry('conditional_tasks', 'id', array( 'id' => 'COURSE_REGISTRATION' ));
    if (strtotime($res[ 'end_date' ]) >= strtotime('today')) {
        return true;
    }

    return false;
}

function getSlotTiles($id)
{
    $tiles = getTableEntries('slots', 'groupid', "groupid='$id'");
    $result = array( );

    foreach ($tiles as $tile) {
        $result[ $tile[ 'id' ] ] = $tile;
    }

    return $result;
}

/**
 * @brief Is a course running on given tile e.g. 7A, 7B etc.
 *
 * @param $course
 * @param $tile
 *
 * @return
 */
function isCourseRunningOnThisTile($course, $tile)
{
    if (strpos($course['ignore_tiles'], $tile) !== 0) {
        return true;
    }
    return false;
}

function getCourseSlotTiles($course)
{
    $sid = $course[ 'slot' ];
    $tiles = getSlotTiles($sid);
    $result = array( );
    foreach ($tiles as $id => $tile) {
        if (isCourseRunningOnThisTile($course, $id)) {
            $result[ ] = $id;
        }
    }

    return implode(",", $result);
}


/* --------------------------------------------------------------------------*/
/**
 * @Synopsis So far how many CLASS events have happened.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function totalClassEvents()
{
    $courses = getTableEntries('courses');
    $numEvents = 0;
    foreach ($courses as $c) {
        $startDate = strtotime($c[ 'start_date' ]);
        $endDate = min(strtotime('now'), strtotime($c['end_date' ]));
        $slots = $c[ 'slot' ];
        $nTiles = count(getSlotTiles($slots));
        $nWeeks = intval(max(0, $endDate - $startDate) / (24*3600*7.0));

        // For each week, add this many events.
        $numEvents += $nWeeks * $nTiles;
    }

    return $numEvents;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis get table field info.
 *
 * @Param $tableName
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getTableFieldInfo($tableName) : array
{
    $schema = getTableSchema($tableName);
    $res = [];
    foreach ($schema as $row) {
        $type = $row['Type'];
        $fname = strtolower($row['Field']);
        if (preg_match("/^(enum|set)\((.*)\)$/", $type, $match)) {
            $fs = [];
            foreach (explode(",", $match[2]) as &$v) {
                $fs[] = str_replace("'", "", $v);
            }
            $res[$fname] = ['select', $fs];
        } else {
            if (__substr__('varchar', $type)) {
                $res[$fname] = ['text', ''];
            } elseif (__substr__('datetime', $type)) {
                $res[$fname] = ['datetime', ''];
            } elseif (__substr__('timestamp', $type)) {
                $res[$fname] = ['datetime', ''];
            } elseif (__substr__('date', $type)) {
                $res[$fname] = ['date', ''];
            } elseif (__substr__('time', $type)) {
                $res[$fname] = ['time', ''];
            } else {
                $res[$fname] = [$type, ''];
            }
        }
    }
    return $res;
}


/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get the Type of column from mysql tables.
 *
 * @Param $tablename
 * @Param $columnname
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getTableColumnTypes($tableName, $columnName)
{
    $hippoDB = initDB();
    ;
    $stmt = $hippoDB->prepare("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
    $stmt->execute();
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    $type = $column[ "Type" ];

    $res = array( );
    if (preg_match("/^(enum|set)\((.*)\)$/", $type, $match)) {
        foreach (explode(",", $match[2]) as $v) {
            $v = str_replace("'", "", $v);
            $res[] = $v;
        }
    } else {
        $res[] = $type;
    }

    return $res;
}

function getPIOrHost(string $loginOrEmail) : string
{
    $login = explode('@', $loginOrEmail)[0];

    // A. Search in table logins.
    $hippoDB = initDB();
    ;
    $row = getTableEntry('logins', "login", array( 'login' => $login ));
    if (__get__($row, 'pi_or_host', '')) {
        return $row[ 'pi_or_host' ];
    }

    // B. Search in previous AWS databases.
    $awses = getMyAws($login);
    if (count($awses) > 0) {
        $mostRecentAWS = $awses[0];
        $piOrHost = $mostRecentAWS[ 'supervisor_1'];
        if ($piOrHost) {
            // Update PI or HOST table.
            updateTable(
                'logins',
                'login',
                'pi_or_host',
                array( 'login' => $login, 'pi_or_host' => $piOrHost )
            );
        }
        return $mostRecentAWS[ 'supervisor_1'];
    }
    return '';
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Find all courses running on given venue/slot and between given
 * dates.
 *
 * @Param $venue
 * @Param $slot
 * @Param $start
 * @Param $end
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getCoursesAtThisVenueSlotBetweenDates($venue, $slot, $start, $end)
{
    $whereExpr = "(end_date > '$start' AND start_date < '$end' )
                    AND slot='$slot' AND venue='$venue'";
    $courses = getTableEntries('courses', 'start_date', $whereExpr);
    return $courses;
}


/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get the specialization available for student.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getAllSpecialization() : array
{
    $hippoDB = initDB();
    ;
    $res = $hippoDB->query('SELECT DISTINCT(specialization) FROM faculty');
    return fetchEntries($res);
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get specialization of given login.
 *
 * @Param $speaker (usually student, could be faculty as well).
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getLoginSpecialization($login)
{
    $hippoDB = initDB();
    ;
    $res = $hippoDB->query("SELECT specialization FROM logins WHERE login='$login'");
    $res = $res->fetch(PDO::FETCH_ASSOC);
    return trim($res[ 'specialization' ]);
}

function getFacultySpecialization(string $email)
{
    $hippoDB = initDB();
    ;
    $res = $hippoDB->query("SELECT specialization FROM faculty WHERE email='$email'");
    $res = $res->fetch(PDO::FETCH_ASSOC);
    if ($res) {
        return trim($res['specialization']);
    }
    return 'UNKNOWN';
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get login specialization, if not found, fetch the PIEmail
 * specialization from faculty database.
 *
 * @Param $login
 * @Param $PIEmail
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getSpecialization($login, $PIEmail = '')
{
    $specialization = getLoginSpecialization($login);
    if (! $specialization) {
        if ($PIEmail) {
            $specialization = getFacultySpecialization($PIEmail);
        }
    }

    if (! trim($specialization)) {
        $specialization = 'UNSPECIFIED';
    }

    return $specialization;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Generate slot map.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getSlotMap($slots = array( ))
{
    if (! $slots) {
        $slots = getTableEntries('slots', 'groupid');
    }

    $slotMap = array();
    foreach ($slots as $s) {
        if (intval($s[ 'groupid' ]) == 0) {
            continue;
        }

        $slotGroupId = $s[ 'groupid' ];
        if (! array_key_exists($slotGroupId, $slotMap)) {
            $slotMap[ $slotGroupId ] = $slotGroupId .  ' (' . $s['day'] . ':'
            . humanReadableTime($s[ 'start_time' ])
            . '-' . humanReadableTime($s['end_time'])
            . ')';
        } else {
            $slotMap[ $slotGroupId ] .= ' (' . $s['day'] . ':'
            . humanReadableTime($s[ 'start_time' ])
            . '-' . humanReadableTime($s['end_time'])
            . ')';
        }
    }
    return $slotMap;
}

/////////////////////////////////////////////////////////////////////////////////
// JOURNAL CLUBS
//
///////////////////////////////////////////////////////////////////////////////

function getJCAdmins($jc_id)
{
    return getTableEntries(
        'jc_subscriptions',
        'login',
        "jc_id='$jc_id' AND subscription_type='ADMIN'"
    );
}

function getJournalClubs($status = 'ACTIVE')
{
    return getTableEntries('journal_clubs', 'id', "status='$status'");
}

function isSubscribedToJC($login, $jc_id)
{
    $res = getTableEntry(
        'jc_subscriptions',
        'login,jc_id,status',
        array( 'login' => $login, 'jc_id' => $jc_id, 'status' => 'VALID' )
    );

    if ($res) {
        return true;
    }

    return false;
}

function getJCInfo($jc)
{
    if (is_array($jc)) {
        $jc_id = __get__($jc, 'jc_id', $jc['id']);
    } elseif (is_string($jc)) {
        $jc_id = $jc;
    }

    return getTableEntry('journal_clubs', 'id', array( 'id' => $jc_id ));
}


/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Return the list of JC user is subscribed to.
 *
 * @Param $login
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getUserJCs($login)
{
    if (! $login) {
        return array( );
    }

    return getTableEntries('jc_subscriptions', 'login', "login='$login' AND status='VALID' ");
}

function getMyJCs()
{
    return getUserJCs(whoAmI());
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get JC presentations for given Journal Club for given day.
 *
 * @Param $jcID
 * @Param $date
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getUpcomingJCPresentations($jcID = '', $date = 'today')
{
    $date = dbDate($date);
    $whereExpr = "date >= '$date'";
    if (trim($jcID)) {
        $whereExpr .= " AND jc_id='$jcID' AND status='VALID'";
    }

    $whereExpr .= " AND status='VALID' AND CHAR_LENGTH(presenter) > 1";
    $jcs = getTableEntries('jc_presentations', 'date', $whereExpr);
    return $jcs;
}

function getUpcomingJCPresentationsOfUser($presenter, $jcID, $date = 'today')
{
    $date = dbDate($date);
    return getTableEntries(
        'jc_presentations',
        'date',
        "date >= '$date' AND presenter='$presenter'
            AND jc_id='$jcID' AND status='VALID' "
    );
}

function getUpcomingPresentationsOfUser($presenter, $date = 'today')
{
    $date = dbDate($date);
    return getTableEntries(
        'jc_presentations',
        'date',
        "date >= '$date' AND (presenter='$presenter' OR presenter LIKE '$presenter\@%') 
            AND status='VALID' "
    );
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get JC presentations.
 *
 * @Param $jc
 * @Param $user
 * @Param $date
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getJCPresentation($jc, $presenter = '', $date = 'today')
{
    $date = dbDate($date);
    $keys = 'jc_id,date';

    if ($presenter) {
        $keys .= ',presenter';
    }

    return getTableEntry(
        'jc_presentations',
        $keys,
        array( 'jc_id' => $jc, 'presenter' => $presenter, 'date' => $date )
    );
}

function getJCPresentations($jc, $date = '', $presenter = '')
{
    $whereExpr = "status='VALID' AND jc_id='$jc' ";
    if ($date) {
        $date = dbDate($date);
        $whereExpr .= " AND date='$date' ";
    }

    if ($presenter) {
        $whereExpr .= " AND presenter='$presenter' ";
    }

    return getTableEntries('jc_presentations', 'date', $whereExpr);
}


function isJCAdmin($user)
{
    $res = getTableEntry(
        'jc_subscriptions',
        'status,login,subscription_type',
        ['login' => $user, 'subscription_type'=>'ADMIN','status'=>'VALID']
    );
    if ($res) {
        return true;
    }
    return false;
}

function getJCForWhichUserIsAdmin($user)
{
    return getTableEntries(
        'jc_subscriptions',
        'jc_id',
        "login='$user' AND subscription_type='ADMIN' AND status='VALID'"
    );
}

function getJCSubscriptions($jc_id)
{
    return getTableEntries(
        'jc_subscriptions',
        'login',
        "jc_id='$jc_id' AND status='VALID'"
    );
}

function getAllPresentationsBefore($date, $presenter = '')
{
    $date = dbDate($date);
    $whereExpr = " status='VALID' AND date <= '$date' ";
    if ($presenter) {
        $whereExpr .= " AND presenter='$presenter' ";
    }

    return getTableEntries('jc_presentations', 'date', $whereExpr);
}

function getAllAdminsOfJC($jc_id)
{
    $admins = getTableEntries(
        'jc_subscriptions',
        'login',
        "status='VALID' AND subscription_type='ADMIN' AND jc_id='$jc_id'"
    );

    $res = array( );
    foreach ($admins as $admin) {
        $res[ $admin['login'] ] = loginToHTML($admin['login']);
    }

    return $res;
}

function getUserVote($voteId, $voter)
{
    $res = getTableEntry(
        'votes',
        'id,voter,status',
        array( 'id' => $voteId, 'voter' => $voter, 'status' => 'VALID' )
    );
    return $res;
}

function getMyVote($voteId)
{
    return getUserVote($voteId, whoAmI());
}

function getVotes($voteId)
{
    return getTableEntries('votes', '', "id='$voteId' AND status='VALID'");
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get the config parameters from database.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getConfigFromDB() : array
{
    $config = array( );
    foreach (getTableEntries('config') as $row) {
        $config[ $row['id'] ] = $row[ 'value' ];
    }
    return $config;
}

function getConfigValue($key, $config = null)
{
    if (! $config) {
        $config = getConfigFromDB();
    }
    $val = __get__($config, $key, '');
    return $val;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get a clickbale url for a query.
 *
 * @Param $idOrExternalId
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getQueryWithIdOrExtId($idOrExternalId)
{
    $res = executeQuery(
        "SELECT  * FROM queries WHERE
        (id='$idOrExternalId' OR external_id='$idOrExternalId')
            AND status='PENDING'"
    );

    if (! $res) {
        return -1;
    }

    return intval($res[0]['id']);
}

function getActiveJCs()
{
    return getTableEntries('journal_clubs', 'id', "status='ACTIVE'");
}

function pickPresenter($jcID, $picker = 'random', $gap_between_presentations_in_months = 6)
{
    $logins = getJCSubscriptions($jcID);

    $suitable = array( );
    foreach ($logins as $login) {
        $presenter = $login[ 'login' ];

        if (! $presenter) {
            continue;
        }

        $onOrBefore = strtotime('now') + $gap_between_presentations_in_months * 30 * 24 * 3600;

        // Get presentations of this USER in lats
        // gap_between_presentations_in_months months. It does not matter in
        // which JC she has given presentations.
        $presentations = getAllPresentationsBefore($onOrBefore, $presenter);
        if (count($presentations)  > 0) {
            continue;
        }

        $upcoming = getUpcomingJCPresentationsOfUser($jcID, $presenter);
        if ($upcoming) {
            echo printInfo("user $presenter has upcoming JC");
            continue;
        }

        $suitable[] = $presenter;
        if ($picker == 'round_robin') {
            return $presenter;
        }
    }

    // Else return a random sample.
    return $suitable[ mt_rand(0, count($suitable) - 1) ];
}

function getNumberOfRequetsInGroup(string $gid) : int
{
    $res = executeQuery("SELECT COUNT(rid) FROM bookmyvenue_requests WHERE gid='$gid'");
    if ($res) {
        return intval($res[0]['COUNT(rid)']);
    } else {
        return 0;
    }
}

function getNumberOfRowsInTable($tableName, $where) : int
{
    $res = executeQuery("SELECT COUNT(*) FROM $tableName WHERE $where");
    if ($res) {
        return intval($res[0]['COUNT(*)']);
    } else {
        return 0;
    }
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Check if students has given feedback.
 *
 * @Param $student
 * @Param $cid
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function numQuestionsNotAnswered($student, $year, $sem, $cid) : int
{
    $questions = getTableEntries('course_feedback_questions', 'id', "status='VALID'");

    // Get all the response for this year, semester and course id.
    $nQuesNotAnswered = count($questions);

    $res = array();
    foreach ($questions as $q) {
        $res = getTableEntry(
            'course_feedback_responses',
            'question_id,login,course_id,year,semester',
            ['login'=>$student, 'year'=>$year, 'semester'=>$sem, 'course_id'=>$cid, 'question_id'=>$q['id']]
        );
        if ($res) {
            $nQuesNotAnswered -= 1;
        }
    }
    return $nQuesNotAnswered;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get all questions for given category.
 *
 * @Param $category
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getQuestionsWithCategory($category) : array
{
    $res = array();
    $entries = getTableEntries('question_bank', 'id', "status='VALID' AND category='$category'");

    foreach ($entries as $i => $q) {
        $res[$q['subcategory']][] = $q;
    }

    return $res;
}

function getCourseFeedbackQuestions() : array
{
    $qsMap = [];
    $entries = getTableEntries('course_feedback_questions', 'id', "status='VALID'");
    foreach ($entries as $e) { 
        $qsMap[$e['category']][] = $e;
    }
    return $qsMap;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Get old course feedback.
 *
 * @Param $year
 * @Param $semester
 * @Param $cid
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function getCourseSpecificFeedback(string $year, string $semester, string $cid, string $login='') : array
{
    $responses = array();
    if (! $login) {
        $login = whoAmI();
    }

    $entries = getTableEntries(
        'course_feedback_responses',
        'question_id',
        "login='$login' AND course_id='$cid' AND year='$year' AND semester='$semester' AND status='VALID'"
    );

    foreach ($entries as $entry) {
        $responses[$entry['question_id']] = $entry;
    }

    return $responses;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis API function.
 *
 * @Param $year
 * @Param $semester
 * @Param $cid
 * @Param $login
 *
 * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getCourseFeedback(string $year, string $semester, string $cid, string $login) : array
{
    $where = "course_id='$cid' AND year='$year' AND semester='$semester' AND status='VALID' ";
    $where .= " AND login='$login' ";

    $questions = [];

    $entries = [];
    foreach(getTableEntries('course_feedback_responses', 'question_id', $where) as $entry) {
        $entries[$entry['question_id']][$entry['instructor_email']] = [
            'response' => $entry['response']
            , 'last_modified_on' => $entry['last_modified_on']
            , 'timestamp' => $entry['timestamp']
        ];
    }

    $instructors = getCourseInstructorsList($cid, $year, $semester);

    // Question which are not answered.
    $numUnanswered = 0;
    $questions = getTableEntries('course_feedback_questions', 'id', "status='VALID'", "id,type");
    foreach($questions as $q) {
        if(__get__($entries, $q['id']))
            continue;

        $numUnanswered += 1;
        if($q['type'] === 'INSTRUCTOR SPECIFIC') {
            foreach($instructors as &$inst) {
                $entries[$q['id']][$inst[0]] = [ 
                    'response' => "", 'last_modified_on' => "", 'timestamp' => ""
                ];
            }
        }
        else {
            $entries[$q['id']][""] = ['response' => "", 'last_modified_on' => "", 'timestamp' => ""];
        }
    }

    $course = getRunningCourseByID($cid, $year, $semester);

    // Don't allow feedback once it is fully submitted or 6 months have passed.
    $editable = [true, ''];
    if(strtotime('now') > strtotime($course['end_date']) + 6*30*86400)
        $editable = [false, "6 months have passed since course completion"];
    else if(strtotime('now') < strtotime($course['end_date']))
        $editable = [false, "Course is yet to complete."];
    else if($numUnanswered == 0)
        $editable = [false, "You have completed the form."];

    return ["responses" => $entries, "unanswered"=>$numUnanswered, 'editable'=>$editable];
}

function getCourseThisFeedback(string $year, string $semester, string $cid, string $login, string $instructor_email) : array
{
    $responses = array();
    if (! $login) {
        $login = whoAmI();
    }

    $where = "course_id='$cid' AND year='$year' AND semester='$semester' AND status='VALID' ";
    $where .= " AND login='$login' ";
    $where .= " AND instructor_email='$instructor_email' ";
    return getTableEntry('course_feedback_responses', 'question_id', $where);
}

function getInstructorSpecificFeedback(string $year, string $semester, string $cid, string $email, $login='')
{
    $responses = array();

    if (! $login) {
        $login = whoAmI();
    }

    $entries = getTableEntries(
        'course_feedback_responses',
        'question_id',
        "login='$login' AND course_id='$cid' AND year='$year' AND semester='$semester' AND 
            instructor_email='$email' AND status='VALID'"
    );

    foreach ($entries as $entry) {
        $responses[$entry['question_id']] = $entry;
    }

    return $responses;
}


/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Find anyone with given login or email.
 *
 * @Param $loginOrEmail
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function findAnyoneWithLoginOrEmail($loginOrEmail)
{
    return executeQuery("SELECT * FROM logins WHERE login='$loginOrEmail' OR email='$loginOrEmail'");
}

function getSchedulingRequests(string $user) : array
{
    $today = dbDate('today');

    $res = getTableEntries(
        'aws_scheduling_request',
        'id',
        "(first_preference > '$today' OR second_preference > '$today')
                AND speaker='$user' AND status!='CANCELLED' "
    );

    if (count($res) > 0) {
        return $res[0];
    }

    return [];
}

function isAWSHoliday($date) : bool
{
    $h = getTableEntry('holidays', 'date,schedule_talk_or_aws', [ 'date' => $date, 'schedule_talk_or_aws' => 'YES' ]);

    if ($h) {
        return true;
    }

    return false;
}

function updateCourseWaitlist(string $cid, string $year, string $semester): bool
{
    $data = [ 'course_id' => $cid, 'year' => $year, 'semester' => $semester ];
    $course = getTableEntry('courses', 'course_id,year,semester', $data);

    if (! $course) {
        return false;
    }

    // Otherwise get the waitlist.
    $nmax = intval($course['max_registration']);
    $curEnroll = count(getCourseRegistrations($cid, $year, $semester));

    $data['status'] = 'WAITLIST';
    $waitList = getTableEntries(
        'course_registration',
        'registered_on',
        "status='WAITLIST' AND year='$year' AND semester='$semester'"
    );

    for ($i = 0; $i < min(count($waitList), $nmax - $curEnroll); $i++) {
        $toUpdate = $waitList[$i];
        $res = updateTable(
            'course_registration',
            'student_id,course_id,year,semester',
            'status',
            [ 'course_id'=> $cid, 'year'=>$year, 'semester'=>$semester
                , 'student_id'=>$toUpdate['student_id']
                , 'status' => 'VALID' ]
        );
    }
    return true;
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis
 *
 * @Param $course
 * @Param array
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function registerForCourse(array $course, array $data, bool $sendEmail=true): array
{
    $res = ['success'=>true, 'msg' => ''];
    $data['last_modified_on'] = dbDateTime('now');
    $data['registered_on'] = dbDateTime('now');

    // This is not very clean solution.
    if (__get__($data, 'status', 'VALID') !== 'DROPPED') {
        $data['status'] = 'VALID';
        $what = $data['type'] . 'ed';
    } else {
        $what = 'DROPPed';
    }

    $data['year'] = $course['year'];
    $data['semester'] = $course['semester'];

    $cid = $course['course_id'];
    if (! $cid) {
        $res['success'] = false;
        $res['msg'] = "Empty course id";
        return $res;
    }

    // If user has asked for AUDIT but course does not allow auditing,
    // do not register and raise and error.
    if ($course['is_audit_allowed'] == 'NO' && $data['type'] == 'AUDIT') {
        $res['msg'] = "Sorry but course $cid does not allow <tt>AUDIT</tt>.";
        return $res;
    }

    // If number of students are over the number of allowed students
    // then add student to waiting list and raise a flag.
    if ($course['max_registration'] > 0) {
        $numEnrollments = count(getCourseRegistrations($cid, $course['year'], $course['semester']));
        if (intval($numEnrollments) >= intval($course['max_registration'])) {
            $data['status'] = 'WAITLIST';
            $res['msg'] .= p(
                "<i class=\"fa fa-flag fa-2x\"></i>
                Number of registrations have reached the limit. I've added you to 
                <tt>WAITLIST</tt>. Please contact academic office or your instructor about 
                the policy on <tt>WAITLIST</tt>. By default, <tt>WAITLIST</tt> means 
                <tt>NO REGISTRATION</tt>."
            );
        }
    }

    $data = array_merge($data, $course);

    // If already registered then update the type else register new.
    $r = insertOrUpdateTable(
        'course_registration',
        'student_id,semester,year,type,course_id,registered_on,last_modified_on',
        'type,last_modified_on,status',
        $data
    );

    // Update waiting lists.
    if (strtoupper($data['status']) === 'DROPPED') {
        updateCourseWaitlist($data['course_id'], $data['year'], $data['semester']);
    }

    if (! $r) {
        $res['msg'] .= p("Failed to $what the course " . $data['course_id']);
        $res['success'] = false;
        return $res;
    }

    $res['msg'] .= p("Successfully $what course ".$data['course_id'].'.');

    if ($sendEmail) {
        // Send email to user.
        $type = $data['type'];
        $cid = $data['course_id'];
        $login = getLoginInfo($data['student_id'], true, true);
        $msg = p("Dear " . arrayToName($login, true));
        $msg .= p("Hippo has successfully updated your courses.");

        $sem = getCurrentSemester();
        $year = getCurrentYear();

        // User courses and slots.
        $myCourses = getMyCourses($sem, $year, $user=$data['student_id']);
        if (count($myCourses)>0) {
            $msg .= p("List of your courses this semester.");
            foreach ($myCourses as $c) {
                $msg .= arrayToVerticalTableHTML($c, 'info', '', 'grade,grade_is_given_on,status');
            }
        } else {
            $msg .= p("You are registered for no course this semester.");
        }

        $to = $login['email'];
        $cname = getCourseName($cid);
        sendHTMLEmail($msg, "Successfully $what the course '$cname ($cid)'", $to);
    }
    return $res;
}
