<?php

function update_publishing_database()
{
    $extra = 'datetype=pdat&reldate=14';
    $url = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?'
        . "db=pubmed&retmode=json&$extra"
        . '&term=national+centre+for+biological+sciences[Affiliation]';

    $data = json_decode(file_get_contents($url, true));

    $items = $data->esearchresult->idlist;
    $idlist = implode(',', $items);

    $url = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/';
    $url .= "esummary.fcgi?db=pubmed&id=$idlist&retmode=json";
    $res = json_decode(file_get_contents($url, true));
    $items = $res->result;

    $data = [];
    foreach ($items->uids as $uid) {
        $item = $items->$uid;
        $data['title'] = $item->title;
        $data['sha512'] = hash('sha512', $data['title']);
        $data['abstract'] = 'NA';
        $data['publisher'] = $item->fulljournalname;
        $data['type'] = $item->pubtype[0];
        $data['date'] = dbDate($item->pubdate);
        $data['modified_on'] = dbDate('now');
        $data['external_id'] = "PUBMED.$uid";
        $data['doi'] = $item->elocationid;
        $data['json_metadata'] = json_encode($item);

        // If $sha is not in the table, create one entry. Notify the
        // academic topic.
        $entry = getTableEntries('publications', 'sha512', $data);
        if (!$entry) {
            insertIntoTable('publications', array_keys($data), $data);
            $title = $data['title'];
            $title .= ' <br />DOI ' . $data['doi'];
            sendFirebaseCloudMessage('academic', 'A new publication', $title);
        }
    }
}

function update_database_cron()
{
    echo printInfo('Monday, removing students who have given PRE_SYNOPSIS SEMINAR and thesis SEMINAR');

    // In last year.
    $cutoff = dbDate(strtotime('today') - 365 * 86400);
    $presynAWS = getTableEntries('annual_work_seminars', 'date', "IS_PRESYNOPSIS_SEMINAR='YES' AND date > '$cutoff'");

    foreach ($presynAWS as $aws) {
        $speaker = $aws['speaker'];
        if (isEligibleForAWS($speaker)) {
            echo printInfo("Removing $speaker from AWS list");
            removeAWSSpeakerFromList($speaker);
        }
    }

    /* Now removing students with THESIS SEMINAR */
    echo printInfo('Removing students who have given thesis seminar');
    $thesisSeminars = getTableEntries('talks', 'id', "class='THESIS SEMINAR' OR class='PRESYNOPSIS THESIS SEMINAR'"
    );

    foreach ($thesisSeminars as $talk) {
        $speaker = getSpeakerByID($talk['speaker_id']) or getSpeakerByName($talk['speaker']);
        $login = findAnyoneWithEmail(__get__($speaker, 'email', ''));
        if (__get__($login, 'login', '')) {
            $login = $login['login'];
            if (isEligibleForAWS($login)) {
                echo printInfo("Removing $speaker from AWS roster");
                removeAWSSpeakerFromList($login);
            }
        }
    }

    echo printInfo('Cleanup login');
    $badLogins = getTableEntries('logins', 'login', "(first_name IS NULL OR first_name='') OR first_name=last_name AND status='ACTIVE'"
    );

    echo printInfo('Total ' . count($badLogins) . ' are found');
    foreach ($badLogins as $l) {
        $login = __get__($l, 'login', '');
        if ((! $login) || (strpos($login, 'office') !== false)) {
            continue;
        }

        echo printWarning("Login $login is bad");
        $ldap = getUserInfoFromLdap($login);
        if ($ldap) {
            $ldap['login'] = $login;
            $res = updateTable('logins', 'login', 'first_name,last_name,email', $ldap);
            if ($res) {
                echo printInfo(" ... $login is fixed");
            }
        }
    }

    echo printInfo('Removing all MSc students with at-least 1 aws');
    $logins = getTableEntries('logins', 'login', "title='MSC' AND eligible_for_aws='YES'");
    foreach ($logins as $i => $msc) {
        $speaker = $msc['login'];
        $aws = getAWSSpeakers($msc['login']);
        if (count($aws) > 0) {
            echo printInfo("MSc student $speaker has given AWS. Remove him/her");
            removeAWSSpeakerFromList($speaker);
        }
    }


    // Remove those users who are not active on LDAP.
    echo printInfo('Removing inactive accounts');
    $logins = getTableEntries('logins', 'login', "status='ACTIVE'");
    $toInactivate = [];
    foreach ($logins as $login) {
        // login can be an email.
        $id = explode('@', $login['login'])[0];

        if (!$id) {
            continue;
        }
        $ldap = getUserInfoFromLdap($id);
        if (!$ldap) {
            $toInactivate[] = $id;
        }

        $isActive = strtolower($ldap['is_active']);
        if ('false' === $isActive) {
            $toInactivate[] = $id;
        }
    }
    if (count($toInactivate) > 0) {
        inactiveAccounts($toInactivate);
    }
}

// Their talksa are cancelled.
function cleanupOrphanedEvents()
{
    echo printInfo('Cleaning up orphaned events');
    $today = dbDate('today');
    $events = getTableEntries('events', 'date', "date>'$today' AND status='VALID' AND external_id !='SELF.-1'");
    foreach ($events as $ev) {
        $talkID = explode('.', $ev['external_id'])[1];
        $talk = getTableEntries('talks', 'id', "id='$talkID' AND status='VALID'");
        if (!$talk) {
            echo printWarning('Associated talk with this event has been cancelled. 
                Cancelling this event as well.');
            echo printInfo($ev['title']);

            $ev['status'] = 'INVALID';
            $res = updateTable('events', 'gid,eid', 'status', $ev);
        }
    }
}
