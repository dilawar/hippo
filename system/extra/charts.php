<?php

function getChartsPublications(): array
{
    // default styles.
    $charts = [];

     /*  This section count the publications from NCBS and PUBMED. */
    $pubMed = getTableEntries('publications', 'date', "source='PUBMED' AND date < NOW()");
    $pubYearWisePUBMED = [];
    foreach ($pubMed as $e) {
        $year = intval(date('Y', strtotime($e['date'])));
        $sha = $e['sha512'];
        if ($year > 1990)
            $pubYearWisePUBMED[$year] = __get__($pubYearWisePUBMED, $year, 0) + 1;
    }

    $data = [];
    foreach($pubYearWisePUBMED as $key => $val )
        $data[] = [$key, $val];

    return [
        'type' => 'line',
        'xlabel' => 'year',
        'ylabel' => 'Count',
        'title' => 'No of publications (source: PUBMED)',
        'data'=> $data, 
    ];

}

function getChartBookingRequests() : array
{
    $upto = dbDate('tomorrow');
    $requests = getTableEntries('bookmyvenue_requests', 'date'
        , "date >= '2017-02-28' AND date <= '$upto'"
        , 'date,status,start_time,end_time,last_modified_on');
    $nApproved = 0;
    $nRejected = 0;
    $nCancelled = 0;
    $nPending = 0;
    $nOther = 0;
    $timeForAction = array( );

    $firstDate = $requests[0]['date'];
    $lastDate = end($requests)['date'];
    $timeInterval = strtotime($lastDate) - strtotime($firstDate);

    foreach ($requests as $r) {
        if ($r[ 'status' ] == 'PENDING') {
            $nPending += 1;
        } elseif ($r[ 'status' ] == 'APPROVED') {
            $nApproved += 1;
        } elseif ($r[ 'status' ] == 'REJECTED') {
            $nRejected += 1;
        } elseif ($r[ 'status' ] == 'CANCELLED') {
            $nCancelled += 1;
        } else {
            $nOther += 1;
        }

        // Time take to approve a request, in hours
        if ($r[ 'last_modified_on' ]) {
            $time = strtotime($r['date'] . ' ' . $r[ 'start_time' ])
                - strtotime($r['last_modified_on']);
            $time = $time / (24 * 3600.0);
            array_push($timeForAction, array($time, 1));
        }
    }

    // rate per day.
    $rateOfRequests = 24 * 3600.0 * count($requests) / (1.0 * $timeInterval);

    /* Venue usage time.  */
    $events = getTableEntries(
        'events',
        'date',
        "status='VALID' AND date < '$upto'",
        'date,start_time,end_time,venue,class'
    );
    return [];
}

function getChartVenueUsage() : array
{
    /*
     * VENUE USAGE CHART
     */
    $venueUsageTime = array( );
    foreach ($events as $e) {
        $time = (strtotime($e[ 'end_time' ]) - strtotime($e[ 'start_time' ])) / 3600.0;
        $venue = $e[ 'venue' ];
        $venueUsageTime[ $venue ] = __get__($venueUsageTime, $venue, 0.0) + $time;
    }

    $venueUsagePie = array_map( function($value, $key) { 
        return [ $key, $value ];
    }, array_values($venueUsageTime), array_keys($venueUsageTime));

    return ['type' => 'pie',
        'title' => 'Events by class',
        'xlabel' => 'class',
        'ylabel' => 'Count',
        'data'=> $venueUsagePie,
    ];
}


function getChartResearchGroupSize(): array
{
    /* AWS GROUPS */
    $speakers = getAWSSpeakers();
    $awsSpeakers = array( );
    foreach ($speakers as $speaker) {
        $pi = getPIOrHost($speaker[ 'login' ]);
        $spec = getSpecialization($speaker[ 'login' ], $pi);
        $awsSpeakers[ $spec ] = __get__($awsSpeakers, $spec, 0) + 1;
    }

    return ['type' => 'pie',
        'title' => 'Size of reseach groups (by AWS)',
        // 'ylabel' => 'Research area',
        'data'=> $awsSpeakers,
    ];
}

function getChartThesisSeminar()
{
    $thesisSeminars = getTableEntries('talks', 'class', "class='THESIS SEMINAR'", 'id');
    $thesisSemPerYear = array( );
    foreach($thesisSeminars as $ts)
    {
        $ev = getEventsOfTalkId($ts['id']);
        if(! $ev)
            continue;
        $year = explode('-', $ev['date'])[0];
        $thesisSemPerYear[$year] = __get__($thesisSemPerYear, $year, 0)+1;
    }

    $bar = [];
    foreach($thesisSemPerYear as $year => $count)
        $bar[] = [$year, $count];

    return ['type' => 'column',
        'title' => 'Thesis seminar (per year)',
        // 'ylabel' => 'Research area',
        'data'=> $bar,
    ];
}

function getChartAWS()
{
    $res = executeQuery("SELECT YEAR(date), COUNT(id) FROM
        annual_work_seminars WHERE YEAR(date) > '2012' GROUP BY YEAR(date)");

    $data = [];
    foreach($res as $e)
        $data[] = array_values($e);

    return ['type' => 'column',
        'title' => 'AWS (per year)',
        'data'=> $data,
    ];

}

function getChartCourseRatings(): array
{
    $all = getCourseFeedbackApi();
    return ['type' => 'column',
        'title' => 'Courses ratings (Max 10)',
        'data'=> $all['score'],
    ];
}

function getCharts() : array
{
    $charts = [];
    $charts['Publications'] = getChartsPublications();
    // $charts['Course ratings (Max 10)'] = getChartCourseRatings();
    $charts['AWS per year'] = getChartAWS();
    $charts['Thesis Seminar per year'] = getChartThesisSeminar();
    $charts['Research area size'] = getChartResearchGroupSize();
    return $charts;
}

?>
