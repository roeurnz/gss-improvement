<?php

use Shuchkin\SimpleXLSXGen;

function getLangByKeyAndId($key, $l) {
    $langPath = __DIR__ . "/../language/";
    $lang = "";
    $filePath = $langPath.$l.'.php';

    if(file_exists($filePath)) {
        $read = file_get_contents($filePath);
        $pattern = "/'" . $key . "'(.*)=>(.*)'(.+?)'/";
        preg_match($pattern, $read, $matches);
        if (isset($matches[3])) {
            $lang = $matches[3];
        }
    }
    return $lang;
}

function collectAllLangValue($key) {
    global $langActive;

    $langPath = __DIR__ . "/../language/";
    $files = scandir($langPath);
    $lang = "";

    foreach($langActive as $la) {
        $fileName = strtolower($la).".php";
        if (file_exists($langPath.$fileName)) {
            $read = file_get_contents($langPath.$fileName);
            $pattern = "/'" . $key . "'(.*)=>(.*)'(.+?)'/";
            preg_match($pattern, $read, $matches);
            if (isset($matches[3])) {
                $lang .= $matches[3] . " / ";
            }
        }
    }
    return substr($lang, 0, -3);
}

function isCorrectReply($step, $pos, $button = false)
{
    global $userId, $botSettings, $ezzeTeamsModel;

    $replyBase = ACCEPTABLE_REPLY;

    $return = false;
    if (array_key_exists($step, $replyBase)) {
        if ($button !== false) {
            if (in_array($button, $replyBase[$step][$pos])) {
                return true;
            }
        } else {
            if ($replyBase[$step][$pos]) {
                return true;
            }
        }
    }

    if (!$return) {
        if ($pos == 'live_location') {
            $tasks = $ezzeTeamsModel->getDailyTaskThisTime($userId, $botSettings['dead_man_task_time']);
            $missedTask = $ezzeTeamsModel->getLastMissedDailyTask($userId);
            if (($tasks > 0 || $missedTask > 0) && $botSettings['dead_man_feature']) {
                return true;
            }
        }
    }
    return false;
}

function generateDailyReport($dataClock, $dataBreak, $dataVisit, $dataPING, $breadCrumb)
{
    $cs = "report/" . md5(time()) . ".xlsx";

    //Clock Data
    $xlsx = new SimpleXLSXGen();
    $xlsx->addSheet($dataClock['data'], 'TimeClock Summary');
    $xlsx->setDefaultFont('Calibri')
        ->setDefaultFontSize(14)
        ->setColWidth(1, 12)
        ->setColWidth(2, 6)
        ->setColWidth(3, 17)
        ->setColWidth(4, 17);
    if (count($dataClock['mergeCell']) > 0) {
        foreach ($dataClock['mergeCell'] as $merge) {
            $xlsx->mergeCells($merge);
        }
    }
    $xlsx->autoFilter('A2:G2');

    //Break Data
    $xlsx->addSheet($dataBreak['data'], 'Break Summary');
    $xlsx->setDefaultFont('Calibri')
        ->setDefaultFontSize(14)
        ->setColWidth(1, 12)
        ->setColWidth(2, 6)
        ->setColWidth(3, 17)
        ->setColWidth(4, 17);
    if (count($dataBreak['mergeCell']) > 0) {
        foreach ($dataBreak['mergeCell'] as $merge) {
            $xlsx->mergeCells($merge);
        }
    }
    $xlsx->autoFilter('A2:G2');

    //Visit Data
    $xlsx->addSheet($dataVisit['data'], 'Visit Summary');
    $xlsx->setDefaultFont('Calibri')
        ->setDefaultFontSize(14)
        ->setColWidth(1, 12)
        ->setColWidth(2, 6)
        ->setColWidth(3, 17)
        ->setColWidth(4, 17);
    if (count($dataVisit['mergeCell']) > 0) {
        foreach ($dataVisit['mergeCell'] as $merge) {
            $xlsx->mergeCells($merge);
        }
    }
    $xlsx->autoFilter('A2:G2');

    //PING Data
    $xlsx->addSheet($dataPING['data'], 'PING Alerts');
    $xlsx->setDefaultFont('Calibri')
        ->setDefaultFontSize(14)
        ->setColWidth(1, 12)
        ->setColWidth(2, 6)
        ->setColWidth(3, 17)
        ->setColWidth(4, 17);
    if (count($dataPING['mergeCell']) > 0) {
        foreach ($dataPING['mergeCell'] as $merge) {
            $xlsx->mergeCells($merge);
        }
    }
    $xlsx->autoFilter('A2:G2');

    //BreadCrumb Data
    $xlsx->addSheet($breadCrumb['data'], 'BreadCrumbs Summary');
    $xlsx->setDefaultFont('Calibri')
        ->setDefaultFontSize(14)
        ->setColWidth(1, 12)
        ->setColWidth(2, 6)
        ->setColWidth(3, 17)
        ->setColWidth(4, 17)
        ->setColWidth(5, 17)
        ->setColWidth(6, 17)
        ->setColWidth(7, 17);
    if (count($breadCrumb['mergeCell']) > 0) {
        foreach ($breadCrumb['mergeCell'] as $merge) {
            $xlsx->mergeCells($merge);
        }
    }
    $xlsx->autoFilter('A2:I2');

    //General Summary
    $dataGeneral = getGeneralSummaryReport($dataClock['data']);
    $xlsx->addSheet($dataGeneral['data'], 'General Summary');
    $xlsx->setDefaultFont('Calibri')
        ->setDefaultFontSize(14)
        ->setColWidth(1, 12)
        ->setColWidth(2, 6)
        ->setColWidth(3, 17)
        ->setColWidth(4, 17);
    if (count($dataGeneral['mergeCell']) > 0) {
        foreach ($dataGeneral['mergeCell'] as $merge) {
            $xlsx->mergeCells($merge);
        }
    }
    $xlsx->autoFilter('A2:G2');

    $xlsx->saveAs($cs);

    return $cs;
}

function generateEditMessageBtn($id)
{
    return [
        [
            "text" => "Edit Message",
            "callback_data" => "s-message-edit_" . $id
        ],
        [
            "text" => "Remove Message",
            "callback_data" => "s-message-remove_" . $id
        ],
    ];
}

function generateMessagePrevNextBtn($currentPage = 1, $pageNum)
{
    $nextPage = $currentPage + 1;
    if ($nextPage > $pageNum) $nextPage = $pageNum;

    $prevPage = $currentPage - 1;
    if ($prevPage < 1) $prevPage = 1;

    if ($currentPage == $pageNum) {
        return [
            [
                "text" => _l('button_previous'),
                "callback_data" => "messages-prev_$prevPage"
            ]
        ];
    } else if ($currentPage == 1) {
        return [
            [
                "text" => _l('button_next'),
                "callback_data" => "messages-next_$nextPage"
            ]
        ];
    } else {
        return [
            [
                "text" => _l('button_previous'),
                "callback_data" => "messages-prev_$prevPage"
            ],
            [
                "text" => _l('button_next'),
                "callback_data" => "messages-next_$nextPage"
            ]
        ];
    }
}

function getAllScheduledMessageLists($currentPage = 1, $messageId = false)
{
    global $ezzeTeamsModel, $userId;

    $limit = 5;
    $offset = ($currentPage * $limit) - $limit;
    $messageLists = $ezzeTeamsModel->getAllscheduledMessage($offset, $limit);

    $response = '';
    $i = $offset + 1;
    $response .= "<strong><u>List Scheduled Messages</u></strong>__";
    foreach ($messageLists['data'] as $message) {
        $response .= $i . ". " . $message['title'] . " /viewScheduleMessage" . $message['id'] . "_";
        $i++;
    }

    $totalPage = $messageLists['page'];
    $isInline = false;
    $btn = null;
    if ($totalPage > 1) {
        $btn = generateMessagePrevNextBtn($currentPage, $totalPage);
        $isInline = true;
    }
    if (!$messageId) {
        prepareMessage(null, $response, null, null, [$userId], null, $isInline, $btn);
    } else {
        //prepareMessage(null, json_encode($btn), null, null, [$userId]);
        prepareMessage(null, $response, null, 'editMessageText', array($userId), null, $isInline, [$btn], $messageId, true);
    }
    return true;
}

function generateMessageScheduleTime($day = 1, $hStart = 8, $mStart = 0)
{
    $main_arr =
        [
            [
                [
                    "text" => "↑",
                    "callback_data" => "next-h-schedule-message_" . $day . "_" . $hStart . "_" . $mStart
                ],
                [
                    "text" => "↑",
                    "callback_data" => "next-mn-schedule-message_" . $day . "_" . $hStart . "_" . $mStart
                ]
            ],
            [
                [
                    "text" => sprintf("%02d", $hStart),
                    "callback_data" => 'none'
                ],
                [
                    "text" => sprintf("%02d", $mStart),
                    "callback_data" => 'none'
                ]
            ],
            [
                [
                    "text" => "↓",
                    "callback_data" => "prev-h-schedule-message_" . $day . "_" . $hStart . "_" . $mStart
                ],
                [
                    "text" => "↓",
                    "callback_data" => "prev-mn-schedule-message_" . $day . "_" . $hStart . "_" . $mStart
                ]
            ],
            [
                [
                    "text" => "Skip",
                    "callback_data" => "schedule-message-skip_" . $day . "_" . $hStart . "_" . $mStart
                ]
            ],
            [
                [
                    "text" => "Confirm " . intToDay($day),
                    "callback_data" => "schedule-message-confirm_" . $day . "_" . $hStart . "_" . $mStart
                ]
            ]
        ];
    return $main_arr;
}

function generateRuntimeSchedultMessageButton()
{
    $data = '';
    return [
        [
            "text" => 'One Time',
            "callback_data" => "scheduleMessageRepeat_1"
        ],
        [
            "text" => 'Repeat',
            "callback_data" => "scheduleMessageRepeat_2"
        ]
    ];
}

function localizeFile($file_id)
{
    global $api_key;

    $params['file_id'] = $file_id;
    $file = sendMessage($params, 'getFile');
    $url = 'https://api.telegram.org/file/bot' . $api_key . '/' . $file->result->file_path;
    $img = __DIR__ . '/../../images/' . $file->result->file_path;
    $fl = fopen($img, 'w');
    fwrite($fl, file_get_contents($url));
    fclose($fl);
    return $img;
}

function sendMessageToAllUser($message, $media = false, $media_id = false, $destinations = 'all', $btn = false)
{
    global $userId, $admin_id, $ezzeTeamsModel;

    $limit = 20;
    if ($destinations == 'all') {
        $approvedUser = $ezzeTeamsModel->getUserIdAllApprovedUser();
        $userLists[0] = $admin_id;
        $i = count($admin_id);
        $n = 0;
        foreach ($approvedUser as $user) {
            if ($i % $limit == 0) {
                $n++;
            }
            $userLists[$n][] = $user['user_id'];
            $i++;
        }
    } else {
        $dest = explode(',', $destinations);
        $i = 0;
        $n = -1;
        foreach ($dest as $d) {
            if ($i % $limit == 0) {
                $n++;
            }
            $userLists[$n][] = $d;
            $i++;
        }
    }

    if (!$media) {
        foreach ($userLists as $list) {
            if (!$btn) {
                prepareMessage(null, $message, null, 'sendMessage', $list);
            } else {
                prepareMessage(null, $message, null, 'sendMessage', $list, null, true, $btn);
            }
            sleep(1);
        }
    } else if ($media == 'document') {
        $documentPath = localizeFile($media_id);
        foreach ($userLists as $list) {
            if (!$btn) {
                prepareMessage(null, $message, null, 'sendDocument', $list, null, false, null, null, false, $documentPath);
            } else {
                prepareMessage(null, $message, null, 'sendDocument', $list, null, true, $btn, null, false, $documentPath);
            }
            sleep(1);
        }
        unlink($documentPath);
    } else if ($media == 'photo') {
        foreach ($userLists as $list) {
            if (!$btn) {
                prepareMessage(null, $message, $media_id, 'sendPhoto', $list);
            } else {
                prepareMessage(null, $message, $media_id, 'sendPhoto', $list, null, true, $btn);
            }
            sleep(1);
        }
    } else if ($media == 'video') {
        foreach ($userLists as $list) {
            if (!$btn) {
                prepareMessage(null, $message, null, 'sendVideo', $list, null, false, null, null, false, null, $media_id);
            } else {
                prepareMessage(null, $message, null, 'sendVideo', $list, null, true, $btn, null, false, null, $media_id);
            }
            sleep(1);
        }
    }
    return true;
}

function getGeneralSummaryReport($clockData)
{
    unset($clockData[0]);
    unset($clockData[1]);
    //echo "<pre>";print_r($clockData);die;

    $data[] = [
        '<style color="#B73930">Generated at ' . date("d M Y H:i:s") . '</style>'
    ];
    $data[] = [
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Date</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Day</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>First Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Last Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Work Location</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 1</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 2</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>All OK</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Absent</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-IN Late</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-OUT Early</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-OUT Failed</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-IN Wrong Location</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-OUT Wrong Location</i></b></center></middle></style>'
    ];

    $pointCheckValue = '<center><b>X</b></center>';
    foreach ($clockData as $clock) {
        $arr = [];
        $arr['date'] = $clock[0];
        $arr['day'] = $clock[1];
        $arr['firstName'] = $clock[2];
        $arr['lastName'] = $clock[3];
        $arr['workLocation'] = $clock[4];
        $arr['sort1'] = $clock[5];
        $arr['sort2'] = $clock[6];

        $isAbsent = strtolower(trim(strip_tags($clock[7])));
        if ($isAbsent != 'absent') {
            //10: clock IN Time Status
            //15: clock IN Location Status
            //21: clock OUT Time Status
            //26: clock OUT Location Status
            if ($clock[10] == 'OK' && $clock[15] == 'OK' && $clock[21] == 'OK' && $clock[26] == 'OK') {
                $arr['allOK'] = $pointCheckValue;
                $arr['absent'] = '';
                $arr['clockINLate'] = '';
                $arr['clockOUTEarly'] = '';
                $arr['clockOUTFailed'] = '';
                $arr['clockINWrongLocation'] = '';
                $arr['clockOUTWrongLocation'] = '';
            } else {
                $arr['allOK'] = '';
                $arr['absent'] = '';

                $arr['clockINLate'] = ($clock[10] == 'LATE' && $clock[10] != '') ? $pointCheckValue : '';
                $arr['clockOUTEarly'] = (isset($clock[21]) && $clock[21] == 'EARLY CLOCK OUT' && $clock[21] != '') ? $pointCheckValue : '';
                $arr['clockOUTFailed'] = (isset($clock[21]) && $clock[21] == 'FAILED CLOCK OUT' && $clock[21] != '') ? $pointCheckValue : '';
                $arr['clockINWrongLocation'] = (isset($clock[15]) && $clock[15] == 'WRONG LOCATION' && $clock[15] != '') ? $pointCheckValue : '';
                $arr['clockOUTWrongLocation'] = (isset($clock[26]) && $clock[26] == 'WRONG LOCATION' && $clock[26] != '') ? $pointCheckValue : '';;
            }
        } else {
            $arr['allOK'] = '';
            $arr['absent'] = $pointCheckValue;
            $arr['clockINLate'] = '';
            $arr['clockOUTEarly'] = '';
            $arr['clockOUTFailed'] = '';
            $arr['clockINWrongLocation'] = '';
            $arr['clockOUTWrongLocation'] = '';
        }
        $data[] = array_values($arr);
    }

    $mergeCell = ['A1:M1'];

    return ['data' => $data, 'mergeCell' => $mergeCell];
}

function getReportBreadcrumbs($dateStart, $dateEnd){
    global $ezzeTeamsModel, $botSettings;

    $data[] = [
        '<style color="#B73930">Generated at ' . date("d M Y H:i:s") . '</style>'
    ];
    $data[] = [
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Date</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Day</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>First Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Last Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Work Location</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Crumb</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Latitude</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Longitude</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>GPS</i></b></center></middle></style>'
    ];

    $breadCrumbs = $ezzeTeamsModel->getBreadCrumbDataByDate($dateStart, $dateEnd);
    $userData = [];
    foreach($breadCrumbs as $arr) {
        $m = [];
        if ($arr['lat'] != '' && $arr['lon'] != '') {
            /*if ($arr['crumbs'] == 'clock_in') {
                $userData[$arr['user_id']]['clockin'][] = $arr;
            } else if ($arr['crumbs'] == 'clock_out') {
                $userData[$arr['user_id']] = array_merge_recursive($userData[$arr['user_id']]['clockin'], $userData[$arr['user_id']]['lists']);
                unset($userData[$arr['user_id']]['clockin']);
                unset($userData[$arr['user_id']]['lists']);
                $userData[$arr['user_id']][] = $arr;
            } else {
                $userData[$arr['user_id']]['lists'][] = $arr;
            }*/
            $userData[$arr['user_id']][] = $arr;
        }
    }
    //echo "<pre>";print_r($userData);echo "</pre>";die;

    $mergeCell = ['A1:I1'];
    $split = 10;
    $k = 3;
    $last_user = "";
    foreach($userData as $user_id => $userCrumbs) {
        $i = 0;
        $gmap = "https://www.google.com/maps/dir/";
        foreach($userCrumbs as $crumbs) {
            $last = "";
            $m = [];
            $crumbsDate = new DateTime($crumbs['created']);
            $m[] = $crumbsDate->format("d M Y H:i");
            $m[] = $k." | ".$crumbsDate->format("D");
            $m[] = $crumbs['firstname'];
            $m[] = $crumbs['lastname'];
            $m[] = $crumbs['branch_name'];
            $m[] = $crumbs['crumbs'];
            $m[] = $crumbs['lat'];
            $m[] = $crumbs['lon'];
            $m[] = $crumbs['lat'] . ", " . $crumbs['lon'];
            $gmap .= $crumbs['lat'] . ",+" . $crumbs['lon']."/";
            $data[] = $m;
            $i++;
            $k++;
            if ( $i % $split == 0 ) {
                $mergeCell[] = 'A'.$k.':F'.$k;
                $mergeCell[] = 'G'.$k.':I'.$k;
                $m = [];
                $m[] = "<center><b>TOTAL " . strtoupper($crumbs['firstname']) . " " . strtoupper($crumbs['lastname'])."</b></center>";
                $m[] = "";
                $m[] = "";
                $m[] = "";
                $m[] = "";
                $m[] = "";
                $m[] = $gmap;
                $data[] = $m;
                $gmap = "https://www.google.com/maps/dir/";
                $last = "TOTAL";
                $k++;
            }
        }

        if ($last != "TOTAL") {
            $mergeCell[] = 'A' . $k . ':F' . $k;
            $mergeCell[] = 'G' . $k . ':I' . $k;
            $m = [];
            $m[] = "<center><b>TOTAL " . strtoupper($crumbs['firstname']) . " " . strtoupper($crumbs['lastname'])."</b></center>";
            $m[] = "";
            $m[] = "";
            $m[] = "";
            $m[] = "";
            $m[] = "";
            $m[] = $gmap;
            $data[] = $m;
            $k++;
        }

        $last_user = $user_id;
    }

    return ['data' => $data, 'mergeCell' => $mergeCell];
}

function getReportPINGByDate2($dateStart, $dateEnd)
{
    global $ezzeTeamsModel, $botSettings;

    $data[] = [
        '<style color="#B73930">Generated at ' . date("d M Y H:i:s") . '</style>'
    ];
    $data[] = [
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Date</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Day</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>First Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Last Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Work Location</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 1</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 2</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>PING Start</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>PING END</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Reply Time</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Reply Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Reply GPS</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Reply Location Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Distance (meters)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Process TX (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Location TX (Y/N) Error</i></b></center></middle></style>'
    ];
    $pingData = $ezzeTeamsModel->getAllPINGDataByDate($dateStart, $dateEnd);

    $arr = [];
    foreach ($pingData as $ping) {
        $m = [];
        $ampm = date('A', strtotime($ping['task_start']));
        $pingDate = new DateTime($ping['created_at']);
        $alert = 'N';
        if ($botSettings['module_alert']) {
            if ($ping['task_send'] == 1) {
                if ($ping['task_reply'] == 2) {
                    $alert = 'Y';
                }
            }
        }

        $m[] = $pingDate->format("d M Y");
        $m[] = $pingDate->format("D");
        $m[] = $ping['firstname'];
        $m[] = $ping['lastname'];
        $m[] = $ping['branch_name'];
        $m[] = 1;
        $m[] = $ampm;
        $m[] = date("H:i", strtotime($ping['task_start']));
        $m[] = date("H:i", strtotime($ping['task_end']));
        $m[] = date("H:i", strtotime($ping['reply_time']));
        $m[] = ($ping['task_status'] == 'OK') ? 'OK' : 'FAILED';
        $m[] = $ping['reply_location'];
        $m[] = $ping['reply_location_status'];
        $m[] = ($ping['reply_location_distance'] != '') ? ($ping['reply_location_distance'] * 1000) : '';
        $m[] = '<center>' . $alert . '</center>';
        $m[] = '<center>CS</center>';
        $m[] = '<center>CS</center>';
        $data[] = $m;
    }

    $mergeCell = ['A1:Q1'];

    return ['data' => $data, 'mergeCell' => $mergeCell];
}

function getReportVisitByDate2($dateStart, $dateEnd)
{
    global $ezzeTeamsModel;

    $data[] = [
        '<style color="#B73930">Generated at ' . date("d M Y H:i:s") . '</style>'
    ];
    $data[] = [
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Date</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Day</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>First Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Last Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Work Location</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 1</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 2</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Start Visit</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>End Visit</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Visit Total</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Start Visit GPS</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>End Visit GPS</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Start Notes</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>End Notes</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Process TX (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Location TX (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Selfie TX (Y/N)</i></b></center></middle></style>'
    ];

    $visitData = $ezzeTeamsModel->getAllVisitDataByDate($dateStart, $dateEnd);

    $arr = [];
    foreach ($visitData as $key => $visit) {
        if (!isset($visitUserPos[$visit['user_id']])) $visitUserPos[$visit['user_id']] = 0;
        if (!isset($visitUserTotal[$visit['user_id']]['total'])) $visitUserTotal[$visit['user_id']]['total'] = '00:00';
        $dateData = date("d M Y", strtotime($visit['created_at']));
        $dateStdFormat = date("Y-m-d", strtotime($visit['created_at']));
        $ampm = date('A', strtotime($dateData));

        $gpsCoordinate = (($visit['visit_lat'] != '' && $visit['visit_lon'] != '')) ? $visit['visit_lat'] . ", " . $visit['visit_lon'] : '';

        if ($visit['visit_action'] == 'start_visit') {
            $visitUserTotal[$visit['user_id']]['name'] = $visit['firstname'] . " " . $visit['lastname'];
            $visitUserTotal[$visit['user_id']]['start_visit'] = $visit['visit_time'];

            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]][] = $dateData;
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]][] = $visit['visit_day'];
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]][] = $visit['user_id'];
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]][] = $visit['firstname'];
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]][] = $visit['lastname'];
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]][] = $visit['branch_name'];
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]][] = 1;
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]][] = $ampm;
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['start_visit'] = date('H:i', strtotime($visit['visit_time']));
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['end_visit'] = '';
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['visit_total'] = '';
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['start_location'] = $gpsCoordinate;
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['end_location'] = '';
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['start_notes'] = $visit['visit_notes'];
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['end_notes'] = '';
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['alert_sent'] = 'CS';
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['process_tx'] = 'CS';
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['location_tx'] = 'CS';
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['selfie_tx'] = 'CS';
        } elseif ($visit['visit_action'] == 'end_visit') {
            //$startVisit = $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['start_visit'];
            $startVisit = '';
            if (isset($visitUserTotal[$visit['user_id']]['start_visit'])) {
                $startVisit = date("Y-m-d H:i", strtotime($visitUserTotal[$visit['user_id']]['start_visit']));
            }
            $endVisit = date("H:i", strtotime($visit['visit_time']));
            $visitDuration = '';
            if ($startVisit != '' && $endVisit != '') {
                $time1 = new DateTime($startVisit);
                $time2 = new DateTime(date("Y-m-d H:i", strtotime($visit['visit_time'])));
                $interval = $time1->diff($time2);
                $visitDuration = $interval->format('%H:%I');
                $visitUserTotal[$visit['user_id']]['total'] = sum_the_time($visitUserTotal[$visit['user_id']]['total'], $visitDuration);
            }

            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['end_visit'] = $endVisit;
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['visit_total'] = $visitDuration;
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['end_location'] = $gpsCoordinate;
            $arr[$visit['user_id']][$visitUserPos[$visit['user_id']]]['end_notes'] = $visit['visit_notes'];
            $visitUserPos[$visit['user_id']] = $visitUserPos[$visit['user_id']] + 1;
        }
    }

    $mergeCell = ['A1:R1'];
    $row = 3;
    foreach ($arr as $userId => $dt) {
        $i = 0;
        foreach ($dt as $m) {
            unset($m[2]);
            $m = array_values($m);
            $data[] = $m;
            $row++;
            $i++;
        }

        if ($i > 0) {
            $userTotalVisit = [];
            $userTotalVisit[] = "<center><b>TOTAL " . strtoupper($visitUserTotal[$userId]['name']) . " VISIT</b></center>";
            $userTotalVisit[] = "";
            $userTotalVisit[] = "";
            $userTotalVisit[] = "";
            $userTotalVisit[] = "";
            $userTotalVisit[] = "";
            $userTotalVisit[] = "";
            $userTotalVisit[] = "";
            $userTotalVisit[] = "";
            $userTotalVisit[] = $visitUserTotal[$userId]['total'];
            $data[] = $userTotalVisit;
            $data[] = ['', ''];
            $mergeCell[] = "A" . $row . ":I" . $row;
            $row = $row + 2;
        }
    }

    return ['data' => $data, 'mergeCell' => $mergeCell];
}

function getReportBreakByDate2($dateStart, $dateEnd)
{
    global $ezzeTeamsModel;

    $data[] = [
        '<style color="#B73930">Generated at ' . date("d M Y H:i:s") . '</style>'
    ];
    $data[] = [
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Date</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Day</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>First Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Last Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Work Location</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 1</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 2</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Start Break</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>End Break</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Break Total</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Start Break Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Start Break GPS</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Distance (m)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Process TX (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Location TX (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Selfie TX (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>BreakEND Location Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>BreakEND GPS Actual</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Distance (m)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert SENT (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Process TX (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Location TX (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Selfie TX (Y/N) Error</i></b></center></middle></style>'
    ];

    $breakData = $ezzeTeamsModel->getAllBreakDataByDate($dateStart, $dateEnd);

    $arr = [];
    foreach ($breakData as $break) {
        if (!isset($break_userpos[$break['user_id']])) $break_userpos[$break['user_id']] = 0;
        if (!isset($breakUserTotal[$break['user_id']]['total'])) $breakUserTotal[$break['user_id']]['total'] = '00:00';
        $dateData = date("d M Y", strtotime($break['created_at']));
        $dateStdFormat = date("Y-m-d", strtotime($break['created_at']));
        $ampm = date('A', strtotime($dateData));
        $breakTimeStd = $break['break_time'];
        $gpsCoordinate = (($break['location_lat'] != '' && $break['location_lon'] != '')) ? $break['location_lat'] . ", " . $break['location_lon'] : '';
        $gpsDistance = ($break['location_distance'] > 0) ? $break['location_distance'] * 1000 : 0;

        if ($break['break_action'] == 'start_break') {
            $breakUserTotal[$break['user_id']]['name'] = $break['firstname'] . " " . $break['lastname'];

            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $dateData;
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $break['break_day'];
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $break['user_id'];
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $break['firstname'];
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $break['lastname'];
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $break['branch_name'];
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = 1;
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $ampm;
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['start_break'] = date("H:i", strtotime($breakTimeStd));
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['end_break'] = '';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['break_duration'] = '';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $break['location_status'];
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $gpsCoordinate;
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $gpsDistance;
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['alert_sent_bstart'] = 'Y';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['alert_actioned_bstart'] = 'CS';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['process_tx_bstart'] = 'CS';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['location_tx_bstart'] = 'CS';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['selfie_tx_bstart'] = 'CS';
        } elseif ($break['break_action'] == 'end_break') {
            $startBreak = $arr[$break['user_id']][$break_userpos[$break['user_id']]]['start_break'];
            $endBreak = date("H:i", strtotime($breakTimeStd));
            $breakDuration = '';
            if ($startBreak != '' && $breakTimeStd != '') {
                $time1 = new DateTime($dateStdFormat . " " . $startBreak);
                $time2 = new DateTime($dateStdFormat . " " . $endBreak);
                $interval = $time1->diff($time2);
                $breakDuration = $interval->format('%H:%I');
                $breakUserTotal[$break['user_id']]['total'] = sum_the_time($breakUserTotal[$break['user_id']]['total'] . ":00", $breakDuration . ":00");
            }

            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['end_break'] = $endBreak;
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['break_duration'] = $breakDuration;
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $break['location_status'];
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $gpsCoordinate;
            $arr[$break['user_id']][$break_userpos[$break['user_id']]][] = $gpsDistance;
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['alert_sent_bend'] = 'Y';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['alert_actioned_bend'] = 'CS';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['process_tx_bend'] = 'CS';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['location_tx_bend'] = 'CS';
            $arr[$break['user_id']][$break_userpos[$break['user_id']]]['selfie_tx_bend'] = 'CS';
            $break_userpos[$break['user_id']] = $break_userpos[$break['user_id']] + 1;
        }
    }

    $mergeCell = ['A1:Z1'];
    $row = 3;
    foreach ($arr as $userId => $dt) {
        $i = 0;
        foreach ($dt as $m) {
            unset($m[2]);
            $m = array_values($m);
            $data[] = $m;
            $row++;
            $i++;
        }

        if ($i > 0) {
            $userTotalBreak = [];
            $userTotalBreak[] = "<center><b>TOTAL " . strtoupper($breakUserTotal[$userId]['name']) . " BREAKS</b></center>";
            $userTotalBreak[] = "";
            $userTotalBreak[] = "";
            $userTotalBreak[] = "";
            $userTotalBreak[] = "";
            $userTotalBreak[] = "";
            $userTotalBreak[] = "";
            $userTotalBreak[] = "";
            $userTotalBreak[] = "";
            $userTotalBreak[] = $breakUserTotal[$userId]['total'];
            $data[] = $userTotalBreak;
            $data[] = ['', ''];
            $mergeCell[] = "A" . $row . ":I" . $row;
            $row = $row + 2;
        }
    }

    return ['data' => $data, 'mergeCell' => $mergeCell];
}

function getReportClockTimeByDate2($dateStart, $dateEnd)
{
    global $ezzeTeamsModel;

    $data[] = [
        '<style color="#B73930">Generated at ' . date("d M Y H:i:s") . '</style>'
    ];
    $data[] = [
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Date</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Day</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>First Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Last Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Work Location</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 1</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 2</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Break Count</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Visit Count</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Scheduled Clock-IN</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-IN Time Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Actual Clock-IN</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Time Difference</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-IN Location Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-IN GPS Actual</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Distance (m)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Scheduled Clock-OUT</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-OUT Time Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Actual Clock-OUT</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Time Difference</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-OUT Location Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-OUT GPS Actual</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Distance (m)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Process TX (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Location (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Selfie (Y/N) Error</i></b></center></middle></style>'
    ];

    $clockData = $ezzeTeamsModel->getAllClockDataByUserListsAndDateTime($dateStart, $dateEnd);

    $arr = [];
    foreach ($clockData as $clock) {
        $date = $clock['created_at'];
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        $dateData = $date->format("d M Y");
        $dateStdFormat = $date->format('Y-m-d');
        $dateStdFormatStart = $dateStdFormat . " 00:00";
        $dateStdFormatEnd = $dateStdFormat . " 23:59";
        $ampm = $date->format('a');

        if ($clock['is_clock_in'] == 'absent') {
            $arr[$clock['user_id']] = [$dateData, $clock['clock_in_day'], $clock['user_id'], $clock['firstname'],
                $clock['lastname'], $clock['branch_name'], 1, strtoupper($ampm), 'ABSENT'];
        } else {
            $alertClock = ($clock['clock_in_time'] == 'OK') ? 'N' : 'Y';
            $alertLocation = ($clock['clock_in_location_status'] == 'OK') ? 'N' : 'Y';

            $time1 = new DateTime($dateStdFormat . " " . $clock['clock_in_time']);
            $time2 = new DateTime($dateStdFormat . " " . $clock['work_start_time']);
            $interval = $time1->diff($time2);
            $clockInDiffTime = $interval->format('%H:%I');

            $gps = $clock['clock_in_lat'] . ", " . $clock['clock_in_lon'];
            $gpsDistance = $clock['clock_in_distance'] * 1000;

            if ($clock['is_clock_in'] == 'clock_in') {
                $break = $ezzeTeamsModel->calculateUserBreakByDay($clock['user_id'], $dateStdFormatStart);
                $visit = $ezzeTeamsModel->calculateUserVisitByDay($clock['user_id'], $dateStdFormatStart);

                $arr[$clock['user_id']] = [
                    $dateData, $clock['clock_in_day'], $clock['user_id'], $clock['firstname'], $clock['lastname'],
                    $clock['branch_name'],
                    '1', strtoupper($ampm), $break['total_break'], $visit['total_visit'], $clock['work_start_time'],
                    $clock['clock_in_time_status'], $clock['clock_in_time'], $alertClock, $clockInDiffTime, 'CS',
                    $clock['clock_in_location_status'], $gps, $alertLocation, $gpsDistance, 'CS'];
            } else {
                if (array_key_exists($clock['user_id'], $arr)) {
                    $arr[$clock['user_id']][] = $clock['work_start_time'];
                    $arr[$clock['user_id']][] = $clock['clock_in_time_status'];
                    $arr[$clock['user_id']][] = $clock['clock_in_time'];
                    $arr[$clock['user_id']][] = $alertClock;
                    $arr[$clock['user_id']][] = $clockInDiffTime;
                    $arr[$clock['user_id']][] = 'CS';
                    $arr[$clock['user_id']][] = $clock['clock_in_location_status'];
                    $arr[$clock['user_id']][] = $gps;
                    $arr[$clock['user_id']][] = $alertLocation;
                    $arr[$clock['user_id']][] = $gpsDistance;
                    $arr[$clock['user_id']][] = 'CS';
                    $arr[$clock['user_id']][] = 'CS';
                    $arr[$clock['user_id']][] = 'CS';
                    $arr[$clock['user_id']][] = 'CS';
                }
            }
        }
    }

    $i = 3;
    $mergeCell = ['A1:AH1'];
    foreach ($arr as $r) {
        if (count($r) < 25) {
            if (is_string($r[8]) && $r[8] == 'ABSENT') {
                $r[8] = '<center><b>ABSENT</b></center>';
                $mergeCell[] = 'H' . $i . ":AH" . $i;
            } else {
                $userDetails = $ezzeTeamsModel->getDetilUserById($r[2]);
                if ($userDetails['step'] != 'clock_out_done') {
                    $r[] = "<center><b>Working</b></center>";
                    $mergeCell[] = 'U' . $i . ":AH" . $i;
                }
            }
        }
        unset($r[2]);
        $r = array_values($r);
        $data[] = $r;
        $i++;
    }
    return ['data' => $data, 'mergeCell' => $mergeCell];
}

function getReportClockTimeByDateRange($dateStart, $dateEnd)
{
    global $ezzeTeamsModel;

    $data[] = [
        '<style color="#B73930">Generated at ' . date("d M Y H:i:s") . '</style>'
    ];
    $data[] = [
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Date</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Day</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>First Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Last Name</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Work Location</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 1</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Sort 2</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Break Count</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Visit Count</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Scheduled Clock-IN</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-IN Time Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Actual Clock-IN</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Time Difference</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-IN Location Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-IN GPS Actual</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Distance (m)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Scheduled Clock-OUT</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-OUT Time Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Actual Clock-OUT</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Time Difference</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-OUT Location Status</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Clock-OUT GPS Actual</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Sent (Y/N)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Distance (m)</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Alert Actioned</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Process TX (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Location (Y/N) Error</i></b></center></middle></style>',
        '<style height="36" bgcolor="#D8D8D8"><middle><center><b><i>Selfie (Y/N) Error</i></b></center></middle></style>'
    ];

    $clockData = $ezzeTeamsModel->getAllClockDataByUserListsAndDateTime($dateStart, $dateEnd);

    $arr = [];
    $datas = [];
    foreach ($clockData as $clock) {
        $dt = [];
        $date = $clock['created_at'];
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        $dateData = $date->format("d M Y");
        $dateStdFormat = $date->format('Y-m-d');
        $dateM = $date->format("Ymd");
        $dateStdFormatStart = $dateStdFormat . " 00:00";
        $dateStdFormatEnd = $dateStdFormat . " 23:59";
        $ampm = $date->format('a');

        if ($clock['is_clock_in'] == 'absent') {
            $dt = [$dateData, $clock['clock_in_day'], $clock['user_id'], $clock['firstname'],
                $clock['lastname'], $clock['branch_name'], 1, strtoupper($ampm), 'ABSENT'];
            $arr[$dateM][$clock['user_id']] = $dt;
        } else {
            $alertClock = ($clock['clock_in_time'] == 'OK') ? 'N' : 'Y';
            $alertLocation = ($clock['clock_in_location_status'] == 'OK') ? 'N' : 'Y';

            $time1 = new DateTime($dateStdFormat . " " . $clock['clock_in_time']);
            $time2 = new DateTime($dateStdFormat . " " . $clock['work_start_time']);
            $interval = $time1->diff($time2);
            $clockInDiffTime = $interval->format('%H:%I');

            $gps = $clock['clock_in_lat'] . ", " . $clock['clock_in_lon'];
            $gpsDistance = $clock['clock_in_distance'] * 1000;

            if ($clock['is_clock_in'] == 'clock_in') {
                $break = $ezzeTeamsModel->calculateUserBreakByDay($clock['user_id'], $dateStdFormatStart);
                $visit = $ezzeTeamsModel->calculateUserVisitByDay($clock['user_id'], $dateStdFormatStart);

                $dt = [
                    $dateData, $clock['clock_in_day'], $clock['user_id'], $clock['firstname'], $clock['lastname'],
                    $clock['branch_name'],
                    '1', strtoupper($ampm), $break['total_break'], $visit['total_visit'], $clock['work_start_time'],
                    $clock['clock_in_time_status'], $clock['clock_in_time'], $alertClock, $clockInDiffTime, 'CS',
                    $clock['clock_in_location_status'], $gps, $alertLocation, $gpsDistance, 'CS'];
                $arr[$dateM][$clock['user_id']] = $dt;
            } else {
                if (array_key_exists($dateM, $arr)) {
                    if (array_key_exists($clock['user_id'], $arr[$dateM])) {
                        $arr[$dateM][$clock['user_id']][] = $clock['work_start_time'];
                        $arr[$dateM][$clock['user_id']][] = $clock['clock_in_time_status'];
                        $arr[$dateM][$clock['user_id']][] = $clock['clock_in_time'];
                        $arr[$dateM][$clock['user_id']][] = $alertClock;
                        $arr[$dateM][$clock['user_id']][] = $clockInDiffTime;
                        $arr[$dateM][$clock['user_id']][] = 'CS';
                        $arr[$dateM][$clock['user_id']][] = $clock['clock_in_location_status'];
                        $arr[$dateM][$clock['user_id']][] = $gps;
                        $arr[$dateM][$clock['user_id']][] = $alertLocation;
                        $arr[$dateM][$clock['user_id']][] = $gpsDistance;
                        $arr[$dateM][$clock['user_id']][] = 'CS';
                        $arr[$dateM][$clock['user_id']][] = 'CS';
                        $arr[$dateM][$clock['user_id']][] = 'CS';
                        $arr[$dateM][$clock['user_id']][] = 'CS';
                    }
                }
            }
        }
    }

    $i = 3;
    $mergeCell = ['A1:AH1'];
    foreach ($arr as $ary) {
        if (count($ary) > 0) {
            foreach ($ary as $r) {
                if (count($r) < 20) {
                    if ($r[8] != 'ABSENT') {
                        $userDetails = $ezzeTeamsModel->getDetilUserById($r[2]);
                        if ($userDetails['step'] != 'clock_out_done') {
                            $r[] = "<center><b>On Work</b></center>";
                            $mergeCell[] = 'U' . $i . ":AH" . $i;
                        }
                    } elseif ($r[8] == 'ABSENT') {
                        $r[8] = '<center><b>ABSENT</b></center>';
                        $mergeCell[] = 'H' . $i . ":AH" . $i;
                    }
                }
                unset($r[2]);
                $r = array_values($r);
                $data[] = $r;
                $i++;
            }
        }
    }

    return ['data' => $data, 'mergeCell' => $mergeCell];
}

function forceClockOut($data)
{
    global $ezzeTeamsModel;

    //set clock out data
    $m = $ezzeTeamsModel->forceClockOut($data);
    //echo "force clock out = "; print_r($m); echo "<br>";

    //set end break
    $lastBreak = $ezzeTeamsModel->getLastRecordBreakUseId($data['user_id']);
    if (isset($lastBreak['break_action']) && $lastBreak['break_action'] == 'start_break') {
        $params = [];
        $params['user_id'] = $data['user_id'];
        $params['day'] = $data['clock_in_day'];
        $params['break_time'] = date("H:i:s");
        $params['location_status'] = "";
        $params['location_lat'] = "";
        $params['location_lon'] = "";
        $params['location_msg_id'] = "";
        $params['location_distance'] = "";
        $params['selfie_msg_id'] = "";
        $params['action'] = "end_break";
        $m = $ezzeTeamsModel->userBreak($params);
        //echo "force break = "; print_r($m); echo "<br>";
    }

    //set end visit
    $lastVisit = $ezzeTeamsModel->getLastRecordVisit($data['user_id']);
    if (isset($lastVisit['visit_action']) && $lastVisit['visit_action'] == 'start_visit') {
        $params = [];
        $params['user_id'] = $data['user_id'];
        $params['visit_day'] = date('D');
        $params['visit_time'] = date("Y-m-d H:i:s");
        $params['visit_lat'] = "";
        $params['visit_lon'] = "";
        $params['visit_location_msg_id'] = "";
        $params['selfie_msg_id'] = "";
        $params['visit_notes'] = 'Force clocked out Cause employee not clocked out';
        $params['action'] = 'end_visit';
        $m = $ezzeTeamsModel->userVisit($params);
        //echo "force visit = "; print_r($m); echo "<br>";
    }

    $m = $ezzeTeamsModel->markDeadManRemainOnClockOut($data['user_id']);

    $m = $ezzeTeamsModel->setForceClockOutReminder($data['user_id'], $data['clock_in_time_status']);

    $ezzeTeamsModel->updateUserWhere($data['user_id'], "step = 'clock_out_done'");
    $v = (int)1;
    $ezzeTeamsModel->changeCompleteUserStep($data['user_id'], $v);
    return true;
}

function isLastFiveReminderNotReplyByUser($user_id, $dateNow)
{
    global $ezzeTeamsModel, $botSettings;

    $maxNotReply = $botSettings['max_not_reply_reminder'];
    $lists = $ezzeTeamsModel->getLastFiveReminderNotReply($user_id, $dateNow);

    if (count($lists) < $maxNotReply) {
        return true;
    } else {
        $abort = [];

        $sort = $lists[0]['reminder_num'];
        //array_shift($lists);

        foreach ($lists as $list) {
            if ($list['reply'] == 0) {
                if ($sort == $list['reminder_num']) {
                    $abort[] = $list['id'];
                }
            }
            $sort--;
        }

        if (count($abort) >= $maxNotReply) {
            return false;
        } else {
            return true;
        }
    }
}

function createReminder($user_id, $type, $maxReminder, $dateStart, $msg, $btn)
{
    global $botSettings, $ezzeTeamsModel;

    $intervalInSec = $botSettings['clockout_reminder_interval'] * 60;
    for ($i = 0; $i < $maxReminder; $i++) {
        if ($i < $maxReminder) {
            $btn = $btn;
        } else {
            $btn = generateButtonClockOutNow();
        }
        if ($i == 0) {
            $startReminder = $dateStart + ($botSettings['time_tolerance'] * 60);
        } else {
            $startReminder = $dateStart + ($i * $intervalInSec);
        }
        $startReminderInDate = date("Y-m-d H:i", $startReminder) . ':00';
        if ($i == 0) {
            $endReminder = $dateStart + ($botSettings['clockout_reminder_timeout'] * 60) + ($botSettings['time_tolerance'] * 60);
        } else {
            $endReminder = $startReminder + ($botSettings['clockout_reminder_timeout'] * 60);
        }
        $endReminderInDate = date("Y-m-d H:i", $endReminder) . ':00';
        $data['user_id'] = $user_id;
        $data['type'] = $type;
        $data['start_time'] = $startReminderInDate;
        $data['end_time'] = $endReminderInDate;
        $data['reminder_msg'] = $msg;
        $data['reminder_button'] = json_encode($btn, JSON_UNESCAPED_UNICODE);
        $data['reminder_num'] = $i + 1;
        $ezzeTeamsModel->createRemider($data);
    }
    return true;
}

function generateButtonClockOutNow()
{
    return [
        [
            _l('clock_out_now')
        ]
    ];
}

function generateButtonReminder()
{
    return [
        [
            _l('button_yes')
        ],
        [
            _l('button_remind_later')
        ]
    ];
}

function convertMinToReadable($minutes)
{
    if ($minutes > 59) {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        $val = $h . "h " . $m . "min";
    } else {
        $val = $minutes . "min";
    }
    return $val;
}

function parseText($str)
{
    $char = array('\'', '"', ',', ';', '<', '>', '(', ')', '_', '@', '!', '#', '$', '%', '^', '&', '*');
    return str_replace($char, '', $str);
}

function getReportVisitByDate($branchId, $branchName, $dateStart, $dateEnd)
{
    global $ezzeTeamsModel;

    $users = $ezzeTeamsModel->getAllApprovedUsersByBranch($branchId);

    $cs = "report/" . md5(time()) . ".csv";
    $handle = fopen($cs, "w");
    fputcsv($handle, ['Company Name', COMPANY_NAME]);
    fputcsv($handle, ['Branch Name', ucwords($branchName)]);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['Date Start', $dateStart]);
    fputcsv($handle, ['Date End', $dateEnd]);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['Name', 'Day', 'Date', 'Start Visit', 'End Visit', 'Visit Total', 'Start Visit GPS', 'Start Notes', 'End Visit GPS', 'End Notes']);

    foreach ($users as $user) {
        $listsData = [];
        $user_id = $user['user_id'];
        $userIdLists[] = $user_id;
        $userData[$user_id] = $user;

        $begin = new DateTime($dateStart);
        $end = new DateTime($dateEnd);

        $visitTimeTotal = '00:00';
        $workTimeWithVisitTotal = '00:00';
        $workTimeTotal = '00:00';
        $totalData = 0;
        $totalDate = 0;

        for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
            $data = [];
            $params = [];
            $dt = $i->format("Y-m-d");
            $visitData = $ezzeTeamsModel->getAllVisitDataByUserListsAndDate($user_id, $dt);

            foreach ($visitData as $visit) {
                if ($visit['visit_action'] == 'start_visit') {
                    $startVisitTime = $visit['visit_time'];
                    $startVisitgps = $visit['visit_lat'] . ", " . $visit['visit_lon'];
                    $startVisitNote = $visit['visit_notes'];
                } else {
                    $data = [];

                    $startVisit = $startVisitTime;
                    $endVisit = $visit['visit_time'];

                    $time1 = new DateTime($startVisit);
                    $time2 = new DateTime($endVisit);
                    $diff = $time1->diff($time2)->format('%H:%I');

                    $visitTimeTotal = sum_the_time($visitTimeTotal, $diff);

                    $data[] = $user['firstname'] . " " . $user['lastname'];
                    $data[] = $i->format('D');
                    $data[] = $dt;
                    $data[] = date("H:i", strtotime($startVisitTime));
                    $data[] = date("H:i", strtotime($visit['visit_time']));
                    $data[] = $diff;
                    $data[] = $startVisitgps;
                    $data[] = $startVisitNote;
                    $data[] = $visit['visit_lat'] . ", " . $visit['visit_lon'];
                    $data[] = $visit['visit_notes'];
                    fputcsv($handle, $data);

                    $startVisitTime = "";
                    $startVisitgps = "";
                    $startVisitDist = "";
                    $startVisitLocSt = "";
                }
                $totalData++;
                $totalDate++;
            }
        }

        if ($totalData > 0) {
            fputcsv($handle, ['Total ' . $user['firstname'], '', '', '', '', $visitTimeTotal]);
        }
        if ($totalDate > 0) {
            fputcsv($handle, ['', '']);
            fputcsv($handle, ['', '']);
            $totalDate = 0;
        }
    }
    fclose($handle);
    return $cs;
}

function getReportBreakByDate($branchId, $branchName, $dateStart, $dateEnd)
{
    global $ezzeTeamsModel;

    $users = $ezzeTeamsModel->getAllApprovedUsersByBranch($branchId);

    $cs = "report/" . md5(time()) . ".csv";
    $handle = fopen($cs, "w");
    fputcsv($handle, ['Company Name', COMPANY_NAME]);
    fputcsv($handle, ['Branch Name', ucwords($branchName)]);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['Date Start', $dateStart]);
    fputcsv($handle, ['Date End', $dateEnd]);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['Name', 'Day', 'Date', 'Start Break', 'End Break', 'Break Total', 'Start Break GPS', 'Distance (meters)', 'Start Break Location Status', 'End Break GPS', 'Distance (meters)', 'Location Status']);

    foreach ($users as $user) {
        $listsData = [];
        $user_id = $user['user_id'];
        $userIdLists[] = $user_id;
        $userData[$user_id] = $user;

        $begin = new DateTime($dateStart);
        $end = new DateTime($dateEnd);

        $breakTimeTotal = '00:00';
        $workTimeWithBreakTotal = '00:00';
        $workTimeTotal = '00:00';
        $totalData = 0;

        for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
            $data = [];
            $params = [];
            $dt = $i->format("Y-m-d");
            $breakData = $ezzeTeamsModel->getAllBreakDataByUserListsAndDate($user_id, $dt);

            foreach ($breakData as $break) {
                if ($break['break_action'] == 'start_break') {
                    $startBreakTime = date("H:i", strtotime($break['user_time']));
                    $startBreakgps = $break['location_lat'] . ", " . $break['location_lon'];
                    $startBreakDist = $break['location_distance'];
                    $startBreakLocSt = $break['location_status'];
                } else {
                    $data = [];

                    $startBreak = $dt . " " . $startBreakTime;
                    $endBreak = $dt . " " . $break['user_time'];

                    $time1 = new DateTime($startBreak);
                    $time2 = new DateTime($endBreak);
                    $diff = $time1->diff($time2)->format('%H:%I');

                    $breakTimeTotal = sum_the_time($breakTimeTotal, $diff);

                    $data[] = $user['firstname'] . " " . $user['lastname'];
                    $data[] = $i->format('D');
                    $data[] = $dt;
                    $data[] = $startBreakTime;
                    $data[] = date("H:i", strtotime($break['user_time']));
                    $data[] = $diff;
                    $data[] = $startBreakgps;
                    $data[] = $startBreakDist * 1000;
                    $data[] = $startBreakLocSt;
                    $data[] = $break['location_lat'] . ", " . $break['location_lon'];
                    $data[] = $break['location_distance'] * 1000;
                    $data[] = $break['location_status'];
                    fputcsv($handle, $data);

                    $startBreakTime = "";
                    $startBreakgps = "";
                    $startBreakDist = "";
                    $startBreakLocSt = "";
                }
                $totalData++;
            }
        }

        if ($totalData > 0) {
            fputcsv($handle, ['Total ' . $user['firstname'], '', '', '', '', $breakTimeTotal]);
        }
        fputcsv($handle, ['', '']);
        fputcsv($handle, ['', '']);
    }
    fclose($handle);
    return $cs;
}

function parseDateCommand($cm, $c)
{
    if (strpos($cm[1], 'today') !== false) {
        $dateStart = date("Y-m-d");
        $dateEnd = date("Y-m-d");
        $b = explode("today", $cm[1]);
        $branchName = trim($b[0]);
        $textMsg = _l('today');
        $dateOk = true;
    } elseif (strpos($cm[1], 'yesterday') !== false) {
        $dateStart = date("Y-m-d", strtotime("-1 days"));
        $dateEnd = date("Y-m-d", strtotime("-1 days"));
        $b = explode("yesterday", $cm[1]);
        $branchName = trim($b[0]);
        $textMsg = _l('yesterday');
        $dateOk = true;
    } elseif (strpos($cm[1], 'last week') !== false) {
        $dateStart = date("Y-m-d", strtotime("last week monday"));
        $dateEnd = date("Y-m-d", strtotime("last week sunday"));
        $b = explode("last week", $cm[1]);
        $branchName = trim($b[0]);
        $textMsg = _l('last_week');
        $dateOk = true;
    } elseif (strpos($cm[1], 'last month') !== false) {
        $dateStart = date("Y-m-d", strtotime("first day of last month"));
        $dateEnd = date("Y-m-d", strtotime("last day of last month"));
        $b = explode("last month", $cm[1]);
        $branchName = trim($b[0]);
        $textMsg = _l('last_month');
        $dateOk = true;
    } elseif (strpos($cm[1], 'last year') !== false) {
        $last_year = date("Y", strtotime("last year"));
        $dateStart = $last_year . "-01-01";
        $dateEnd = $last_year . "-12-31";
        $b = explode("last year", $cm[1]);
        $branchName = trim($b[0]);
        $textMsg = _l('last_year');
        $dateOk = true;
    } elseif (strpos($cm[1], 'this month') !== false) {
        $this_month = date("Y-m");
        $dateStart = $this_month . "-01";
        $dateEnd = date("Y-m-d");
        $b = explode("this month", $cm[1]);
        $branchName = trim($b[0]);
        $textMsg = _l('this_month');
        $dateOk = true;
    } elseif (strpos($cm[1], 'this year') !== false) {
        $this_year = date("Y");
        $dateStart = $this_year . "-01-01";
        $dateEnd = date("Y-m-d");
        $b = explode("this year", $cm[1]);
        $branchName = trim($b[0]);
        $textMsg = _l('this_year');
        $dateOk = true;
    } else {
        $dateReport = $c[2];
        $endStrCmd = end($c);
        $b = explode(end($c), $cm[1]);
        $branchName = trim($b[0]);
        $dateOk = false;
        if ($endStrCmd != trim($cm[1])) {
            $pattern = '/^\d{2}\/\d{2}\/\d{4}-\d{2}\/\d{2}\/\d{4}$/';
            if (preg_match($pattern, $endStrCmd)) {
                $dateS = explode("-", $endStrCmd);
                $patternSingleDate = '/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/';
                if (preg_match($patternSingleDate, trim($dateS[0]))) {
                    $dateStart = DateTime::createFromFormat('d/m/Y', trim($dateS[0]))->format("Y-m-d");
                    if (count($dateS) > 1) {
                        if (preg_match($patternSingleDate, trim($dateS[1]))) {
                            $dateEnd = DateTime::createFromFormat('d/m/Y', trim($dateS[1]))->format("Y-m-d");
                            $textMsg = "From " . date('d/m/Y', strtotime($dateStart)) . " To " . date('d/m/Y', strtotime($dateEnd));
                            $dateOk = true;
                        }
                    } else {
                        $dateEnd = $dateStart;
                        $textMsg = date('d/m/Y', strtotime($dateStart));
                        $dateOk = true;
                    }
                }
            } else {
                $dateStart = $endStrCmd;
                $dateEnd = "";
                $b = "";
                $branchName = "";
                $textMsg = "";
                $dateOk = false;
            }
        }
    }

    $result = [
        'date_start' => $dateStart,
        'date_end' => $dateEnd,
        'b' => $b,
        'branch_name' => $branchName,
        'text_msg' => $textMsg,
        'dateOk' => $dateOk
    ];
    return $result;
}

function generateVisitMsg($id, $tgUsername = '')
{
    global $admin_id, $ezzeTeamsModel;

    $visitData = $ezzeTeamsModel->getVisitDataById($id);

    $startEnd = ($visitData['visit_action'] == 'start_visit') ? "just Started a Visit" : "just Ended a Visit";

    $msg = "<strong>" . $visitData['firstname'] . " " . $visitData['lastname'] . "</strong> ".$tgUsername." " . $startEnd . "" .
        "__<strong>Time: </strong>" . date("H:i", strtotime($visitData['visit_time'])) .
        "_<strong>Note: </strong>" . $visitData['visit_notes'] .
        "_<strong>MAP: </strong>" . "/showMapVisit" . $id .
        "_/viewEmployee" . $visitData['user_id'];
    if ($visitData['visit_selfie_msg_id'] != '') {
        prepareMessage(null, $msg, $visitData['visit_selfie_msg_id'], 'sendPhoto', $admin_id);
    } else {
        prepareMessage(null, $msg, null, null, $admin_id);
    }
    //prepareLocationMessage($admin_id, $visitData['visit_lat'], $visitData['visit_lon']);
    return true;
}

function getTgUsername($user_id) {
    return "";
    $params['chat_id'] = $user_id;
    $userChat = sendMessage($params, 'getChat', false);
    if (isset($userChat->result->username)) {
        $tgUsername = $userChat->result->username;
        $tgUsername = "@" . str_replace(array('_'), array('--'), $tgUsername);
    } else {
        $tgUsername = "";
    }
    return $tgUsername;
}

function detailEmployee($user_data)
{
    $tgUsername = ($user_data['tg_username'] != '') ? '@'.str_replace("_", "###", $user_data['tg_username']) : "";
    return "<strong>Details Information:</strong>" .
        "__<strong>First Name: </strong>" . $user_data['firstname'] .
        "_<strong>Last Name: </strong>" . $user_data['lastname'] .
        "_<strong>Username: </strong> " . $tgUsername  .
        "_<strong>Phone Number: </strong>" . $user_data['phone'] .
        "_<strong>Employee ID: </strong>" . $user_data['email'] .
        "_<strong>Work Location: </strong>" . $user_data['branch_name'] .
        "_<strong>Job Description: </strong>" . $user_data['jobdesc'] .
        "_<strong>Notes: </strong>" . $user_data['notes'] .
        "_<strong>Registration Date: </strong>" . date("d/m/Y H:i", strtotime($user_data['created_at']));
}

function setEmployeeMenu($user_id)
{
    global $userCommands;

    $params['commands'] = $userCommands;
    $params['scope'] = json_encode(array(
        'type' => 'chat',
        'chat_id' => $user_id
    ), JSON_UNESCAPED_UNICODE);
    sendMessage($params, 'setMyCommands', false);
    return true;
}

function setAdminMenu($user_id)
{
    global $adminCommands;

    $params['commands'] = $adminCommands;
    $params['scope'] = json_encode(array(
        'type' => 'chat',
        'chat_id' => $user_id
    ));
    sendMessage($params, 'setMyCommands');
    return true;
}

function saveVideoToLocal($fileId)
{
    global $api_key;

    $params['file_id'] = $fileId;
    $file = sendMessage($params, 'getFile');
    $url = 'https://api.telegram.org/file/bot' . $api_key . '/' . $file->result->file_path;
    $img = __DIR__ . '/../../images/' . $fileId . '.mp4';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $videoData = curl_exec($ch);
    curl_close($ch);

    file_put_contents($img, $videoData);
    return $img;
}

function saveImgToLocal($fileId)
{
    global $api_key;

    $params['file_id'] = $fileId;
    $file = sendMessage($params, 'getFile');
    $url = 'https://api.telegram.org/file/bot' . $api_key . '/' . $file->result->file_path;
    $img = __DIR__ . '/../../images/' . $fileId . '.jpg';
    file_put_contents($img, file_get_contents($url));
    return $img;
}

function showOnBreakToday()
{
    global $ezzeTeamsModel;

    $lists = $ezzeTeamsModel->listEmployeeClockInToday('on_break');
    $msg = "<b><u>List Employee on Break_</u></b>";
    $i = 1;
    foreach ($lists as $list) {
        $msg .= "_" . $i . ". " . $list['firstname'] . " " . $list['lastname'] . " [Working Time: " . $list['start_time'] . "-" . $list['end_time'] . "]";
        $msg .= " /viewEmployee" . $list['user_id'];
        $i++;
    }
    return $msg;
}

function showClockedInToday()
{
    global $ezzeTeamsModel;

    $lists = $ezzeTeamsModel->listEmployeeClockInToday('clock_in_done');
    $msg = "<b><u>List Employee ready at Office_</u></b>";
    $i = 1;
    foreach ($lists as $list) {
        $msg .= "_" . $i . ". " . $list['firstname'] . " " . $list['lastname'] . " [Working Time: " . $list['start_time'] . "-" . $list['end_time'] . "]";
        $msg .= " /viewEmployee" . $list['user_id'];
        $i++;
    }
    return $msg;
}

function generateWorkingSchedule($user_id)
{
    global $ezzeTeamsModel;

    $schedule = $ezzeTeamsModel->getUserSchedule($user_id);
    $msg = "__<b>Working Schedule:</b> ";
    foreach ($schedule as $s) {
        $msg .= "_" . $s['work_day'] . ": <code>" . $s['start_time'] . "-" . $s['end_time'] . "</code>";
    }
    return $msg;
}

function generateDetilEmployeeBtn($user_data)
{
    global $admin_id;
    $data = $user_data['user_id'] . "_" . $user_data['firstname'];

    $btn = [];
    $btnLine1 =
        [
            [
                "text" => _l('button_change_working_time'),
                "callback_data" => "change-working-time_" . $data
            ]
        ];
    $btnLine2 = [];
    $btnLine3 = [];

    if (in_array($user_data['user_id'], $admin_id)) {
        $admin_btn = [
            "text" => _l('button_remove_as_admin'),
            "callback_data" => "remove-admin_" . $data
        ];
        array_push($btnLine1, $admin_btn);
    } else {
        $admin_btn = [
            "text" => _l('button_set_as_admin'),
            "callback_data" => "set-admin_" . $data
        ];

        $refresh_btn = [
            "text" => _l('button_refresh'),
            "callback_data" => "refresh-employee_" . $data
        ];
        array_push($btnLine3, $refresh_btn);

        array_push($btnLine1, $admin_btn);
    }

    if ($user_data['approval_status'] == 'approved') {
        $user_btn = [
            "text" => _l('button_change_work_location'),
            "callback_data" => "change-work-location_" . $data
        ];
        array_push($btnLine2, $user_btn);
        $user_btn = [
            "text" => _l('button_set_inactive'),
            "callback_data" => "set-inactive_" . $data
        ];
        array_push($btnLine2, $user_btn);
    }

    array_push($btn, $btnLine1);
    array_push($btn, $btnLine2);
    if (isset($refresh_btn) && is_array($refresh_btn)) {
        array_push($btn, $btnLine3);
    }
    return $btn;
}

function generateEmployeePrevNextBtn($page = 1)
{
    return [
        [
            "text" => _l('button_previous'),
            "callback_data" => "employee-prev_$page"
        ],
        [
            "text" => _l('button_next'),
            "callback_data" => "employee-next_$page"
        ]
    ];
}

function generateApprovedEmployee($chat_id = null, $is_edit = false, $limit = 10, $offset = 0, $page = 1)
{
    global $ezzeTeamsModel, $admin_id;

    $user_data = $ezzeTeamsModel->getApprovedUser($offset, $limit);
    /*if (count($user_data) < 1) {
        $offset = $offset - $limit;
        $page = $page - 1;
        return generateApprovedEmployee($chat_id, $is_edit, $limit, $offset, $page);
    }*/
    $list = "<strong><u>" . _l('admin_list_active_employee') . "</u></strong>";

    foreach ($user_data as $i => $data) {
        if ($data['approval_status'] == '') {
            if (in_array($data['user_id'], $admin_id)) {
                $offset = $offset + 1;
                $list .= "__<strong>" . $offset . "</strong>. " . $data['firstname'] . ' ' . $data['lastname'] . chr(32) . "/viewEmployee" . $data['user_id'];
            }
        } else {
            $offset = $offset + 1;
            $list .= "__<strong>" . $offset . "</strong>. " . $data['firstname'] . ' ' . $data['lastname'] . chr(32) . "/viewEmployee" . $data['user_id'];
        }
    }

    if (!is_null($is_edit) && $is_edit == true) {
        $current_user_data = $ezzeTeamsModel->getUserByID($chat_id[0]);
        $inline_keyboard_config_asking_approval = generateEmployeePrevNextBtn($page);
        if (!is_null($current_user_data['list_emp_msg_id'])) {
            prepareMessage(null, $list, null, 'editMessageText', $chat_id, null, true, $inline_keyboard_config_asking_approval, $current_user_data['list_emp_msg_id']);
        } else {
            $inline_keyboard_config_asking_approval = generateEmployeePrevNextBtn();
            prepareMessage(null, $list, null, null, null, null, true, $inline_keyboard_config_asking_approval);
        }
    } else {
        $inline_keyboard_config_asking_approval = generateEmployeePrevNextBtn();
        prepareMessage(null, $list, null, null, null, null, true, $inline_keyboard_config_asking_approval);
    }

}

function isTimeWorkingNow($userId, $retval = false)
{
    global $ezzeTeamsModel, $botSettings;

    $lastClock = $ezzeTeamsModel->getLastClock2($userId);
    $isWorking = false;
    $uData = $ezzeTeamsModel->getDetilUserById($userId);
    $hourMaxLate = 4;
    if (is_array($lastClock) && $lastClock['is_clock_in'] == 'clock_in') {
        $workingDay = $ezzeTeamsModel->getWorkingDayDataByUser($userId, $lastClock['clock_in_day']);
        $dateLastClock = $lastClock['created_at'];
        $dateStart = DateTime::createFromFormat('Y-m-d H:i:s', $dateLastClock)->format("Y-m-d H:i:s");
        $dateDStart = DateTime::createFromFormat('Y-m-d H:i:s', $dateLastClock)->format("Y-m-d");

        $wE = explode(":", $workingDay['end_time']);
        $wS = explode(":", $workingDay['start_time']);

        if (intVal($wE[0]) < intVal($wS[0])) {
            $dateEnd = date('Y-m-d', strtotime($dateDStart . '+1 day')) . " " . $workingDay['end_time'] . ':00';
            $dateEndStr = strtotime($dateEnd);
        } else {
            $dateEnd = $dateDStart . " " . $workingDay['end_time'] . ':00';
            $dateEndStr = strtotime($dateEnd);
        }

        $timeNow = time();
        $timediffFromLastClockOut = ($timeNow - $dateEndStr) / 3600;
        if ($timeNow < $dateEndStr) {
            $isWorking = true;
        } else {
            $nextWorkingDay = $ezzeTeamsModel->getWorkingDayDataByUser($userId, date("D"));
            //prepareMessage(null, json_encode($nextWorkingDay));exit;
            if ($timediffFromLastClockOut > $hourMaxLate && $uData['step'] == 'clock_in_done') {
                $lastClock['created_at'] = date("Y-m-d H:i:s", $dateEndStr);
                $lastClock['is_clock_in'] = 'clock_out';
                $ezzeTeamsModel->insertUserAbsent($lastClock);
                $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_out_done'");
                $uData = $ezzeTeamsModel->getDetilUserById($userId);
                $r = getMsgByStep($uData);
                prepareMessage($r['keyboard'], _l('please_refresh'));
                exit;
                $isWorking = false;
            } else if (is_array($nextWorkingDay)) {
                $nextStart = DateTime::createFromFormat('H:i', $nextWorkingDay['start_time'])->format("Y-m-d H:i:s");
                $nextStartStr = strtotime($nextStart) - (3600);

                if ($timeNow >= $nextStartStr) {
                    $isWorking = true;
                } else {
                    $d = strtotime($nextStart) - $timeNow;
                    if ($d >= (3600 * $hourMaxLate) && $d <= $nextStartStr) {
                        $isWorking = true;
                    }
                }
            } else {
                $isWorking = true;
            }
        }
    } else {
        $day = (isset($lastClock['clock_in_day']) ? $lastClock['clock_in_day'] : date("D"));
        $workingDay = $ezzeTeamsModel->getWorkingDayDataByUser($userId, $day);
        if (is_array($workingDay)) {
            $isWorking = true;
        } else {
            $isWorking = false;
        }
    }
    if ($retval) {
        if ($isWorking) {
            return $workingDay;
        }
    }
    return $isWorking;
}

function isWorkingNow()
{
    global $ezzeTeamsModel, $userId;

    $current_day = date('D');
    $working_day = getWorkday();
    if ($working_day > 0) {
        return true;
    }

    return false;
}

function run_cron($url, $id)
{
    global $ezzeTeamsModel;

    file_get_contents($url);
    $ezzeTeamsModel->CronRunUpdate($id);
    return true;
}

function _l($key, $params = array())
{
    global $langBase;

    $val = '';
    if (!isset($langBase[$key])) {
        return $key;
    }
    $originalWords = $langBase[$key];

    $numParams = count($params);
    $numVars = substr_count($originalWords, '%s');
    $x = explode(' ', $originalWords);
    if ($x > 0) {
        $k = 0;
        for ($i = 0; $i < count($x); $i++) {
            $var = $x[$i];
            if ($x[$i] == '%s') {
                $var = $params[$k];
                $k++;
            }
            $val .= $var . " ";
        }
    }
    return trim($val);
}

function initDeadManFeature($user_id, $taskTime = 30)
{
    global $ezzeTeamsModel;

    $day = date("D");
    $numTasks = rand(1, 3);

    $workingData = $ezzeTeamsModel->getWorkingDayDataByUser($user_id, $day);

    $time1 = new DateTime($workingData['end_time']);
    $time2 = new DateTime($workingData['start_time']);
    if ($time1->format('U') < $time2->format('U')) {
        $time1 = $time1->modify('+1 day');
    }

    $interval = $time1->diff($time2);
    $difTimes = $interval->format('%H:%I');
    $dif = explode(':', $difTimes);
    $difInMinutes = ($dif[0] * 60) + $dif[1];
    $intervalTasks = floor($difInMinutes / $numTasks);

    $todayStart = date("Y-m-d") . " " . $workingData['start_time'];
    $todayEnd = date("Y-m-d") . " " . $workingData['end_time'];

    $n = 0;
    for ($i = 1; $i <= $difInMinutes; $i += $intervalTasks) {
        $params['user_id'] = $user_id;
        $min = $i - 1;
        $max = ($min + $intervalTasks) - $taskTime;
        $r = rand($min, $max);

        $time = new DateTime($todayStart);
        $time->add(new DateInterval('PT' . $r . 'M'));
        $params['task_start'] = $time->format('Y-m-d H:i');

        $time = new DateTime($params['task_start']);
        $time->add(new DateInterval('PT' . $taskTime . 'M'));
        $params['task_end'] = $time->format("Y-m-d H:i");

        $m = $ezzeTeamsModel->initDailyTask($params);
        $n++;
    }
    return true;
}

function getEventReportByDate($branchId, $branchName, $dateStart, $dateEnd)
{
    global $ezzeTeamsModel;

    $users = $ezzeTeamsModel->getAllApprovedUsersByBranch($branchId);

    $cs = "report/" . md5(time()) . ".csv";
    $handle = fopen($cs, "w");
    fputcsv($handle, ['Company Name', COMPANY_NAME]);
    fputcsv($handle, ['Branch Name', ucwords($branchName)]);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['Date Start', $dateStart]);
    fputcsv($handle, ['Date End', $dateEnd]);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['Name', 'Day', 'Date', 'All OK', 'Absent', 'Clock In Late', 'Clock Out Early', 'Clock In Wrong Location', 'Clock Out Wrong Location']);

    foreach ($users as $user) {
        $listsData = [];
        $user_id = $user['user_id'];
        $userIdLists[] = $user_id;
        $userData[$user_id] = $user;

        $begin = new DateTime($dateStart);
        $end = new DateTime($dateEnd);
        $totalData = 0;
        $userRow = 0;

        for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
            $data = [];
            $params = [];
            $dt = $i->format("Y-m-d");
            $clockInData = $ezzeTeamsModel->getAllClockDataByUserListsAndDate($user_id, $dt, 'clock_in');
            $absent = $ezzeTeamsModel->getAllClockDataByUserListsAndDate($user_id, $dt, 'absent');
            $isAbsent = false;
            if (is_array($absent)) {
                $isAbsent = true;
            }
            if ($isAbsent) {
                $data[] = $user['firstname'] . " " . $user['lastname'];
                $data[] = $i->format('D');
                $data[] = $odt;
                $data[] = "";
                $data[] = "X";

                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($handle, $data);
                $totalData++;
            } else if (is_array($clockInData)) {
                $params['clockIntLoc'] = $clockInData['clock_in_lat'] . ", " . $clockInData['clock_in_lon'];
                $params['clockInDist'] = $clockInData['clock_in_distance'];
                $params['clockInLocStatus'] = $clockInData['clock_in_location_status'];;
                $params['clockInTimeStatus'] = $clockInData['clock_in_time_status'];

                $workingHour = $ezzeTeamsModel->getWorkingDayDataByUser($user_id, $i->format('D'));
                $x = explode(':', $workingHour['start_time']);
                $x2 = explode(':', $workingHour['end_time']);
                $odt = $dt;
                $ndt = $dt;
                if ($x2[0] < $x[0]) {
                    $n = new DateTime($dt);
                    $dt = $n->modify("+  1 day")->format("Y-m-d");
                    $ndt = $dt;
                }

                $clockOutData = $ezzeTeamsModel->getAllClockDataByUserListsAndDate($user_id, $dt, 'clock_out');
                if (is_array($clockOutData) > 0) {
                    $params['clockOutLoc'] = $clockOutData['clock_in_lat'] . ", " . $clockOutData['clock_in_lon'];
                    $params['clockOutDist'] = $clockOutData['clock_in_distance'];
                    $params['clockOutLocStatus'] = $clockOutData['clock_in_location_status'];
                    $params['clockOutTimeStatus'] = $clockOutData['clock_in_time_status'];
                } else {
                    $day = $i->format("D");
                    $params['clockOutLoc'] = '';
                    $params['clockOutDist'] = '';
                    $params['clockOutLocStatus'] = '';
                    $params['clockOutTimeStatus'] = '';
                }
                if (!is_array($clockOutData) && !is_array($clockInData)) {
                    continue;
                }

                $data[] = $user['firstname'] . " " . $user['lastname'];
                $data[] = $i->format('D');
                $data[] = $odt;
                if ($params['clockInLocStatus'] == 'OK' && $params['clockInTimeStatus'] != 'LATE' && $params['clockOutLocStatus'] == 'OK' && $params['clockOutTimeStatus'] != 'EARLY CLOCK OUT') {
                    $data[] = "X";
                } else {
                    $data[] = "";
                }
                $data[] = "";
                $data[] = ($params['clockInTimeStatus'] == 'LATE') ? "X" : "";
                $data[] = ($params['clockOutTimeStatus'] == 'EARLY CLOCK OUT') ? "X" : "";
                $data[] = ($params['clockInLocStatus'] == 'WRONG LOCATION') ? "X" : "";
                $data[] = ($params['clockOutLocStatus'] == 'WRONG LOCATION') ? "X" : "";

                //fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($handle, $data);
                $totalData++;
                $userRow++;
            }
        }
        if ($begin != $end) {
            if ($userRow > 0) {
                fputcsv($handle, ['', '']);
                fputcsv($handle, ['', '']);
            }
        }
    }
    fclose($handle);
    return $cs;
}

function getReportByDate($branchId, $branchName, $dateStart, $dateEnd)
{
    global $ezzeTeamsModel;

    $users = $ezzeTeamsModel->getAllApprovedUsersByBranch($branchId);

    $cs = "report/" . md5(time()) . ".csv";
    $handle = fopen($cs, "w");
    fputcsv($handle, ['Company Name', COMPANY_NAME]);
    fputcsv($handle, ['Branch Name', ucwords($branchName)]);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['Date Start', $dateStart]);
    fputcsv($handle, ['Date End', $dateEnd]);
    fputcsv($handle, ['', '']);
    fputcsv($handle, ['Name', 'Day', 'Date', 'Clock-In', 'Clock-Out', 'Break Count', 'Break Total', 'Work Hours', 'Total Work Hours', 'Clock-In GPS', 'Clock-In Distance (meters)', 'Clock-IN Location Status', 'Clock-In Time Status', 'Clock-Out GPS', 'Clock-Out Distance (meters)', 'Clock-Out Location Status', 'Clock-Out Time Status']);

    foreach ($users as $user) {
        $listsData = [];
        $user_id = $user['user_id'];
        $userIdLists[] = $user_id;
        $userData[$user_id] = $user;

        $begin = new DateTime($dateStart);
        $end = new DateTime($dateEnd);

        $breakTimeTotal = '00:00';
        $workTimeWithBreakTotal = '00:00';
        $workTimeTotal = '00:00';
        $totalData = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
            $data = [];
            $params = [];
            $dt = $i->format("Y-m-d");
            $clockInData = $ezzeTeamsModel->getAllClockDataByUserListsAndDate($user_id, $dt, 'clock_in');
            if (is_array($clockInData) > 0) {
                $params['clockIntLoc'] = $clockInData['clock_in_lat'] . ", " . $clockInData['clock_in_lon'];
                $params['clockInDist'] = $clockInData['clock_in_distance'];
                $params['clockInLocStatus'] = $clockInData['clock_in_location_status'];;
                $params['clockInTimeStatus'] = $clockInData['clock_in_time_status'];

                $workingHour = $ezzeTeamsModel->getWorkingDayDataByUser($user_id, $i->format('D'));
                $x = explode(':', $workingHour['start_time']);
                $x2 = explode(':', $workingHour['end_time']);
                $odt = $dt;
                $ndt = $dt;
                if ($x2[0] < $x[0]) {
                    $n = new DateTime($dt);
                    $dt = $n->modify("+  1 day")->format("Y-m-d");
                    $ndt = $dt;
                }

                $clockOutData = $ezzeTeamsModel->getAllClockDataByUserListsAndDate($user_id, $dt, 'clock_out');
                if (is_array($clockOutData) > 0) {
                    if (isset($clockOutData['clock_in_time'])) {
                        $time1 = new DateTime($odt . " " . $clockInData['clock_in_time']);
                        $time2 = new DateTime($ndt . " " . $clockOutData['clock_in_time']);
                        $interval = $time1->diff($time2);
                        $params['workTime'] = $interval->format('%H:%I');
                    } else {
                        $time1 = new DateTime($odt . " " . $clockInData['clock_in_time']);
                        $time2 = new DateTime($ndt . " " . $clockInData['clock_in_time']);
                        $interval = $time1->diff($time2);
                        $params['workTime'] = $interval->format('%H:%I');
                    }

                    $params['clockOutLoc'] = $clockOutData['clock_in_lat'] . ", " . $clockOutData['clock_in_lon'];
                    $params['clockOutDist'] = $clockOutData['clock_in_distance'];
                    $params['clockOutLocStatus'] = $clockOutData['clock_in_location_status'];
                    $params['clockOutTimeStatus'] = $clockOutData['clock_in_time_status'];
                } else {
                    $day = $i->format("D");
                    //$workingDay = $ezzeTeamsModel->getWorkingDayDataByUser($user_id, $day);
                    $time1 = new DateTime($odt . " " . $clockInData['clock_in_time']);
                    $time2 = new DateTime($ndt . " " . $workingHour['end_time']);
                    $interval = $time1->diff($time2);
                    $params['workTime'] = $interval->format('%H:%I');
                }
                if (!is_array($clockOutData) && !is_array($clockInData)) {
                    continue;
                }
                $totalBreak = $ezzeTeamsModel->calculateUserBreakByDay($user_id, $odt . " " . $workingHour['start_time'], $ndt . " " . $workingHour['end_time']);
                $time1 = new DateTime($params['workTime']);
                $time2 = new DateTime($totalBreak['total_time']);
                $params['totalWorkingHours'] = $time1->diff($time2)->format('%H:%I');

                $workTime = isset($params['workTime']) ? $params['workTime'] : '00:00';

                $data[] = $user['firstname'] . " " . $user['lastname'];
                $data[] = $i->format('D');
                $data[] = $odt;
                $data[] = isset($clockInData['clock_in_time']) ? $clockInData['clock_in_time'] : '';
                $data[] = isset($clockOutData['clock_in_time']) ? $clockOutData['clock_in_time'] : '00:00';
                $data[] = $totalBreak['total_break']; //break count
                $data[] = $totalBreak['total_time']; //break total
                $data[] = isset($params['workTime']) ? $params['workTime'] : ''; //work hours
                $data[] = $params['totalWorkingHours']; //total work hours (work hours - break total)

                $data[] = isset($params['clockIntLoc']) ? $params['clockIntLoc'] : '';
                $data[] = isset($params['clockInDist']) ? ($params['clockInDist'] * 1000) : '';
                $data[] = isset($params['clockInLocStatus']) ? $params['clockInLocStatus'] : '';
                $data[] = isset($params['clockInTimeStatus']) ? $params['clockInTimeStatus'] : '';

                $data[] = isset($params['clockOutLoc']) ? $params['clockOutLoc'] : '';
                $data[] = isset($params['clockOutDist']) ? ($params['clockOutDist'] * 1000) : '';
                $data[] = isset($params['clockOutLocStatus']) ? $params['clockOutLocStatus'] : '';
                $data[] = isset($params['clockOutTimeStatus']) ? $params['clockOutTimeStatus'] : '';

                $breakTimeTotal = sum_the_time($breakTimeTotal, $totalBreak['total_time']);
                $workTimeWithBreakTotal = sum_the_time($workTimeWithBreakTotal, $workTime);
                $workTimeTotal = sum_the_time($workTimeTotal, $params['totalWorkingHours']);
                fputcsv($handle, $data);
                $totalData++;
            }
        }
        if ($totalData > 0) {
            fputcsv($handle, ['Total ' . $user['firstname'], '', '', '', '', '', $breakTimeTotal, $workTimeWithBreakTotal, $workTimeTotal]);
        }
        fputcsv($handle, ['', '']);
        fputcsv($handle, ['', '']);
    }
    fclose($handle);
    return $cs;
}

function sum_the_time($time1, $time2)
{
    $times = array($time1, $time2);
    $seconds = 0;
    foreach ($times as $time) {
        $x = explode(':', $time);
        $hour = (isset($x[0]))? $x[0] : 0;
        $minute = (isset($x[1]))? $x[1] : 0;
        $second = (isset($x[2]))? $x[2] : 0;
        $seconds += $hour * 3600;
        $seconds += $minute * 60;
        $seconds += $second;
    }
    $hours = floor($seconds / 3600);
    $seconds -= $hours * 3600;
    $minutes = floor($seconds / 60);
    $seconds -= $minutes * 60;
    if ($seconds < 9) {
        $seconds = "0" . $seconds;
    }
    if ($minutes < 9) {
        $minutes = "0" . $minutes;
    }
    if ($hours < 9) {
        $hours = "0" . $hours;
    }
    return "{$hours}:{$minutes}";
}

function getLastStatusByDataUseId($userId)
{
    global $ezzeTeamsModel;

    $current_day = date('D');

    if (isTimeWorkingNow($userId)) {
        $lastClock = $ezzeTeamsModel->getLastClock2($userId);
        if ($lastClock['is_clock_in'] == 'clock_in') {
            $lastBreak = $ezzeTeamsModel->getLastRecordBreak();
            $lastVisit = $ezzeTeamsModel->getLastVisitUserDataToday($userId);
            if ($lastBreak['break_action'] == 'start_break') {
                $step = "on_break";
            } elseif ($lastVisit['visit_action'] == 'start_visit') {
                $step = "on_visit";
            } else {
                $step = "clock_in_done";
            }
        } else {
            $step = "clock_out_done";
        }
    } else {
        $step = "clock_out_done";
    }
    $v = (int)1;
    $ezzeTeamsModel->changeCompleteUserStep($userId, $v);

    $ezzeTeamsModel->setUserStep($step);
    $uData = $ezzeTeamsModel->getDetilUserById($userId);
    return getMsgByStep(array('step' => $step, 'can_visit' => $uData['can_visit'], 'can_break' => $uData['can_break']));
}

function getLastStatusByData($user_id)
{
    global $ezzeTeamsModel;

    $current_day = date('D');

    if (isTimeWorkingNow($user_id)) {
        $lastClock = $ezzeTeamsModel->getLastClock2($user_id);
        if ($lastClock['is_clock_in'] == 'clock_in') {
            $lastBreak = $ezzeTeamsModel->getLastRecordBreakUseId($user_id);
            $lastVisit = $ezzeTeamsModel->getLastVisitUserDataToday($user_id);
            if (isset($lastBreak['break_action']) && $lastBreak['break_action'] == 'start_break') {
                $step = "on_break";
            } elseif (isset($lastVisit['visit_action']) && $lastVisit['visit_action'] == 'start_visit') {
                $step = "on_visit";
            } else {
                $step = "clock_in_done";
            }
        } else {
            $step = "clock_out_done";
        }
    } else {
        $step = "clock_out_done";
    }

    $v = (int)1;
    $ezzeTeamsModel->changeCompleteUserStep($user_id, $v);

    $ezzeTeamsModel->setUserStepUseId($user_id, $step);
    $uData = $ezzeTeamsModel->getDetilUserById($user_id);

    return getMsgByStep(array('step' => $step, 'can_visit' => $uData['can_visit'], 'can_break' => $uData['can_break']));
}

function getMsgByStep($user_data)
{
    global $botSettings;

    if ($user_data['step'] == 'clock_in_done') {
        $keyboard = array();
        if ($botSettings['module_break'] == 1) {
            if ($user_data['can_break'] == 1) {
                array_push($keyboard, [_l('button_start_break')]);
            }
        }
        if ($botSettings['module_visit'] == 1) {
            if ($user_data['can_visit'] == 1) {
                array_push($keyboard, [_l('button_start_visit')]);
            }
        }
        //$keyboard = array(array(_l('button_start_break')), array(_l('button_clock_out')));
        array_push($keyboard, [_l('button_clock_out')]);
        $msg = _l('already_clock_in');
    } elseif ($user_data['step'] == 'on_break') {
        $keyboard = array(array(_l('button_end_break')), array(_l('button_clock_out')));
        $msg = _l('already_break');
    } elseif ($user_data['step'] == 'on_visit') {
        $keyboard = array(array(_l('button_end_visit')), array(_l('button_clock_out')));
        $msg = _l('already_break');
    } else {
        $keyboard = generateClockInBtn();
        $msg = _l('option_menu');
    }

    return array('keyboard' => $keyboard, 'message' => $msg);
}

function isWorkingDay()
{
    global $ezzeTeamsModel, $userId;

    $current_day = date('D');
    $working_day = $ezzeTeamsModel->getWorkday($userId, $current_day, true);
    if ($working_day > 0) {
        return true;
    }

    return false;
}

function deleteMessage($msg_id, $user_id)
{
    $params = [
        'chat_id' => $user_id,
        'message_id' => $msg_id,
    ];
    sendMessage($params, 'deleteMessage');
}

function prepareMessage($keyboard = null, $msg = null, $photo_url = null, $method = null, $chat_id = null,
                        $keyboard_config = array('resize' => true, 'one_time' => false, 'force_reply' => true),
                        $inline_keyboard = false, $inline_keyboard_config = null, $edit_msg_id = null,
                        $multiple_inline = false, $document = null, $video = null)
{
    global $userId, $ezzeTeamsModel;

    $keyboard_settings = [
        'keyboard' => $keyboard,
        'resize_keyboard' => (isset($keyboard_config['resize'])) ? $keyboard_config['resize'] : true,
        'one_time_keyboard' => (isset($keyboard_config['one_time'])) ? $keyboard_config['one_time'] : false,
        'force_reply_keyboard' => (isset($keyboard_config['force_reply'])) ? $keyboard_config['force_reply'] : true,
    ];

    if ($inline_keyboard) {
        $keyboard_settings = ["inline_keyboard" => [$inline_keyboard_config]];
        if ($multiple_inline) {
            $keyboard_settings = ["inline_keyboard" => $inline_keyboard_config];
        }
    }

    $text = str_replace(array('_'), chr(10), $msg);
    $text = str_replace(array('###'), array('_'), $text);

    $params = [
        'chat_id' => $userId,
        'parse_mode' => 'HTML',
    ];

    if ($method == 'editMessageText') {
        $params['message_id'] = $edit_msg_id;
    }

    if (isset($msg) && !isset($photo_url)) {
        $params['text'] = $text;
    }

    if (isset($photo_url)) {
        $params['caption'] = $text;
        $params['photo'] = $photo_url;
    }

    if (isset($keyboard) || $inline_keyboard) {
        $params['reply_markup'] = json_encode($keyboard_settings);
    }

    if (isset($document)) {
        $params['caption'] = $text;
        $params['document'] = $document;
    }

    if (isset($video)) {
        $params['caption'] = $text;
        $params['video'] = $video;
    }
    $params['disable_message_delete'] = true;

    if (!is_null($chat_id) && isset($chat_id) && is_array($chat_id) && count($chat_id) > 0) {
        foreach ($chat_id as $id) {
            $params['chat_id'] = $id;
            sendMessage($params, $method);
        }
    } else {
        sendMessage($params, $method);
    }

}

function prepareForwardMessage($admin_id, $message_id)
{
    global $userId;
    foreach ($admin_id as $id) {
        $params = [
            'chat_id' => $id,
            'from_chat_id' => $userId,
            'message_id' => $message_id
        ];
        sendMessage($params, 'forwardMessage');
    }
}

function prepareLocationMessage($admin_id, $lat, $lon)
{
    foreach ($admin_id as $id) {
        $params = [
            'chat_id' => $id,
            'latitude' => $lat,
            'longitude' => $lon
        ];
        sendMessage($params, 'sendLocation');
    }
}

function sendMessage($params, $method = null, $log = true, $useCA = false)
{
    global $api_key, $ezzeTeamsModel, $adminDetils, $logID;

    $method = isset($method) ? $method : 'sendMessage';
    $url = 'https://api.telegram.org/bot' . $api_key . '/' . $method;

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($handle, CURLOPT_TIMEOUT, 120);
    curl_setopt($handle, CURLOPT_VERBOSE, true);
    curl_setopt($handle, CURLOPT_DNS_SERVERS, "8.8.8.8");
    curl_setopt($handle, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    if ($useCA) {
        curl_setopt($handle, CURLOPT_CAINFO, __DIR__ . "/../../../cacert/cacert.pem");
    }
    //curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($params));
    if ($method == 'sendDocument') {
        $finfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $params['document']);
        $cFile = new CURLFile(realpath($params['document']), $finfo);

        // Add CURLFile to CURL request
        $params['document'] = $cFile;
        curl_setopt($handle, CURLOPT_POSTFIELDS, $params);
    } else {
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    $response = curl_exec($handle);

    if ($response === false) {
        $errorNumber = curl_errno($handle);
        $errorMessage = curl_error($handle);
        curl_close($handle);

        if (!$useCA) {
            sleep(1);
            return sendMessage($params, $method, $log, true);
        }

        // Display the error
        $msg = "CURL ERR: ".$errorNumber." | ".$errorMessage." | ".json_encode($params);
        syslog(LOG_ERR, $msg);
        sendLogMessage($msg);
        return false;
    }

    if (isset($logID)) {
        $ezzeTeamsModel->logReply($logID, $url, $params, $response);
    } else {
        $ezzeTeamsModel->requestLog($url, $params, $response);
    }

    $response = json_decode($response);
    if (isset($response->result->text)) {
        if ($response->result->text == getReceivedNewApplicationNotificationTxt()) {
            $ezzeTeamsModel->updateUserNotificationNewUserMSGID($response->result->message_id, $response->result->chat->id);
        } else if (strpos($response->result->text, 'List of Employee') !== false) {
            $ezzeTeamsModel->updateUserListEmpMSGID($response->result->message_id, $response->result->chat->id);
        } else if (strpos($response->result->text, 'List of Active Employee') !== false) {
            $ezzeTeamsModel->updateUserListEmpMSGID($response->result->message_id, $response->result->chat->id);
        } else if (strpos($response->result->text, 'Day selected') !== false) {
            $ezzeTeamsModel->updateUserDaySelectedMSGID($response->result->message_id, $response->result->chat->id);
        } else if (strpos($response->result->text, 'Working Time For') !== false) {
            $ezzeTeamsModel->updateUserStartTimeMSGID($response->result->message_id, $response->result->chat->id);
        } else if (strpos($response->result->text, 'Please set end time') !== false) {
            $ezzeTeamsModel->updateUserEndTimeMSGID($response->result->message_id, $response->result->chat->id);
        } else if (strpos($response->result->text, 'Scheduled Message Repeat Configurations') !== false) {
            $adminUser = $ezzeTeamsModel->getAdminStep($response->result->chat->id);
            $tempData = json_decode($adminUser['temp'], TRUE);
            $tempData['msgEditId'] = $response->result->message_id;
            $ezzeTeamsModel->setAdminStep($response->result->chat->id, $adminUser['step'], json_encode($tempData));
        } else if (strpos($response->result->text, 'List Scheduled Messages') !== false) {
            $ezzeTeamsModel->updateUserListEmpMSGID($response->result->message_id, $response->result->chat->id);
        }

    }
    return $response;
}

function generateReceivedNewApplicationMSG($user_data)
{
    return "<strong>Details Information:</strong>" .
        "__<strong>First Name: </strong>" . $user_data['firstname'] .
        "_<strong>Last Name: </strong>" . $user_data['lastname'] .
        "_<strong>Phone Number: </strong>" . $user_data['phone'] .
        "_<strong>Employee ID: </strong>" . $user_data['email'] .
        "_<strong>Work Location: </strong>" . $user_data['branch_name'] .
        "_<strong>Registration Date: </strong>" . date("d/m/Y H:i", strtotime($user_data['created_at']));
}

function generateClockInStatusMSG($clock_in_data, $header)
{
    global $admin_id, $ezzeTeamsModel, $botSettings;

    $userProfile = $ezzeTeamsModel->getEmployeeByUserId($clock_in_data['user_id']);
    $distance = ($clock_in_data['clock_in_distance'] < 1) ? ($clock_in_data['clock_in_distance'] * 1000) . " meters" : $clock_in_data['clock_in_distance'] . " kilometers";

    $lastname = ($userProfile['lastname'] == '') ? 'N/A' : $userProfile['lastname'];
    $phone = ($userProfile['phone'] == '') ? 'N/A' : $userProfile['phone'];
    $eId = ($userProfile['email'] == '' || $userProfile['email'] == 'Skip and Send') ? 'N/A' : $userProfile['email'];

    $time1 = strtotime($clock_in_data['work_start_time']);
    $time2 = strtotime($clock_in_data['clock_in_time']);
    $tolleranceH = $time1;
    $tolleranceL = $time1;
    if ($time2 > $tolleranceH) {
        $diff = ($time2 - $tolleranceH) / 60;
        $diff = convertMinToReadable($diff);
        $m = $diff . " " . _l('admin_info_late');
    } elseif ($time2 < $tolleranceL) {
        $diff = ($tolleranceL - $time2) / 60;
        $diff = convertMinToReadable($diff);
        $m = $diff . " " . _l('admin_info_early');
    } else {
        $m = _l('admin_info_within_tollerance');
    }
    $tgUsername = (isset($clock_in_data['tgusername'])) ? $clock_in_data['tgusername'] : "@".str_replace("_", "###", $userProfile['tg_username']);

    $msg = $header .
        "__" . _l('admin_info_job_description') . $userProfile['jobdesc'] .
        "_" . _l('admin_info_work_location') . $userProfile['branch_name'] .
        "__" . _l('admin_info_first_name') . $userProfile['firstname'] .
        "_" . _l('admin_info_last_name') . $lastname .
        "_" . _l('admin_info_tg_username') . $tgUsername .
        "_" . _l('admin_info_phone_number') . $phone .
        "_" . _l('admin_info_employee_id') . $eId .
        "__" . _l('admin_info_sch_clock_in') . $clock_in_data['work_start_time'] .
        "_" . _l('admin_info_actual_clock_in') . $clock_in_data['clock_in_time'] .
        "_" . _l('admin_info_diff_time') . $m .
        "__" . _l('admin_info_distance') . $distance .
        "_" . _l('admin_info_map') . "/showMapClock" . $clock_in_data['id'] .
        "_" . _l('admin_info_employee') . "/viewEmployee" . $clock_in_data['user_id'];

    prepareMessage(null, $msg, $clock_in_data['clock_in_selfie_msg_id'], 'sendPhoto', $admin_id);
    return true;
}

function generateClockOutStatusMSG($clock_in_data, $header)
{
    global $admin_id, $ezzeTeamsModel, $botSettings;

    $userProfile = $ezzeTeamsModel->getEmployeeByUserId($clock_in_data['user_id']);
    $distance = ($clock_in_data['clock_in_distance'] < 1) ? ($clock_in_data['clock_in_distance'] * 1000) . " meters" : $clock_in_data['clock_in_distance'] . " kilometers";

    $lastname = ($userProfile['lastname'] == '') ? 'N/A' : $userProfile['lastname'];
    $phone = ($userProfile['phone'] == '') ? 'N/A' : $userProfile['phone'];
    $eId = ($userProfile['email'] == '' || $userProfile['email'] == 'Skip and Send') ? 'N/A' : $userProfile['email'];

    $time1 = strtotime($clock_in_data['work_start_time']);
    $time2 = strtotime($clock_in_data['clock_in_time']);
    $tolleranceH = $time1;
    $tolleranceL = $time1;
    if ($time2 > $time1) {
        $diff = ($time2 - $tolleranceH) / 60;
        //$diff = convertMinToReadable($diff);
        $m = $diff . " " . _l('admin_info_late');
    } elseif ($time2 < $time1) {
        $diff = ($tolleranceL - $time2) / 60;
        //$diff = convertMinToReadable($diff);
        $m = $diff . " " . _l('admin_info_early');
    } else {
        $m = _l('admin_info_within_tollerance');
    }

    $msg = $header .
        "__" . _l('admin_info_job_description') . $userProfile['jobdesc'] .
        "_" . _l('admin_info_work_location') . $userProfile['branch_name'] .
        "__" . _l('admin_info_first_name') . $userProfile['firstname'] .
        "_" . _l('admin_info_last_name') . $lastname .
        "_" . _l('admin_info_tg_username') . $clock_in_data['tgusername'] .
        "_" . _l('admin_info_phone_number') . $phone .
        "_" . _l('admin_info_employee_id') . $eId .
        "__" . _l('admin_info_sch_clock_out') . $clock_in_data['work_start_time'] .
        "_" . _l('admin_info_actual_clock_out') . $clock_in_data['clock_in_time'] .
        "_" . _l('admin_info_diff_time') . $m .
        "__" . _l('admin_info_distance') . $distance .
        "_" . _l('admin_info_map') . "/showMapClock" . $clock_in_data['id'] .
        "_" . _l('admin_info_employee') . "/viewEmployee" . $clock_in_data['user_id'];

    //prepareMessage(null, $msg, $clock_in_data['clock_in_selfie_msg_id'],  'sendPhoto', [5330895365]);
    prepareMessage(null, $msg, $clock_in_data['clock_in_selfie_msg_id'], 'sendPhoto', $admin_id);
    return true;
}

function generateReceivedNewApplicationBtn($user_data)
{
    $data = $user_data['user_id'] . "_" . $user_data['firstname'];
    return [
        [
            "text" => _l('button_approve'),
            "callback_data" => "approve_" . $data
        ],
        [
            "text" => _l('button_reject'),
            "callback_data" => "reject_" . $data
        ]
    ];
}

function generatePrevNextBtn($page = 1)
{
    return [
        [
            "text" => _l('button_previous'),
            "callback_data" => "prev_$page"
        ],
        [
            "text" => _l('button_next'),
            "callback_data" => "next_$page"
        ]
    ];
}

function generateBranchBtn($id, $name)
{
    global $ezzeTeamsModel;
    $branch = $ezzeTeamsModel->getAllBranch();
    $branch_arr = [];
    $name = 'user';

    foreach ($branch as $i => $data) {
        array_push($branch_arr, [
            "text" => $i + 1 . ". " . $data['branch_name'],
            //"callback_data" => "assign-branch_$id" . "_$name" . "_" . $data['branch_id'] . "_" . $data['branch_name'],
            "callback_data" => "assign-branch_" . $id . "_" . $name . "_" . $data['branch_id'] . "_" . $data['branch_name']
        ]);
    }
    //prepareMessage(null, $name, null, null, array(5330895365));
    return $branch_arr;
}

function generateBranchBtnEmployeeDetil($id, $name)
{
    global $ezzeTeamsModel;
    $branch = $ezzeTeamsModel->getAllBranch();
    $branch_arr = [];

    foreach ($branch as $i => $data) {
        array_push($branch_arr, [
            "text" => $i + 1 . ". " . $data['branch_name'],
            "callback_data" => "ch-assign-branch_$id" . "_$name" . "_" . $data['branch_id'] . "_" . $data['branch_name']
        ]);
    }
    return $branch_arr;
}

function generateBreakStepBtn($id, $name)
{
    $key = [
        [
            "text" => _l('button_yes'),
            "callback_data" => "assign-breakstep_$id" . "_$name" . "_1_yes"
        ],
        [
            "text" => _l('button_no'),
            "callback_data" => "assign-breakstep_$id" . "_$name" . "_0_no"
        ],
    ];

    return $key;
}

function generateBreakModuleUserBtn($id, $name)
{
    $key = [
        [
            "text" => _l('button_yes'),
            "callback_data" => "assign-break_$id" . "_$name" . "_1_yes"
        ],
        [
            "text" => _l('button_no'),
            "callback_data" => "assign-break_$id" . "_$name" . "_0_no"
        ],
    ];

    return $key;
}

function generateVisitAlertUserBtn($id, $name)
{
    $key = [
        [
            "text" => _l('button_yes'),
            "callback_data" => "alert-visit_$id" . "_$name" . "_1_yes"
        ],
        [
            "text" => _l('button_no'),
            "callback_data" => "alert-visit_$id" . "_$name" . "_0_no"
        ],
    ];

    return $key;
}

function generateVisitModuleUserBtn($id, $name)
{
    $key = [
        [
            "text" => _l('button_yes'),
            "callback_data" => "assign-visit_$id" . "_$name" . "_1_yes"
        ],
        [
            "text" => _l('button_no'),
            "callback_data" => "assign-visit_$id" . "_$name" . "_0_no"
        ],
    ];

    return $key;
}

function generatePingModuleUserBtn($id, $name)
{
    $key = [
        [
            "text" => _l('button_yes'),
            "callback_data" => "assign-ping_$id" . "_$name" . "_1_yes"
        ],
        [
            "text" => _l('button_no'),
            "callback_data" => "assign-ping_$id" . "_$name" . "_0_no"
        ],
    ];

    return $key;
}

function generateImportantUserBtn($id, $name)
{
    $key = [
        [
            "text" => _l('button_yes'),
            "callback_data" => "assign-important_$id" . "_$name" . "_1_yes"
        ],
        [
            "text" => _l('button_no'),
            "callback_data" => "assign-important_$id" . "_$name" . "_0_no"
        ],
    ];

    return $key;
}

function generateWorkingDaysBtn($id, $name)
{
    $days_arr = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
    $key_days = array();
    foreach ($days_arr as $i => $day) {
        array_push($key_days, ["text" => "$day", "callback_data" => "assign-work-day" . "_$id" . "_$name" . "_$day"]);
    }
    return $key_days;
}

function generateWorkingDaysBtnNew($id, $day = 1, $hStart = 8, $mStart = 0, $hEnd = 17, $mEnd = 0, $nextDay = false)
{
    $main_arr =
        [
            [
                [
                    "text" => "Start Time",
                    "callback_data" => "none"
                ],
                [
                    "text" => ($nextDay) ? "End Time (Next Day)" : "End Time",
                    "callback_data" => "none"
                ]
            ],
            [
                [
                    "text" => "↑",
                    "callback_data" => "next-h-assign-work-start-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ],
                [
                    "text" => "↑",
                    "callback_data" => "next-mn-assign-work-start-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ],
                [
                    "text" => "↑",
                    "callback_data" => "next-h-assign-work-end-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ],
                [
                    "text" => "↑",
                    "callback_data" => "next-mn-assign-work-end-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ]
            ],
            [
                [
                    "text" => sprintf("%02d", $hStart),
                    "callback_data" => "assign-work-start-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ],
                [
                    "text" => sprintf("%02d", $mStart),
                    "callback_data" => "assign-work-start-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ],
                [
                    "text" => sprintf("%02d", $hEnd),
                    "callback_data" => "assign-work-end-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ],
                [
                    "text" => sprintf("%02d", $mEnd),
                    "callback_data" => "assign-work-end-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ]
            ],
            [
                [
                    "text" => "↓",
                    "callback_data" => "prev-h-assign-work-start-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ],
                [
                    "text" => "↓",
                    "callback_data" => "prev-mn-assign-work-start-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ],
                [
                    "text" => "↓",
                    "callback_data" => "prev-h-assign-work-end-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ],
                [
                    "text" => "↓",
                    "callback_data" => "prev-mn-assign-work-end-time_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ]
            ],
            [
                [
                    "text" => "Day Off",
                    "callback_data" => "dayoff-workingtime_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ]
            ],
            [
                [
                    "text" => "Confirm " . intToDay($day),
                    "callback_data" => "confirm-workingtime_" . $id . "_" . $day . "_" . $hStart . "_" . $mStart . "_" . $hEnd . "_" . $mEnd
                ]
            ]
        ];
    return $main_arr;
}

function generateStartEndTimeBtn($id, $name, $day)
{
    return [
        [
            "text" => _l('button_set_startend_time'),
            "callback_data" => "set-start-end-time-btn_$id" . "_$name" . "_$day"
        ]
    ];
}

function generateWorkingTimeBtn($id, $day, $start_time, $h = '', $mn = '')
{
    $start = $start_time ? '-start' : '-end';

    $main_arr =
        [
            [
                [
                    "text" => "↑",
                    "callback_data" => "next-h-assign-work$start-time_$id" . "_$day" . "_$h" . "_$mn"
                ],
                [
                    "text" => "↑",
                    "callback_data" => "next-mn-assign-work$start-time_$id" . "_$day" . "_$h" . "_$mn"
                ]
            ],
            [
                [
                    "text" => "$h",
                    "callback_data" => "assign-work$start-time_$id" . "_$day"
                ],
                [
                    "text" => "$mn",
                    "callback_data" => "assign-work$start-time_$id" . "_$day"
                ]
            ],
            [
                [
                    "text" => "↓",
                    "callback_data" => "prev-h-assign-work$start-time_$id" . "_$day" . "_$h" . "_$mn"
                ],
                [
                    "text" => "↓",
                    "callback_data" => "prev-mn-assign-work$start-time_$id" . "_$day" . "_$h" . "_$mn"
                ]
            ],
            [
                [
                    "text" => _l('button_ok'),
                    "callback_data" => "okay$start-time_$id" . "_$day" . "_$h" . "_$mn"
                ]
            ]
        ];

    if ($start == '-end') {
        $submit_button =
            [
                [
                    "text" => _l('button_confirm_approve'),
                    "callback_data" => "confirm-approval_$id"
                ]
            ];
        array_push($main_arr, $submit_button);
    }

    return $main_arr;
}

function generateClockInBtn()
{
    return [
        [
            [
                "text" => _l('button_clock_in')
            ]
        ]
    ];
}

function getReceivedNewApplicationNotificationTxt()
{
    return _l('admin_new_registration');
}

function generateListEmployee($chat_id = null, $is_edit = false, $limit = 10, $offset = 0, $page = 1)
{
    global $ezzeTeamsModel;

    $user_data = $ezzeTeamsModel->getAllUser($offset, $limit);
    $list = "<strong><u>" . _l('admin_list_employee_registration') . "</u></strong>";

    foreach ($user_data as $i => $data) {
        $offset = $offset + 1;
        $list .= "__<strong>" . $offset . "</strong>. " . $data['firstname'] . ' ' . $data['lastname'] . chr(32) . "/viewProfile" . $data['user_id'];
    }

    if ($is_edit) {
        $current_user_data = $ezzeTeamsModel->getUserByID($chat_id[0]);
        $inline_keyboard_config_asking_approval = generatePrevNextBtn($page);
        prepareMessage(null, $list, null, 'editMessageText', $chat_id, null, true, $inline_keyboard_config_asking_approval, $current_user_data['list_emp_msg_id']);
    } else {
        $inline_keyboard_config_asking_approval = generatePrevNextBtn();
        prepareMessage(null, $list, null, null, null, null, true, $inline_keyboard_config_asking_approval);
    }

}

function generateProfileCard()
{

    global $ezzeTeamsModel, $admin_id;

    $user_data = $ezzeTeamsModel->getUser('', false);
    $user_data['email'] = ($user_data['email'] == 'Skip and Send') ? 'N/A' : $user_data['email'];
    $user_data['lastname'] = !isset($user_data['lastname']) || $user_data['lastname'] == '' ? 'N/A' : $user_data['lastname'];
    $user_data['branch_name'] = !isset($user_data['branch_name']) || $user_data['branch_name'] == '' ? 'N/A' : $user_data['branch_name'];
    $msg = generateReceivedNewApplicationMSG($user_data);

    prepareMessage(null, $msg, $user_data['photo_id'], 'sendPhoto', $admin_id);
}

function checkLocationTolerance($lat, $long)
{

    global $ezzeTeamsModel;

    $settings = $ezzeTeamsModel->getSettings();
    $user_data = $ezzeTeamsModel->getUser('', false);
    $user_branch = $ezzeTeamsModel->getBranch($user_data['branch_id']);

    $lat1 = $user_branch['branch_lat'];
    $lon1 = $user_branch['branch_lon'];
    $location_tolerance = $settings['location_tolerance'];

    $lat2 = $lat;
    $lon2 = $long;

    function distance($lat1, $lon1, $lat2, $lon2, $unit)
    {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        } else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);

            if ($unit == "K") {
                return ($miles * 1.609344);
            } else if ($unit == "N") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }

    $distance = round(distance($lat1, $lon1, $lat2, $lon2, "K"), 2);

    if ($distance <= $location_tolerance) {
        return array(true, $distance);
    }

    return array(false, $distance);
}

function intToDay($v, $short = false)
{
    $day = [
        '1' => 'Monday',
        '2' => 'Tuesday',
        '3' => 'Wednesday',
        '4' => 'Thursday',
        '5' => 'Friday',
        '6' => 'Saturday',
        '7' => 'Sunday'
    ];

    $dayShort = [
        '1' => 'Mon',
        '2' => 'Tue',
        '3' => 'Wed',
        '4' => 'Thu',
        '5' => 'Fri',
        '6' => 'Sat',
        '7' => 'Sun'
    ];

    $val = '';
    if ($v > 0 && $v < 8) {
        if ($short) {
            $val = $dayShort[$v];
        } else {
            $val = $day[$v];
        }
    }
    return $val;
}

function myErrorHandler($errno, $errstr, $errfile, $errline, $errcontext = '') {
    if((error_reporting() & $errno) === 0) {
        //return false;
    }
    $errorMsg = date("d.m.Y H:i:s")."\r\n\r\nIn file ".$errfile." on line ".$errline.": ".$errno." - ".$errstr;
    syslog(LOG_ERR, $errorMsg);
}
set_error_handler("myErrorHandler");

function sendLogMessage($msg){
    $userId = -777477405;
    $apiKey = "6229111905:AAFS42VysLlOFCOMdxTgsR5D6Vd0yTGKexU";
    $url = 'https://api.telegram.org/bot' . $apiKey . '/sendMessage';

    $params = [
        'chat_id' => $userId,
        'parse_mode' => 'HTML',
        'text' => $msg
    ];

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($handle, CURLOPT_CAINFO, __DIR__ . "/../../../cacert/cacert.pem");
    $response = curl_exec($handle);
    return true;
}