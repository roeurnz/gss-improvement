<?php
include "load.php";

$ezzeTeamsModel = new EzzeTeamsModel();

include_once __DIR__ ."/includes/language/en.php";

if (strtolower(strip_tags($_GET['command'])) == 'run-s-message') {
    $dayNow = date("D");
    $timeNow = date("H:i");
    
    $lists = $ezzeTeamsModel->getSMessageToSendNow($dayNow, $timeNow);
    foreach($lists as $list) {
        if ($list['media_type'] == 'text') {
            $mediaType = false;
            $media = false;
        } else {
            $mediaType = $list['media_type'];
            $media = $list['media'];
        }
        sendMessageToAllUser($list['message'], $mediaType, $media, $list['destination']);
    }
    $ezzeTeamsModel->markMessageLastRun($dayNow, $timeNow);
}