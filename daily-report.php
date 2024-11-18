<?php
include "load.php";
$ezzeTeamsModel = new EzzeTeamsModel();

use Shuchkin\SimpleXLSXGen;

if (strtolower(strip_tags($_GET['command'])) == 'daily-report') {
    $range = false;
    if (isset($_GET['data-hour-range'])) {
        $range = true;
        $timeNow = date("H:i");

        if (isset($_GET['date'])) {
            $pattern = "/([01]?[0-9]|2[0-3]):[0-5][0-9]/";
            if (preg_match($pattern, $_GET['date'], $matches)) {
                $dateEnd = $_GET['date'];
            } else {
                $dateEnd = $_GET['date'] . " " . $timeNow;
            }
            $dateStart = date("Y-m-d H:i", strtotime("-".$_GET['data-hour-range']." hours", strtotime($dateEnd)));
        } else {
            $dateEnd = date("Y-m-d") . " ". $timeNow;
            $dateStart = date("Y-m-d H:i", strtotime("-".$_GET['data-hour-range']." hours", strtotime($dateEnd)));
        }
    } else {
        if (isset($_GET['date'])) {
            $dateStart = $_GET['date'] . " 00:00";
            $dateEnd = $_GET['date'] . " 23:59";
        } else {
            $dateStart = date("Y-m-d") . " 00:00";
            $dateEnd = date("Y-m-d") . " 23:59";
        }
        if (isset($_GET['dateEnd'])) {
            $range = true;
            $dateEnd = $_GET['dateEnd']." 23:59";
        }
    }

    $cs = "report/".md5(time()).".xlsx";
    
    $xlsx = new SimpleXLSXGen();

    if($range){
        $dataClock = getReportClockTimeByDateRange($dateStart, $dateEnd);
    } else {
        $dataClock = getReportClockTimeByDate2($dateStart, $dateEnd);
    }
    $dataBreak = getReportBreakByDate2($dateStart, $dateEnd);
    $dataVisit = getReportVisitByDate2($dateStart, $dateEnd);
    $dataPING = getReportPINGByDate2($dateStart, $dateEnd);
    $dataBreadcrumbs = getReportBreadcrumbs($dateStart, $dateEnd);
    $cs = generateDailyReport($dataClock, $dataBreak, $dataVisit, $dataPING, $dataBreadcrumbs);
    $msg = "Hi Admin, This Daily Report ".date("d M Y", strtotime($dateStart));

    //prepareMessage(null, $msg, null, 'sendDocument', [5330895365], null, false, null, null, true, $cs);
    prepareMessage(null, $msg, null, 'sendDocument', $admin_id, null, false, null, null, true, $cs);
    unlink($cs);
    exit;
}