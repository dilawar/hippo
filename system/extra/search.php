<?php

require_once __DIR__ . '/methods.php';

/*
 * NOTE on this section:
 *    CONCAT: if any value is NULL, CONCATed value will be null. 
 *    See https://stackoverflow.com/a/15741336/1805129
 */

function searchInLogins(string $q, $where=''): array
{
    return executeQuery("SELECT 
        login,email,last_name,middle_name,first_name,pi_or_host,
        CONCAT_WS(' ',first_name,last_name) as name
        FROM logins WHERE status!='EXPIRED' 
        AND login != ''
        AND first_name IS NOT NULL AND  first_name != ''
        $where
        AND (login LIKE '%$q%' OR first_name LIKE '%$q%' OR last_name LIKE '%$q%')
        GROUP BY email
        ");
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Search speaker. The 
    *
    * @Param q string. Could be id, or search in names, email etc.
    *
    * @Returns   
    */
/* ----------------------------------------------------------------------------*/
function searchInSpeakers(string $q): array
{
    // This is ID.
    if(is_numeric($q))
        return executeQuery("SELECT *, 
            CONCAT_WS(' ',`first_name`,`last_name`) as name 
            FROM speakers WHERE id > 0 
            AND first_name IS NOT NULL AND  first_name != '' AND id='$q'
            ");

    return executeQuery("SELECT *, 
        CONCAT_WS(' ',`first_name`,`last_name`) as name 
        FROM speakers WHERE id > 0 
        AND first_name IS NOT NULL AND  first_name != ''
        AND (email LIKE '%$q%' OR first_name LIKE '%$q%' OR last_name LIKE '%$q%')
        ");
}

function searchInFaculty($q): array
{
    return executeQuery("SELECT *, 
        CONCAT_WS(' ',`first_name`,`last_name`) as name
        FROM faculty WHERE 
        first_name IS NOT NULL AND  first_name != ''
        AND (email LIKE '%$q%' OR first_name LIKE '%$q%' OR last_name LIKE '%$q%')
        ");
}

function searchSupervisors($q): array
{
    return executeQuery("SELECT *, 
        CONCAT_WS(' ',`first_name`,`last_name`) as name
        FROM supervisors WHERE 
        first_name IS NOT NULL AND  first_name != ''
        AND (email LIKE '%$q%' OR first_name LIKE '%$q%' OR last_name LIKE '%$q%')
        ");
}

function searchInTalks($q): array 
{
    return executeQuery("SELECT * FROM talks WHERE 
        id IS NOT NULL AND  status = 'VALID'
        AND (host LIKE '%$q%' OR speaker LIKE '%$q%' OR title LIKE '%$q%')
        ");
}

function searchInEvents($q): array 
{
    return executeQuery("SELECT * FROM events WHERE 
        date < NOW() AND
        (gid='$q' OR created_by LIKE '$q%' OR date='$q' OR title LIKE '%$q%')
        ORDER BY date DESC
        LIMIT 20
        ");
}
?>

