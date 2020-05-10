<?php

function trueOnGivenDayAndTime(string $day, string $time): bool
{
    $now = strtotime('today');
    if ($now != strtotime($day)) {
        return false;
    }

    $away = strtotime('now') - strtotime("$time");
    if ($away >= -1 && $away < 15 * 60) {
        return true;
    }

    return false;
}

function isNowEqualsGivenDayAndTime(string $day, string $time): bool
{
    return trueOnGivenDayAndTime($day, $time);
}
