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
        login,email,last_name,middle_name,first_name,
        CONCAT_WS(' ',first_name,last_name) as name
        FROM logins WHERE status='ACTIVE' 
        AND first_name IS NOT NULL AND  first_name != ''
        $where
        AND (login LIKE '%$q%' OR first_name LIKE '%$q%' OR last_name LIKE '%$q%')
        ");
}

function searchInSpeakers($q): array
{
    return executeQuery("SELECT *, 
        CONCAT_WS(' ',`first_name`,`last_name`) as name 
        FROM speakers WHERE 
        first_name IS NOT NULL AND  first_name != ''
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


?>

