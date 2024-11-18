<?php
$adminStep = $ezzeTeamsModel->getAdminStep($userId);

if (isset($result->message->entities[0]->type)){
    if ($result->message->entities[0]->type == 'bot_command') {
        $adminStep['step'] = '';
    }
}

if ( in_array($action, array(_l('button_start'), _l('button_back_home'), _l('button_cancel'))) ) {
    $ezzeTeamsModel->setAdminStep($userId, '', '');
    prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), _l('admin_account'));
}

else if (strpos($action, '/test') !== false ) {
    //getClockDataById
    if (strpos($action, 'Clock') !== false ) {
        $clock_id = (int) explode('Clock', $action)[1];

        $clock_in_data = $ezzeTeamsModel->getClockDataById($clock_id);
        $m = "Test Clock Messages";
        generateClockInStatusMSG($clock_in_data, $m);
    }
}

else if (strpos($action, '/send') !== false ) {
    $c = explode(' ', strtolower($action));

    if (is_numeric($c[1])) {
        $cmd = $c[0].' '.$c[1];
        $msg = explode($cmd, $action);
        prepareMessage(null, $msg[1], null, null, [$c[1]]);
    }
}

else if (strpos($action, '/showMap') !== false ) {
    if (strpos($action, 'Visit') !== false ) {
        $visit_id = (int) explode('Visit', $action)[1];

        $visitData = $ezzeTeamsModel->getVisitDataById($visit_id);
        prepareLocationMessage([$userId], $visitData['visit_lat'], $visitData['visit_lon']);
    }
    elseif (strpos($action, 'Branch') !== false ) {
        $branch_id = (int) explode('Branch', $action)[1];

        $branch = $ezzeTeamsModel->getBranchById($branch_id);
        prepareLocationMessage([$userId], $branch['branch_lat'], $branch['branch_lon']);
    }
    elseif (strpos($action, 'Clock') !== false ) {
        $clock_id = (int) explode('Clock', $action)[1];

        $clockData = $ezzeTeamsModel->getClockDataById($clock_id);
        prepareLocationMessage([$userId], $clockData['clock_in_lat'], $clockData['clock_in_lon']);
    }
    elseif (strpos($action, 'Ping') !== false ) {
        $ping_id = (int) explode('Ping', $action)[1];

        $pingData = $ezzeTeamsModel->getPingDataById($ping_id);
        $location = explode(',', $pingData['reply_location']);
        prepareLocationMessage([$userId], $location[0], $location[1]);
    }
    else {
        prepareMessage(null, _l('admin_command_not_exists'));
    }
}

else if (isset($adminStep['step']) && ($adminStep['step'] == 'user_approve_jobdesc' || $adminStep['step'] == 'user_approve_notes')) {
    if ($adminStep['step'] == 'user_approve_jobdesc') {
        $jobdesc = ucwords($result->message->text);
        $params = json_decode($adminStep['temp']);
        $ezzeTeamsModel->setJobDescEmployee($params->employee_id, $jobdesc);
        $ezzeTeamsModel->setAdminStep($userId, 'user_approve_notes', json_encode($params));
        prepareMessage(array(array(_l('button_skip'))), _l('admin_employee_note', [$params->employee_name]));
    }
    else if ($adminStep['step'] == 'user_approve_notes') {
        $notes = (strtolower($result->message->text) == strtolower(_l('button_skip')))? '' : ucwords($result->message->text);
        $params = json_decode($adminStep['temp']);
        $ezzeTeamsModel->setNotesEmployee($params->employee_id, $notes);
        $ezzeTeamsModel->setAdminStep($userId, '');

        $keyboard_config['resize'] = true;
        $keyboard_config['one_time'] = true;
        $keyboard_config['force_reply'] = true;
        prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), _l('admin_information_saved'));

        if ($botSettings['module_alert'] == 1) {
            $inline = generateImportantUserBtn($params->employee_id, 'user');
            prepareMessage(null, '• ' . _l('admin_registration_trigger_alarm') . ' <strong>' . $params->employee_name . "</strong>?", null, null, array($userId), null, true, $inline);
        } else if ($botSettings['module_break'] == 1) {
            $inline = generateBreakModuleUserBtn($params->employee_id, 'user');
            prepareMessage(null, '• ' . _l('admin_registration_trigger_break') . ' <strong>' . $params->employee_name . "</strong>?", null, null, array($userId), null, true, $inline);
        } else if ($botSettings['module_visit'] == 1) {
            $inline = generateVisitModuleUserBtn($params->employee_id, 'user');
            prepareMessage(null, '• ' . _l('admin_registration_trigger_visit') . ' <strong>' . $params->employee_name . "</strong>?", null, null, array($userId), null, true, $inline);
        } else if ($botSettings['dead_man_feature'] == 1) {
            $inline = generatePingModuleUserBtn($params->employee_id, 'user');
            prepareMessage(null, '• ' . _l('admin_registration_ping_module') . ' <strong>' . $params->employee_name . "</strong>?", null, null, array($userId), null, true, $inline);
        } else {
            $inline_key_work_day = generateWorkingDaysBtnNew($params->employee_id, 1, 8, 0, 17, 0);
            $msg = 'Set <strong>'.$params->employee_name.'</strong> Working Time For <strong>'.strtoupper(intToDay(1)).'</strong>';
            prepareMessage(null, $msg, null, null, array($userId), null, true, $inline_key_work_day,null, true);
        }
    }
}

/** Admin functions config bot **/
else if (strtolower($action) == 'help' || (strtolower($action) == '/help')) {
    $msg = "<b>List of BOT Command</b>_".
        "_<b>BOT Configurations</b>" .
        "<code>" .
        "_/config time-tolerance minutes(0-45)" .
        "_/config distance-tolerance meters(0-500)" .
        "_/config welcome-messages [welcome message text]" .
        "_/config company email [company email]" .
        "_/config company phone [company phone]" .
        "</code>" .

        "__<b>Manage Branches</b>" .
        "<code>" .
        "_/branch list" .
        "_/branch add [Branch Name]" .
        "_/branch edit [Branch Name]" .
        "_/branch remove" .
        "</code>" .

        "__<b>Messages</b>" .
        "<code>" .
        "_/msg list" .
        "_/msg add" .
        "_/msg mass" .
        "</code>" .

        "__<b>Generate Report</b>" .
        "<code>" .
        "_/report standard attendance [branch name] [range]" .
        "_/report standard break [branch name] [range]" .
        "_/report standard visit [branch name] [range]" .
        "_/report event [branch name] [range]" .
        "_/report help" .
        "</code>" .
        "_E.G" .
        "<code>" .
        "_/report standard attendance all today" .
        "_/report standard attendance Branch A today" .
        "_/report standard attendance Branch A yesterday" .
        "_/report standard attendance Branch A last week" .
        "_/report standard attendance Branch A last month" .
        "_/report standard attendance Branch A last year" .
        "_/report standard attendance Branch A this month" .
        "_/report standard attendance Branch A this year" .
        "_/report standard attendance Branch A 28/08/2022" .
        "_/report standard attendance Branch A 28/08/2022-31/08/2022" .
        "_/report daily 28/08/2022-31/08/2022" .
        "</code>" .

        "__<b>Employee</b>" .
        "<code>" .
        "_/showclockedin" .
        "_/showemployeebreak" .
        "</code>"
    ;
    prepareMessage(null, $msg);
    return;
}

else if (strpos(strtolower($action), '/config') !== false && in_array($userId, $admin_id)) {
    $c = explode(' ', strtolower($action));
    $defCommand = "_"._l('admin_accepted_cmd').": " .
        "<code>" .
        "_/config show" .
        "_/config time-tolerance minutes(0-45)" .
        "_/config distance-tolerance meters(0-500)" .
        "_/config welcome-messages" .
        "_/config schedule daily-report enable/disable" .
        "</code>";
    if (strtolower($c[1]) == 'show') {
        $alertModule = ($botSettings['module_alert'])?_l('enabled'):_l('disabled');
        $breakModule = ($botSettings['module_break'])?_l('enabled'):_l('disabled');
        $visitModule = ($botSettings['module_visit'])?_l('enabled'):_l('disabled');
        $pingModule = ($botSettings['dead_man_feature'])?_l('enabled'):_l('disabled');
        $msg = _l('admin_bot_info_modules').
                "_". _l('admin_bot_module_alert'). $alertModule .
                "_". _l('admin_bot_module_break'). $breakModule .
                "_". _l('admin_bot_module_visit'). $visitModule .
                "_". _l('admin_bot_module_ping'). $pingModule .
                "__". _l ('admin_bot_settings') .
                "_". _l ('admin_bot_time_tolerance') . $botSettings['time_tolerance']." min" .
                "_". _l('admin_bot_distance_tolerance') . ($botSettings['location_tolerance'] * 1000)." m";

        if ($botSettings['dead_man_feature']) {
            $msg .= "_". _l('admin_bot_ping_tolerance') . $botSettings['dead_man_task_time']." min";
        }
        $msg .= "_Company Email: " . $botSettings['company_email'];
        $msg .= "_Company Email: " . $botSettings['company_phone'];
        /*$msg .= "_Company Phone: ";
        $msg .= (substr($botSettings['company_phone'], 0, 1)=='+')? substr($botSettings['company_phone'], 1) : $botSettings['company_phone'];
        */
    }
    else if (strtolower($c[1]) == 'time-tolerance') {
        if (isset($c[2])) {
            $time = (int) $c[2];
            if ($time >= 1 && $time <= 45) {
                $ezzeTeamsModel->changeBotSetting('time_tolerance', $time);
                $msg = _l('admin_clocktime_success', [$time]);
            } else {
                $msg = _l('admin_clocktime_range_error') . $defCommand;
            }
        } else {
            $time = 10;
            $ezzeTeamsModel->changeBotSetting('time_tolerance', $time);
            $msg = _l('admin_clocktime_success', [$time]);
            //$msg = _l('admin_clocktime_empty')." _" . $defCommand;
        }
    }
    else if (strtolower($c[1]) == 'distance-tolerance') {
        if (isset($c[2])) {
            $distance = (int) $c[2];
            if ($distance >= 1 && $distance <= 500) {
                $v = $distance / 1000;
                $ezzeTeamsModel->changeBotSetting('location_tolerance', $v);
                $msg = _l('admin_clockdistance_success', [$distance]);
            } else {
                $msg = _l('admin_clockdistance_range_error') . $defCommand;
            }
        } else {
            $distance = 25;
            $v = $distance / 1000;
            $ezzeTeamsModel->changeBotSetting('location_tolerance', $v);
            $msg = _l('admin_clockdistance_success', [$distance]);
            //$msg = _l('admin_clockdistance_empty')." _" . $defCommand;
        }
    }
    else if (strtolower($c[1]) == 'schedule') {
        if (strtolower($c[2]) == 'report-absent') {
            if (strtolower($c[3]) == 'true') {
                $x = explode(':', $c[4]);
                if (count($x) == 2) {
                    if ( $x[0] >= 0 && $x[0] < 24 && $x[1] >= 0 && $x[1] < 60 ) {
                        $val = (int) 1;
                        $m = $ezzeTeamsModel->changeCronConfig(strtolower($c[2]), $val, $c[4]);
                        if ($m) {
                            $msg = _l('admin_schedule_absent_success', [$c[4]]);
                        } else {
                            $msg = _l('admin_schedule_absent_failed');
                        }
                    } else {
                        $msg = _l('admin_time_range_invalid');
                    }
                } else {
                    $msg = _l('admin_time_invalid');
                }
            } elseif (strtolower($c[3]) == 'false') {
                $val = (int) 0;
                $m = $ezzeTeamsModel->changeCronActive(strtolower($c[2]), $val);
                $msg = _l('admin_schedule_absent_off');
            } else {
                $msg = _l('admin_command_not_exists')." _" . $defCommand;
            }
        }
        elseif (strtolower($c[2]) == 'daily-report') {
            if (strtolower($c[3]) == 'enable') {
                $step = "set-schedule-daily-report-time";
                $params['enable'] = 1;
                $ezzeTeamsModel->setAdminStep($userId, $step, json_encode($params));
                $msg = _l('admin_schedule_daily_report_set_time');
            } elseif (strtolower($c[3]) == 'disable') {
                $val = (int) 0;
                $m = $ezzeTeamsModel->changeCronActive(strtolower($c[2]), $val);
                $msg = _l('admin_schedule_daily_report_off');
            } else {
                $msg = _l('admin_command_not_exists')." _" . $defCommand;
            }
        }
        else {
            $msg = _l('admin_command_not_exists')." _" . $defCommand;
        }
    }
    else if (strtolower($c[1]) == 'welcome-messages') {
        /*if (strtolower($c[2]) != '') {
            $w_cmd = "/config welcome-messages";
            $x = explode($w_cmd, $action);
            $step = "set-welcome-message";
            $params = [
                'messages' => trim($x[1])
            ];
            $ezzeTeamsModel->setAdminStep($userId, $step, json_encode($params));
            $msg = "Please Send a Logo/Images of your company";
        } else {
            $msg = _l('admin_command_not_exists')." _" . $defCommand;
        }*/
        $step = "set-welcome-message-text";
        $params['num'] = 0;
        $params['type'] = $botSettings['lang_active'][0];
        $ezzeTeamsModel->setAdminStep($userId, $step, json_encode($params));
        $len = count($botSettings['lang_active']);
        //$msg = "[".strtoupper($botSettings['lang_active'][0])."] Please Send a Logo/Images of your company";
        $msg = "[".strtoupper($botSettings['lang_active'][0])."] Please Set a Welcome Messages";
    }
    else if (strtolower($c[1]) == 'company') {
        if (strtolower($c[2]) == 'email') {
            if (strtolower($c[3]) != '') {
                if (!filter_var($c[3], FILTER_VALIDATE_EMAIL)) {
                    $msg = _l('admin_invalid_email');
                } else {
                    $email = strtolower($c[3]);
                    $ezzeTeamsModel->setBotSetting('company_email', $email);
                    $msg = _l('admin_company_email_success');
                }
            } else {
                $msg = _l('admin_please_type_email');
            }
        }
        else if (strtolower($c[2]) == 'phone') {
            if (strtolower($c[3]) != '') {
                if(!filter_var($c[3], FILTER_SANITIZE_NUMBER_INT)) {
                    $msg = _l('admin_invalid_phone');
                } else {
                    $phone = $c[3];
                    $ezzeTeamsModel->setBotSetting('company_phone', $phone);
                    $msg = _l('admin_company_phone_success');
                }
            } else {
                $msg = _l('admin_please_type_phone');
            }
        }
        else {
            $msg = _l('admin_command_not_exists')." _".$defCommand;
        }
    }
    else if (strtolower($c[1]) == 'module') {
        if (strtolower($c[2]) == 'alert') {
            if (strtolower($c[3]) == 'enable' || strtolower($c[3]) == 'disable') {
                if (strtolower($c[3]) == 'enable') {
                    $val = 1;
                    $msg = _l('admin_module_on', ['Module Alert']);
                } else {
                    $val = 0;
                    $msg = _l('admin_module_off', ['Module Alert']);
                };
                $ezzeTeamsModel->changeBotSetting('module_alert', $val);
            } else {
                $msg = _l('admin_module_miss_cmd');
            }
        }
        else if (strtolower($c[2]) == 'break') {
            if (strtolower($c[3]) == 'enable' || strtolower($c[3]) == 'disable') {
                if (strtolower($c[3]) == 'enable') {
                    $val = 1;
                    $msg = _l('admin_module_on', ['Module Break']);
                } else {
                    $val = 0;
                    $msg = _l('admin_module_off', ['Module Break']);
                };
                $ezzeTeamsModel->changeBotSetting('module_break', $val);
            } else {
                $msg = _l('admin_module_miss_cmd');
            }
        }
        else if (strtolower($c[2]) == 'visit') {
            if (strtolower($c[3]) == 'enable' || strtolower($c[3]) == 'disable') {
                if (strtolower($c[3]) == 'enable') {
                    $val = 1;
                    $msg = _l('admin_module_on', ['Module Visit']);
                } else {
                    $val = 0;
                    $msg = _l('admin_module_off', ['Module Visit']);
                };
                $ezzeTeamsModel->changeBotSetting('module_visit', $val);
            } else {
                $msg = _l('admin_module_miss_cmd');
            }
        }
        else if (strtolower($c[2]) == 'ping') {
            if (strtolower($c[3]) == 'enable') {
                $val = 1;
                if (isset($c[4])) {
                    $time = (int) $c[4];
                    if ($time >= 1 && $time <= 60 ){
                        $ezzeTeamsModel->setDeadManFeature($val, $c[4]);
                        $msg = _l('admin_deadman_success', [$c[4]]);
                    } else {
                        $msg = _l('admin_deadman_timerange_error') . $defCommand;
                    }
                } else {
                    $time = 30;
                        $ezzeTeamsModel->setDeadManFeature($val, $time);
                        $msg = _l('admin_deadman_success', [$time]);
                    //$msg = _l('admin_deadman_timerange_empty') . $defCommand;
                }
            } elseif (strtolower($c[3]) == 'disable') {
                $val = 0;
                $ezzeTeamsModel->setDeadManFeature($val);
                $msg = _l('admin_deadman_off_success');
            } else {
                $msg = _l('admin_command_not_exists')." _" . $defCommand;
            }
        }
        else {
            $msg = _l('admin_command_not_exists')." _".$defCommand;
        }
    }
    else if (strtolower($c[1]) == 'reminder') {
        if (strtolower($c[2]) == 'help') {
            $msg = _l('admin_accepted_cmd').": " .
                "<code>" .
                "_/config reminder interval minutes(30-60)" .
                "_/config reminder time-out minutes(0-29)" .
                "</code>";
        } else if (strtolower($c[2]) == 'interval') {
            if (isset($c[3])) {
                $time = (int) $c[3];
                if ($time >= 30 && $time <= 60) {
                    $ezzeTeamsModel->changeBotSetting('clockout_reminder_interval', $time);
                    $msg = _l('admin_reminder_interval_success', [$time]);
                } else {
                    $msg = _l('admin_reminder_interval_range_error');
                }
            } else {
                $time = 30;
                $ezzeTeamsModel->changeBotSetting('clockout_reminder_interval', $time);
                $msg = _l('admin_reminder_interval_success', [$time]);
            }
        }
        else if (strtolower($c[2]) == 'time-out') {
            if (isset($c[3])) {
                $time = (int) $c[3];
                if ($time >= 1 && $time <= 29) {
                    $ezzeTeamsModel->changeBotSetting('clockout_reminder_timeout', $time);
                    $msg = _l('admin_reminder_timeout_success', [$time]);
                } else {
                    $msg = _l('admin_reminder_timeout_range_error');
                }
            } else {
                $time = 10;
                $ezzeTeamsModel->changeBotSetting('clockout_reminder_timeout', $time);
                $msg = _l('admin_reminder_timeout_success', [$time]);
            }
        }
    }
    else {
        $msg = _l('admin_command_not_exists')." _".$defCommand;
    }
    prepareMessage(null, $msg);return;
}

else if (isset($result->message->photo) ||
    (isset($result->message->document->mime_type) && in_array($result->message->document->mime_type, array('image/png', 'image/jpeg'))) ||
    (isset($result->message->video) && in_array($result->message->video->mime_type, array('video/mp4'))) ) {
    if ($adminStep['step'] == 'set-welcome-message') {
        if (isset($result->message->photo)) {
            $imageId = (isset($result->message->photo[0]->file_id)) ? $result->message->photo[0]->file_id : $result->message->document->file_id;
            $params = json_decode($adminStep['temp'], true);
            $params['type'] = 'images';
            $m = saveImgToLocal($imageId);
            $ezzeTeamsModel->setWelcomeMessage(json_encode($params, JSON_UNESCAPED_UNICODE), $imageId);
            $ezzeTeamsModel->setAdminStep($userId, '');
            prepareMessage(null, _l('admin_welcome_message_saved'));
        }
        elseif (isset($result->message->video)){
            $imageId = $result->message->video->file_id;
            $params = json_decode($adminStep['temp'], true);
            $params['type'] = 'video';
            $ezzeTeamsModel->setWelcomeMessage(json_encode($params, JSON_UNESCAPED_UNICODE), $imageId);
            $ezzeTeamsModel->setAdminStep($userId, '');
            prepareMessage(null, _l('admin_welcome_message_saved'));
        }
    }
    else if ($adminStep['step'] == 'send_message_add_media'){
        $adminUser = $adminDetils[$userId];
        $temp = json_decode($adminUser['temp'], true);
        if (isset($result->message->document)) {
            $imageId = $result->message->document->file_id;;
            $type = 'document';
        } else {
            $imageId = $result->message->photo[0]->file_id;
            $type = 'photo';
        }

        $msg = "OK. Messages will be sent to all Bot User";
        prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), $msg);
        sendMessageToAllUser($temp['message'], $type, $imageId);
        $ezzeTeamsModel->setAdminStep($userId, '', '');
    }
    else if ($adminStep['step'] == 'scheduledMessage_addMedia') {
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], true);

        if (isset($result->message->document)) {
            $media = $result->message->document->file_id;;
            $media_type = 'document';
        } else {
            $media = $result->message->photo[0]->file_id;
            $media_type = 'photo';
        }
        $tempData['media_type'] = $media_type;
        $tempData['media'] = $media;

        $ezzeTeamsModel->setAdminStep($userId, 'scheduledMessage_setConfigurations', json_encode($tempData));
        $msg = "Media Accepted!";
        prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), $msg);
        $msg = "Scheduled Message Repeat Configurations";
        $btn = generateRuntimeSchedultMessageButton();
        prepareMessage(null, $msg, null, null, null, null, true, $btn);
    }
}

else if (isset($result->message->video)) {
    if ($adminStep['step'] == 'send_message_add_media') {
        $adminUser = $adminDetils[$userId];
        $temp = json_decode($adminUser['temp'], true);
        $videoId = $result->message->video->file_id;

        $msg = "OK. Messages will be sent to all Bot User";
        prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), $msg);
        sendMessageToAllUser($temp['message'], 'video', $videoId);
        $ezzeTeamsModel->setAdminStep($userId, '', '');
    }
    else if ($adminStep['step'] == 'scheduledMessage_addMedia') {
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], true);
        $tempData['media_type'] = 'video';
        $tempData['media'] = $result->message->video->file_id;

        $ezzeTeamsModel->setAdminStep($userId, 'scheduledMessage_setConfigurations', json_encode($tempData));
        $msg = "Media Accepted!";
        prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), $msg);
        $msg = "Scheduled Message Repeat Configurations";
        $btn = generateRuntimeSchedultMessageButton();
        prepareMessage(null, $msg, null, null, null, null, true, $btn);
    }
}

else if (strpos(strtolower($action), '/branch') !== false && in_array($userId, $admin_id)) {
    $c = explode(' ', strtolower($action));
    $defCommand = "_"._l('admin_accepted_cmd').": " .
        "<code>" .
        "_/branch list" .
        "_/branch add [Branch Name]" .
        "_/branch edit [Branch Name]" .
        "_/branch remove" .
        "</code>";
    if ($c[1] == 'add') {
        $cmd = "/branch add";
        $x = explode($cmd, $action);
        if ( $x[1] != '' ) {
            $branchName = trim($x[1]);
            $branchExists = $ezzeTeamsModel->getBranchByName($branchName);
            if ( count($branchExists) > 0 ) {
                $msg = _l('admin_branch_already_exists');
            } else {
                $branch_id = $ezzeTeamsModel->addBranchName($branchName);
                $params = ['pos' => 'add_new_branch', 'id' => $branch_id, 'table'=>'branch'];
                $ezzeTeamsModel->setAdminStep($userId, 'admin_branch_add_location', json_encode($params));
                $msg = _l('admin_branch_add_location', [ucfirst($branchName)]);
            }
        } else {
            $msg = _l('admin_branch_missing_name');
        }
    } else if ($c[1] == 'edit') {
        $cmd = "/branch edit";
        $x = explode($cmd, $action);
        if ($x != '') {
            $branchName = trim($x[1]);
            $branchExists = $ezzeTeamsModel->getBranchByName($branchName);
            if ( count($branchExists) > 0 ) {
                //$msg = json_encode($x);
                $params = ['pos' => 'edit_branch', 'id' => $branchExists['branch_id'], 'table'=>'branch'];
                $ezzeTeamsModel->setAdminStep($userId, 'admin_branch_edit_name', json_encode($params));
                $msg = _l('admin_branch_set_new_name', [ucfirst($branchExists['branch_name'])]);
            } else {
                $msg = _l('admin_branch_not_exists');
            }
        } else {
            $msg = _l('admin_branch_missing_name');
        }
    } else if ($c[1] == 'remove') {
        $cmd = "/branch remove";
        $x = explode($cmd, $action);
        if ( $x[1] != '' ) {
            $branchName = trim($x[1]);
            $branchExists = $ezzeTeamsModel->getBranchByName($branchName);
            if ( count($branchExists) > 0 ) {
                $params = ['pos' => 'remove_branch', 'id' => $branchExists['branch_id'], 'table'=>'branch'];
                $ezzeTeamsModel->setAdminStep($userId, 'admin_branch_remove_confirmation', json_encode($params));
                $msg = _l('admin_branch_remove_confirmation', [ucwords($branchExists['branch_name'])]);
                prepareMessage(array(array(_l('button_yes')), array(_l('button_no'))), $msg);
                return;
            } else {
                $msg = _l('admin_branch_not_exists');
            }
        } else {
            $msg = _l('admin_branch_missing_name');
        }
    } else if ($c[1] == 'list') {
        $cmd = "/branch list";
        $x = explode($cmd, $action);
        $branchLists = $ezzeTeamsModel->getAllBranch();
        $msg = "<strong><u>List Work Locations</u></strong>_";
        $i = 1;
        foreach ($branchLists as $b) {
            $msg .= "_".$i.". <strong>".$b['branch_name']."</strong>" .
                    "_". _l('admin_info_latitude') . $b['branch_lat'] .
                    "_". _l('admin_info_longitude') . $b['branch_lon'] .
                    "_". _l('admin_info_map'). "/showMapBranch".$b['branch_id']."_";
            $i++;
        }
        prepareMessage(null, $msg);
        return;
    } else {
        $msg = _l('admin_command_not_exists')."_".$defCommand;
    }
    prepareMessage(null, $msg);
}

else if ( isset($result->message->location->latitude) && isset($result->message->location->longitude) ) {
    $adminUser = $adminDetils[$userId];
    $arr_step = ['admin_branch_add_location'];
    if ( in_array($adminUser['step'], $arr_step) ) {
        if ($adminUser['step'] == 'admin_branch_add_location') {
            $pos = json_decode($adminUser['temp'], true);
            $ezzeTeamsModel->setBranchLatLong($pos['id'], $result->message->location->latitude, $result->message->location->longitude);
            $ezzeTeamsModel->setAdminStep($userId, '');
            $msg = _l('admin_branch_add_success');
        }
    } else if ($adminUser['step'] == 'admin_branch_edit_location') {
        $pos = json_decode($adminUser['temp'], true);
        $pos['lat'] = $result->message->location->latitude;
        $pos['long'] = $result->message->location->longitude;

        $ezzeTeamsModel->setAdminStep($userId, 'admin_branch_edit_confirmation', json_encode($pos));
        $msg = _l('admin_info_is_correct')." __".ucwords($pos['new_branch_name']);
        prepareMessage(array(array(_l('button_yes')), array(_l('button_cancel'))), $msg);
        $params = [
            'chat_id' => $userId,
            'latitude' => $result->message->location->latitude,
            'longitude' => $result->message->location->longitude
        ];
        sendMessage($params, 'sendLocation');return;
    } else {
        $msg = _l('admin_command_not_exists');
    }
    prepareMessage(null, $msg);
    return;
}

/** Generate CLock report */
else if (strpos(strtolower($action), '/report') !== false) {
    $c = explode(' ', strtolower($action));
    $inline_keyboard = null;
    $defCommand = "_"._l('admin_generate_report_cmd').": " .
            "_<code>/report standard attendance [branch name] [range]" .
            "_/report standard break [branch name] [range]" .
            "_/report standard visit [branch name] [range]" .
            "_/report event [branch name] [range]" .
            "_/report daily [range]" .
            "__E.G." .
            "_/report standard attendance all today" .
            "_/report standard attendance Branch A today" .
            "_/report standard attendance Branch A yesterday" .
            "_/report standard attendance Branch A last week" .
            "_/report standard attendance Branch A last month" .
            "_/report standard attendance Branch A last year" .
            "_/report standard attendance Branch A this month" .
            "_/report standard attendance Branch A this year" .
            "_/report standard attendance Branch A 28/08/2022" .
            "_/report standard attendance Branch A 28/08/2022-31/08/2022" .
            "_/report daily 28/08/2022-31/08/2022</code>";
    if (strtolower($c[1]) == 'standard') {
        if (strtolower($c[2]) == 'attendance') {
            $cm = explode("/report standard attendance", strtolower($action));
            if (isset($cm[1]) && $cm[1] != '') {
                $result = parseDateCommand($cm, $c);
                $dateStart = $result['date_start'];
                $dateEnd = $result['date_end'];
                $b = $result['b'];
                $branchName = $result['branch_name'];
                $textMsg = $result['text_msg'];
                $dateOk = $result['dateOk'];

                if ($dateOk == false) {
                    $msg = _l('admin_report_invalid_date') . " _" . $defCommand;
                } elseif ($branchName == '') {
                    $msg = _l('admin_report_missing_branch') . " _" . $defCommand;
                } else {
                    $branch = $ezzeTeamsModel->getBranchByName($branchName);
                    if ($branchName != 'all' && count($branch) < 1) {
                        $msg = _l('admin_report_missing_branch');
                        $msg .= "__" . $defCommand;
                    } else {
                        //validate date
                        $dtS = explode("-", $dateStart);
                        $dtE = explode("-", $dateEnd);
                        $dtSStr = strtotime($dateStart);
                        $dtEStr = strtotime($dateEnd);
                        $dtTStr = strtotime("now");
                        if (count($dtS) < 3 && count($dtE) < 3 && !checkdate($dtS[1], $dtS[2], $dtS[0]) && !checkdate($dtE[1], $dtE[2], $dtE[0])) {
                            $msg = _l('admin_report_invalid_date');
                        } elseif ($dtSStr > $dtEStr) {
                            $msg = _l('admin_report_date_validation');
                        } elseif ($dtSStr > $dtTStr || $dtEStr > $dtTStr) {
                            $msg = _l('admin_report_date_max_today');
                        } else {
                            if ($branchName == 'all') {
                                $branches = $ezzeTeamsModel->getAllBranch();
                                foreach ($branches as $bbranch) {
                                    $msg .= "_" . $bbranch['branch_name'];
                                    $cs = getReportByDate($bbranch['branch_id'], $bbranch['branch_name'], $dateStart, $dateEnd);
                                    $msg = _l('admin_report_attendance') . " " . ucwords($bbranch['branch_name']) . " " . $textMsg;
                                    prepareMessage(null, $msg, null, 'sendDocument', null, null, false, null, null, true, $cs);
                                    unlink($cs);
                                }
                                exit;
                            } else {
                                $cs = getReportByDate($branch['branch_id'], $branchName, $dateStart, $dateEnd);
                                $msg = _l('admin_report_attendance') . " " . ucwords($branchName) . " " . $textMsg;
                                $inline_keyboard = [
                                    [
                                        [
                                            "text" => "Download CSV File",
                                            "url" => BASE_URL . $cs
                                        ]
                                    ]
                                ];
                            }
                        }
                    }
                }
            } else {
                $msg = _l('admin_command_not_exists') . " " . $defCommand;
            }
        }
        elseif (strtolower($c[2]) == 'break') {
            $cm = explode("/report standard break", strtolower($action));
            if (isset($cm[1]) && $cm[1] != '') {
                $result = parseDateCommand($cm, $c);
                $dateStart = $result['date_start'];
                $dateEnd = $result['date_end'];
                $b = $result['b'];
                $branchName = $result['branch_name'];
                $textMsg = $result['text_msg'];
                $dateOk = $result['dateOk'];

                if ($dateOk == false) {
                    $msg = _l('admin_report_invalid_date') . " " . $defCommand;
                } elseif ($branchName == '') {
                    $msg = _l('admin_report_missing_branch') . " " . $defCommand;
                } else {
                    $branch = $ezzeTeamsModel->getBranchByName($branchName);
                    if ($branchName != 'all' && count($branch) < 1) {
                        $msg = _l('admin_report_missing_branch');
                        $msg .= "__" . $defCommand;
                    } else {
                        //validate date
                        $dtS = explode("-", $dateStart);
                        $dtE = explode("-", $dateEnd);
                        $dtSStr = strtotime($dateStart);
                        $dtEStr = strtotime($dateEnd);
                        $dtTStr = strtotime("now");
                        if (count($dtS) < 3 && count($dtE) < 3 && !checkdate($dtS[1], $dtS[2], $dtS[0]) && !checkdate($dtE[1], $dtE[2], $dtE[0])) {
                            $msg = _l('admin_report_invalid_date');
                        } elseif ($dtSStr > $dtEStr) {
                            $msg = _l('admin_report_date_validation');
                        } elseif ($dtSStr > $dtTStr || $dtEStr > $dtTStr) {
                            $msg = _l('admin_report_date_max_today');
                        } else {
                            if ($branchName == 'all') {
                                $branches = $ezzeTeamsModel->getAllBranch();
                                foreach ($branches as $bbranch) {
                                    $msg .= "_" . $bbranch['branch_name'];
                                    $cs = getReportBreakByDate($bbranch['branch_id'], $bbranch['branch_name'], $dateStart, $dateEnd);
                                    $msg = _l('admin_report_break') . " " . ucwords($bbranch['branch_name']) . " " . $textMsg;
                                    prepareMessage(null, $msg, null, 'sendDocument', null, null, false, null, null, true, $cs);
                                    unlink($cs);
                                }
                                exit;
                            } else {
                                $cs = getReportBreakByDate($branch['branch_id'], $branchName, $dateStart, $dateEnd);
                                $msg = _l('admin_report_break') . " " . ucwords($branchName) . " " . $textMsg;
                                $inline_keyboard = [
                                    [
                                        [
                                            "text" => "Download CSV File",
                                            "url" => BASE_URL . $cs
                                        ]
                                    ]
                                ];
                            }
                        }
                    }
                }
            } else {
                $msg = _l('admin_command_not_exists') . " " . $defCommand;
            }
        }
        elseif (strtolower($c[2]) == 'visit') {
            $cm = explode("/report standard visit", strtolower($action));
            if (isset($cm[1]) && $cm[1] != '') {
                $result = parseDateCommand($cm, $c);
                $dateStart = $result['date_start'];
                $dateEnd = $result['date_end'];
                $b = $result['b'];
                $branchName = $result['branch_name'];
                $textMsg = $result['text_msg'];
                $dateOk = $result['dateOk'];

                if ($dateOk == false) {
                    $msg = _l('admin_report_invalid_date') . " " . $defCommand;
                } elseif ($branchName == '') {
                    $msg = _l('admin_report_missing_branch') . " " . $defCommand;
                } else {
                    $branch = $ezzeTeamsModel->getBranchByName($branchName);
                    if ($branchName != 'all' && count($branch) < 1) {
                        $msg = _l('admin_report_missing_branch');
                        $msg .= "__" . $defCommand;
                    } else {
                        //validate date
                        $dtS = explode("-", $dateStart);
                        $dtE = explode("-", $dateEnd);
                        $dtSStr = strtotime($dateStart);
                        $dtEStr = strtotime($dateEnd);
                        $dtTStr = strtotime("now");
                        if (count($dtS) < 3 && count($dtE) < 3 && !checkdate($dtS[1], $dtS[2], $dtS[0]) && !checkdate($dtE[1], $dtE[2], $dtE[0])) {
                            $msg = _l('admin_report_invalid_date');
                        } elseif ($dtSStr > $dtEStr) {
                            $msg = _l('admin_report_date_validation');
                        } elseif ($dtSStr > $dtTStr || $dtEStr > $dtTStr) {
                            $msg = _l('admin_report_date_max_today');
                        } else {
                            if ($branchName == 'all') {
                                $branches = $ezzeTeamsModel->getAllBranch();
                                foreach ($branches as $bbranch) {
                                    $msg .= "_" . $bbranch['branch_name'];
                                    $cs = getReportVisitByDate($bbranch['branch_id'], $bbranch['branch_name'], $dateStart, $dateEnd);
                                    $msg = _l('admin_report_break') . " " . ucwords($bbranch['branch_name']) . " " . $textMsg;
                                    prepareMessage(null, $msg, null, 'sendDocument', null, null, false, null, null, true, $cs);
                                    unlink($cs);
                                }
                                exit;
                            } else {
                                $cs = getReportVisitByDate($branch['branch_id'], $branchName, $dateStart, $dateEnd);
                                $msg = _l('admin_report_break') . " " . ucwords($branchName) . " " . $textMsg;
                                $inline_keyboard = [
                                    [
                                        [
                                            "text" => "Download CSV File",
                                            "url" => BASE_URL . $cs
                                        ]
                                    ]
                                ];
                            }
                        }
                    }
                }
            } else {
                $msg = _l('admin_command_not_exists') . " " . $defCommand;
            }
        }
        else {
            $msg = _l('admin_command_not_exists') . " " . $defCommand;
        }
    }
    else if (strtolower($c[1]) == 'event') {
        $cm = explode("/report event", strtolower($action));
        if (isset($cm[1]) && $cm[1] != '') {
            $result = parseDateCommand($cm, $c);
            $dateStart = $result['date_start'];
            $dateEnd = $result['date_end'];
            $b = $result['b'];
            $branchName = $result['branch_name'];
            $textMsg = $result['text_msg'];
            $dateOk = $result['dateOk'];

            if ($dateOk == false) {
                $msg = _l('admin_report_invalid_date') . " " . $defCommand;
            } elseif ($branchName == '') {
                $msg = _l('admin_report_missing_branch') . " " . $defCommand;
            } else {
                $branch = $ezzeTeamsModel->getBranchByName($branchName);
                if ($branchName != 'all' && count($branch) < 1) {
                    $msg = _l('admin_report_missing_branch');
                    $msg .= "__" . $defCommand;
                } else {
                    //validate date
                    $dtS = explode("-", $dateStart);
                    $dtE = explode("-", $dateEnd);
                    $dtSStr = strtotime($dateStart);
                    $dtEStr = strtotime($dateEnd);
                    $dtTStr = strtotime("now");
                    if (count($dtS) < 3 && count($dtE) < 3 && !checkdate($dtS[1], $dtS[2], $dtS[0]) && !checkdate($dtE[1], $dtE[2], $dtE[0])) {
                        $msg = _l('admin_report_invalid_date');
                    } elseif ($dtSStr > $dtEStr) {
                        $msg = _l('admin_report_date_validation');
                    } elseif ($dtSStr > $dtTStr || $dtEStr > $dtTStr) {
                        $msg = _l('admin_report_date_max_today');
                    } else {
                        if ($branchName == 'all') {
                            $branches = $ezzeTeamsModel->getAllBranch();
                            foreach ($branches as $bbranch) {
                                $msg .= "_" . $bbranch['branch_name'];
                                $cs = getEventReportByDate($bbranch['branch_id'], $bbranch['branch_name'], $dateStart, $dateEnd);
                                $msg = _l('admin_report_event') . " " . ucwords($bbranch['branch_name']) . " " . $textMsg;
                                prepareMessage(null, $msg, null, 'sendDocument', null, null, false, null, null, true, $cs);
                                unlink($cs);
                            }
                            exit;
                        } else {
                            $cs = getEventReportByDate($branch['branch_id'], $branchName, $dateStart, $dateEnd);
                            $msg = _l('admin_report_event') . " " . ucwords($branchName) . " " . $textMsg;
                            $inline_keyboard = [
                                [
                                    [
                                        "text" => "Download CSV File",
                                        "url" => BASE_URL . $cs
                                    ]
                                ]
                            ];
                        }
                    }
                }
            }
        }
    }
    else if (strtolower($c[1]) == 'daily') {
        $cm = explode("/report daily", strtolower($action));
        $m = "/report daily all ".$cm[1];
        $cm = explode("/report daily", strtolower($m));
        if (isset($cm[1]) && $cm[1] != '') {
            $result = parseDateCommand($cm, $c);
            $dateStart = $result['date_start'];
            $dateEnd = $result['date_end'];
            $b = $result['b'];
            $branchName = $result['branch_name'];
            $textMsg = $result['text_msg'];
            $dateOk = $result['dateOk'];

            if ($dateOk == false) {
                $msg = _l('admin_report_invalid_date') . " " . $defCommand;
            } elseif ($branchName == '') {
                $msg = _l('admin_report_missing_branch') . " " . $defCommand;
            } else {
                $branch = $ezzeTeamsModel->getBranchByName($branchName);
                if ($branchName != 'all' && count($branch) < 1) {
                    $msg = _l('admin_report_missing_branch');
                    $msg .= "__" . $defCommand;
                } else {
                    //validate date
                    $dtS = explode("-", $dateStart);
                    $dtE = explode("-", $dateEnd);
                    $dtSStr = strtotime($dateStart);
                    $dtEStr = strtotime($dateEnd);
                    $dtTStr = strtotime("now");
                    if (count($dtS) < 3 && count($dtE) < 3 && !checkdate($dtS[1], $dtS[2], $dtS[0]) && !checkdate($dtE[1], $dtE[2], $dtE[0])) {
                        $msg = _l('admin_report_invalid_date');
                    } elseif ($dtSStr > $dtEStr) {
                        $msg = _l('admin_report_date_validation');
                    } elseif ($dtSStr > $dtTStr || $dtEStr > $dtTStr) {
                        $msg = _l('admin_report_date_max_today');
                    } else {
                        $range = false;
                        if ($dateStart != $dateEnd){
                            $range = true;
                        }

                        $dateStartWithTime = $dateStart." 00:00";
                        $dateEndWithTime = $dateEnd." 23:59";
                        if($range){
                            $dataClock = getReportClockTimeByDateRange($dateStartWithTime, $dateEndWithTime);
                        } else {
                            $dataClock = getReportClockTimeByDate2($dateStartWithTime, $dateEndWithTime);
                        }
                        $dataBreak = getReportBreakByDate2($dateStartWithTime, $dateEndWithTime);
                        $dataVisit = getReportVisitByDate2($dateStartWithTime, $dateEndWithTime);
                        $dataPING = getReportPINGByDate2($dateStartWithTime, $dateEndWithTime);
                        $dataBreadcrumbs = getReportBreadcrumbs($dateStartWithTime, $dateEndWithTime);
                        $cs = generateDailyReport($dataClock, $dataBreak, $dataVisit, $dataPING, $dataBreadcrumbs);

                        $msg = "Daily Report For " . $textMsg;
                        $inline_keyboard = [
                            [
                                [
                                    "text" => "Download File",
                                    "url" => BASE_URL . $cs
                                ]
                            ]
                        ];
                    }
                }
            }
        }
    }
    elseif (strtolower($c[1]) == 'help') {
        $msg = $defCommand;
    }
    else {
        $msg = _l('admin_command_not_exists') . " " . $defCommand;
    }

    if ($inline_keyboard == null){
        prepareMessage(null, $msg);
    } else {
        prepareMessage(null, $msg, null, 'sendDocument', null, null, false, null, null, true, $cs);
        unlink($cs);
    }
    return;
}

else if (strtolower($action) == 'show clocked in' || (strtolower($action) == '/showclockedin')) {
    $m = showClockedInToday();
    prepareMessage(null, $m);return;
}

else if (strtolower($action) == 'show employee break' || (strtolower($action) == '/showemployeebreak')) {
    $m = showOnBreakToday();
    prepareMessage(null, $m);
    return;
}

/** Display User Registrations Lists */
else if (($action == '/showpendingapproval' || strtolower($action) == 'show pending approval')) {
    generateListEmployee();
}

/** Display Approved Employee **/
else if ( (strtolower($action) == '/showemployee') || strtolower($action) == 'show all active' ) {
    generateApprovedEmployee();
}

/** View detail Approved Employee */
else if (strpos($action, '/viewEmployee') !== false ) {
    $profile_id = explode('Employee', $action);
    $profile_id = (int) $profile_id[1];

    $user_data = $ezzeTeamsModel->getDetilUserById($profile_id);
    if (!isset($user_data) || $user_data['approval_status'] == 'rejected') {
        prepareMessage(null, _l('admin_user_not_found'));
        return;
    }
    $user_data['email'] = ($user_data['email'] == 'Skip and Send') ? 'N/A' : $user_data['email'];
    $user_data['lastname'] = !isset($user_data['lastname']) || $user_data['lastname'] == '' ? 'N/A' : $user_data['lastname'];
    $user_data['branch_name'] = !isset($user_data['branch_name']) || $user_data['branch_name'] == '' ? 'N/A' : $user_data['branch_name'];
    $msg = detailEmployee($user_data);

    $msg .= generateWorkingSchedule($user_data['user_id']);

    $inline_keyboard_config_asking_approval = generateDetilEmployeeBtn($user_data);
    if (!isset($user_data['photo_id']) || $user_data['photo_id'] == ''){
        prepareMessage(null, $msg, null, null, null, null, true, $inline_keyboard_config_asking_approval, null, true);
    } else {
        prepareMessage(null, $msg, $user_data['photo_id'], 'sendPhoto', null, null, true, $inline_keyboard_config_asking_approval, null, true);
    }
    return;
}

/** View User Profile */
else if (strpos($action, '/viewProfile') !== false ) {

    $profile_id = explode('Profile', $action);
    $profile_id = (int) $profile_id[1];

    $user_data = $ezzeTeamsModel->getUserByID($profile_id);
    if (!isset($user_data) || in_array($profile_id, $admin_id)) {
        prepareMessage(null, _l('admin_user_not_found'));
        return;
    }
    $user_data['email'] = ($user_data['email'] == 'Skip and Send') ? 'N/A' : $user_data['email'];
    $user_data['lastname'] = !isset($user_data['lastname']) || $user_data['lastname'] == '' ? 'N/A' : $user_data['lastname'];
    $user_data['branch_name'] = !isset($user_data['branch_name']) || $user_data['branch_name'] == '' ? 'N/A' : $user_data['branch_name'];
    $msg = generateReceivedNewApplicationMSG($user_data);

    $inline_keyboard_config_asking_approval = generateReceivedNewApplicationBtn($user_data);
    prepareMessage(null, $msg, $user_data['photo_id'], 'sendPhoto', null, null, true, $inline_keyboard_config_asking_approval);
    if (!isset($user_data['photo_id']) || $user_data['photo_id'] == ''){
        prepareMessage(null, $msg, null, null, null, null, true, $inline_keyboard_config_asking_approval);
    }

}

/** Scheduled Messages */
else if (strpos($action, '/msg') !== false ) {
    $c = explode(' ', strtolower($action));
    $defCommand = "_"._l('admin_accepted_cmd').": " .
        "<code>" .
        "_/msg list" .
        "_/msg add" .
        "_/msg mass" .
        "</code>";
    if ($c[1] == 'add') {
        $tempData = ['type' => 'addnew'];
        $ezzeTeamsModel->setAdminStep($userId, 'scheduledMessage_setTitle', json_encode($tempData));
        $msg = "Please add a title for Scheduled Message";
        prepareMessage(null, $msg);
    }
    else if ($c[1] == 'list') {
        $msg = getAllScheduledMessageLists();
    } else if ($c[1] == 'mass') {
        $ezzeTeamsModel->setAdminStep($userId, 'send_message_all', '');
        $msg = "Please type a text messages";
        prepareMessage(null, $msg);
    } else {
        prepareMessage(null, $defCommand);
    }
}

else if (strpos($action, '/viewScheduleMessage') !== false ) {
    $cmd = explode('ScheduleMessage', $action);
    $messageId = (int) $cmd[1];
    $data = $ezzeTeamsModel->getScheduledMessageById($messageId);
    if (!$data) {
        prepareMessage(null, 'Messages Not Found!');
        exit;
    }
    if ($data['media_type'] == 'text') {
        $mediaType = false;
        $media_id = false;
    } else {
        $mediaType = $data['media_type'];
        $media_id = $data['media'];
    }

    $runtime = "";
    if ($data['runtime'] == 1) $runtime = "One Time"; elseif($data['runtime'] == 2) $runtime = "Repeat";

    $msg = "<strong>Scheduled Message Details:</strong>" .
        "__<strong>Title: </strong>" . $data['title'] .
        "_<strong>Recipient: </strong>" . (($data['destination'] == 'all')? 'All User' : $data['destination']) .
        "_<strong>Repeat: </strong>" . $runtime .
        "_<strong>Last Run: </strong>" . (($data['last_run'] == '0000-00-00 00:00:00')? '-' : date("d M Y H:i:s", strtotime($data['last_run']))) .
        "_<strong>Created By: </strong>" . $data['firstname'] . " " . $data['lastname'] .
        "_<strong>Created Date: </strong>" . date("d M Y H:i:s", strtotime($data['created_at'])) .
        "_<strong>Messages:_</strong>" . $data['message'];

    $msg .= "__<strong>Scheduled:</strong>";

    foreach($data['schedule'] as $schedule) {
        $msg .= "_<strong>".$schedule['day'].":</strong> " . $schedule['time'] .
            (($schedule['is_run'] == 1) ? " (Done!)" : "");
    }
    $btn = generateEditMessageBtn($messageId);
    sendMessageToAllUser($msg, $mediaType, $media_id, $userId, $btn);
}
/** User Working Configurations */
else if (isset($result->callback_query->data)) {

    global $ezzeTeamsModel;
    $data = explode('_', $result->callback_query->data);
    $limit = 10;

    if ($data[0] == 'approve') {
        $inline_key_branch = generateBranchBtn($data[1], $data[2]);

        $employee = $ezzeTeamsModel->getEmployeeByUserId($data[1]);
        $name = $employee['firstname']." ".$employee['lastname'];
        prepareMessage(null,  '• '._l('admin_registration_select_branch').' <strong>' . $name . "</strong>", null, null, array($result->callback_query->from->id), null, true, $inline_key_branch);
    }

    else if ($data[0] == 'assign-branch') {

        $ezzeTeamsModel->updateUserWhere($result->callback_query->from->id, 'day_selected_msg_id = null');
        $ezzeTeamsModel->updateUserBranch($data[1], $data[3], $data[4]);

        $employee = $ezzeTeamsModel->getEmployeeByUserId($data[1]);
        $name = $employee['firstname']." ".$employee['lastname'];

        $datas = ['employee_id' => $data[1], 'employee_name' => $name];
        $ezzeTeamsModel->setAdminStep($result->callback_query->from->id, 'user_approve_jobdesc', json_encode($datas));

        prepareMessage(null, _l('admin_enter_jobdesc', [$name]),null, null, array($result->callback_query->from->id));

    }

    else if ($data[0] == 'assign-important') {
        $ezzeTeamsModel->updateUserTriggerAlaram($data[1], $data[3]);

        $employee = $ezzeTeamsModel->getEmployeeByUserId($data[1]);
        $name = $employee['firstname']." ".$employee['lastname'];

        if ($botSettings['module_break'] == 1) {
            $inline = generateBreakModuleUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_trigger_break') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        } else if ($botSettings['module_visit'] == 1) {
            $inline = generateVisitModuleUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_trigger_visit') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        } else if ($botSettings['dead_man_feature'] == 1) {
            $inline = generatePingModuleUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_ping_module') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        } else {
            $inline_key_work_day = generateWorkingDaysBtnNew($data[1], 1, 8, 0, 17, 0);
            $msg = 'Set <strong>'.$name.'</strong> Working Time For <strong>'.strtoupper(intToDay(1)).'</strong>';
            prepareMessage(null, $msg, null, null, array($result->callback_query->from->id), null, true, $inline_key_work_day,null, true);
        }
    }

    else if ($data[0] == 'assign-break') {
        $ezzeTeamsModel->updateUserBreakTrigger($data[1], $data[3]);

        $employee = $ezzeTeamsModel->getEmployeeByUserId($data[1]);
        $name = $employee['firstname']." ".$employee['lastname'];

        if ($data[3] == 1) {
            $inline = generateBreakStepBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_break_step') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        }
        else if ($botSettings['module_visit'] == 1) {
            $inline = generateVisitModuleUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_trigger_visit') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        }
        else if ($botSettings['dead_man_feature'] == 1) {
            $inline = generatePingModuleUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_ping_module') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        }
        else {
            $inline_key_work_day = generateWorkingDaysBtnNew($data[1], 1, 8, 0, 17, 0);
            $msg = 'Set <strong>'.$name.'</strong> Working Time For <strong>'.strtoupper(intToDay(1)).'</strong>';
            prepareMessage(null, $msg, null, null, array($result->callback_query->from->id), null, true, $inline_key_work_day,null, true);
        }
    }

    else if ($data[0] == 'assign-breakstep') {
        $ezzeTeamsModel->updateUserBreakStep($data[1], $data[3]);

        $employee = $ezzeTeamsModel->getEmployeeByUserId($data[1]);
        $name = $employee['firstname']." ".$employee['lastname'];

        if ($botSettings['module_visit'] == 1) {
            $inline = generateVisitModuleUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_trigger_visit') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        }
        else if ($botSettings['dead_man_feature'] == 1) {
            $inline = generatePingModuleUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_ping_module') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        }
        else {
            $inline_key_work_day = generateWorkingDaysBtnNew($data[1], 1, 8, 0, 17, 0);
            $msg = 'Set <strong>'.$name.'</strong> Working Time For <strong>'.strtoupper(intToDay(1)).'</strong>';
            prepareMessage(null, $msg, null, null, array($result->callback_query->from->id), null, true, $inline_key_work_day,null, true);
        }
    }

    else if ($data[0] == 'assign-visit') {
        $ezzeTeamsModel->updateUserVisitTrigger($data[1], $data[3]);

        $employee = $ezzeTeamsModel->getEmployeeByUserId($data[1]);
        $name = $employee['firstname']." ".$employee['lastname'];

        if ($data[3] == 1) {
            $inline = generateVisitAlertUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_alert_visit') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        } else if ($botSettings['dead_man_feature'] == 1) {
            $inline = generatePingModuleUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_ping_module') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        } else {
            $inline_key_work_day = generateWorkingDaysBtnNew($data[1], 1, 8, 0, 17, 0);
            $msg = 'Set <strong>' . $name . '</strong> Working Time For <strong>' . strtoupper(intToDay(1)) . '</strong>';
            prepareMessage(null, $msg, null, null, array($result->callback_query->from->id), null, true, $inline_key_work_day, null, true);
        }
    }

    else if ($data[0] == 'alert-visit') {
        $ezzeTeamsModel->updateUserVisitAlert($data[1], $data[3]);

        $employee = $ezzeTeamsModel->getEmployeeByUserId($data[1]);
        $name = $employee['firstname']." ".$employee['lastname'];

        if ($botSettings['dead_man_feature'] == 1) {
            $inline = generatePingModuleUserBtn($data[1], $data[2]);
            prepareMessage(null, '• ' . _l('admin_registration_ping_module') . ' <strong>' . $name . "</strong>?", null, null, array($result->callback_query->from->id), null, true, $inline);
        } else {
            $inline_key_work_day = generateWorkingDaysBtnNew($data[1], 1, 8, 0, 17, 0);
            $msg = 'Set <strong>' . $name . '</strong> Working Time For <strong>' . strtoupper(intToDay(1)) . '</strong>';
            prepareMessage(null, $msg, null, null, array($result->callback_query->from->id), null, true, $inline_key_work_day, null, true);
        }
    }

    else if ($data[0] == 'assign-ping') {
        $ezzeTeamsModel->updateUserPingModule($data[1], $data[3]);

        $employee = $ezzeTeamsModel->getEmployeeByUserId($data[1]);
        $name = $employee['firstname']." ".$employee['lastname'];

        $inline_key_work_day = generateWorkingDaysBtnNew($data[1], 1, 8, 0, 17, 0);
        $msg = 'Set <strong>' . $name . '</strong> Working Time For <strong>' . strtoupper(intToDay(1)) . '</strong>';
        prepareMessage(null, $msg, null, null, array($result->callback_query->from->id), null, true, $inline_key_work_day, null, true);
    }

    /**
     * Setting Start Time
     * Decrease Hour
     */
    else if ($data[0] == 'prev-h-assign-work-start-time') {
        $user_data = $ezzeTeamsModel->getUserByID($result->callback_query->from->id);

        $user_id = $data[1];
        $day = $data[2];
        $hourStart = $data[3];
        $minStart = $data[4];
        $hourEnd = $data[5];
        $minEnd = $data[6];

        $hourStart = $hourStart - 1;
        if ($hourStart < 0) { $hourStart = 23; }
        $nextDay = false;
        $startTime = strtotime($hourStart.":".$minStart);
        $EndTime = strtotime($hourEnd.":".$minEnd);
        if ($EndTime < $startTime) { $nextDay = true; }

        $inline_key_work_time = generateWorkingDaysBtnNew($data[1], $day, $hourStart, $minStart, $hourEnd, $minEnd, $nextDay);
        $employee_data = $ezzeTeamsModel->getDetilUserById($user_id);
        $msg = 'Set <strong>'.$employee_data['firstname'].' '.$employee_data['lastname'].'</strong> Working Time For <strong>'.strtoupper(intToDay($day)).'</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key_work_time, $user_data['set_start_time_msg_id'], true);

    }

    /**
     * Setting Start Time
     * Increase Hour
     */
    else if ($data[0] == 'next-h-assign-work-start-time') {
        $user_data = $ezzeTeamsModel->getUserByID($result->callback_query->from->id);

        $user_id = $data[1];
        $day = $data[2];
        $hourStart = $data[3];
        $minStart = $data[4];
        $hourEnd = $data[5];
        $minEnd = $data[6];

        $hourStart = $hourStart + 1;
        if ($hourStart > 23) { $hourStart = 0; }
        $nextDay = false;
        $startTime = strtotime($hourStart.":".$minStart);
        $EndTime = strtotime($hourEnd.":".$minEnd);
        if ($EndTime < $startTime) { $nextDay = true; }

        $inline_key_work_time = generateWorkingDaysBtnNew($data[1], $day, $hourStart, $minStart, $hourEnd, $minEnd, $nextDay);
        $employee_data = $ezzeTeamsModel->getDetilUserById($user_id);
        $msg = 'Set <strong>'.$employee_data['firstname'].' '.$employee_data['lastname'].'</strong> Working Time For <strong>'.strtoupper(intToDay($day)).'</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key_work_time, $user_data['set_start_time_msg_id'], true);
    }

    /**
     * Setting Start Time
     * Decrease Minute
     */
    else if ($data[0] == 'prev-mn-assign-work-start-time') {

        $user_data = $ezzeTeamsModel->getUserByID($result->callback_query->from->id);

        $user_id = $data[1];
        $day = $data[2];
        $hourStart = $data[3];
        $minStart = $data[4];
        $hourEnd = $data[5];
        $minEnd = $data[6];

        $minStart = $minStart - 15;
        if ($minStart < 0) {
            $minStart = 45;
            $hourStart = $hourStart - 1;
            if ($hourStart < 0) {
                $hourStart = 23;
            }
        }
        $nextDay = false;
        $startTime = strtotime($hourStart.":".$minStart);
        $EndTime = strtotime($hourEnd.":".$minEnd);
        if ($EndTime < $startTime) { $nextDay = true; }

        $inline_key_work_time = generateWorkingDaysBtnNew($data[1], $day, $hourStart, $minStart, $hourEnd, $minEnd, $nextDay);
        $employee_data = $ezzeTeamsModel->getDetilUserById($user_id);
        $msg = 'Set <strong>'.$employee_data['firstname'].' '.$employee_data['lastname'].'</strong> Working Time For <strong>'.strtoupper(intToDay($day)).'</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key_work_time, $user_data['set_start_time_msg_id'], true);
    }

    /**
     * Setting Start Time
     * Increase Minute
     */
    else if ($data[0] == 'next-mn-assign-work-start-time') {

        $user_data = $ezzeTeamsModel->getUserByID($result->callback_query->from->id);

        $user_id = $data[1];
        $day = $data[2];
        $hourStart = $data[3];
        $minStart = $data[4];
        $hourEnd = $data[5];
        $minEnd = $data[6];

        $minStart = $minStart + 15;
        if ($minStart > 59) {
            $minStart = 0;
            $hourStart = $hourStart + 1;
            if ($hourStart > 23) {
                $hourStart = 0;
            }
        }
        $nextDay = false;
        $startTime = strtotime($hourStart.":".$minStart);
        $EndTime = strtotime($hourEnd.":".$minEnd);
        if ($EndTime < $startTime) { $nextDay = true; }

        $inline_key_work_time = generateWorkingDaysBtnNew($data[1], $day, $hourStart, $minStart, $hourEnd, $minEnd, $nextDay);
        $employee_data = $ezzeTeamsModel->getDetilUserById($user_id);
        $msg = 'Set <strong>'.$employee_data['firstname'].' '.$employee_data['lastname'].'</strong> Working Time For <strong>'.strtoupper(intToDay($day)).'</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key_work_time, $user_data['set_start_time_msg_id'], true);

    }

    /**
     * Setting End Time
     * Decrease Hour
     */
    else if ($data[0] == 'prev-h-assign-work-end-time') {

        $user_data = $ezzeTeamsModel->getUserByID($result->callback_query->from->id);

        $user_id = $data[1];
        $day = $data[2];
        $hourStart = $data[3];
        $minStart = $data[4];
        $hourEnd = $data[5];
        $minEnd = $data[6];
        $startTime = strtotime($hourStart.":".$minStart);

        $hourEnd = $hourEnd - 1;
        $nextDay = false;
        if ($hourEnd < 0) { $hourEnd = 23; }
        $EndTime = strtotime($hourEnd.":".$minEnd);
        if ($EndTime < $startTime) { $nextDay = true; }

        $inline_key_work_time = generateWorkingDaysBtnNew($data[1], $day, $hourStart, $minStart, $hourEnd, $minEnd, $nextDay);
        $employee_data = $ezzeTeamsModel->getDetilUserById($user_id);
        $msg = 'Set <strong>'.$employee_data['firstname'].' '.$employee_data['lastname'].'</strong> Working Time For <strong>'.strtoupper(intToDay($day)).'</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key_work_time, $user_data['set_start_time_msg_id'], true);

    }

    /**
     * Setting End Time
     * Increase Hour
     */
    else if ($data[0] == 'next-h-assign-work-end-time') {

        $user_data = $ezzeTeamsModel->getUserByID($result->callback_query->from->id);

        $user_id = $data[1];
        $day = $data[2];
        $hourStart = $data[3];
        $minStart = $data[4];
        $hourEnd = $data[5];
        $minEnd = $data[6];
        $startTime = strtotime($hourStart.":".$minStart);

        $hourEnd = $hourEnd + 1;
        $nextDay = false;
        if ($hourEnd > 23) { $hourEnd = 0; }
        $EndTime = strtotime($hourEnd.":".$minEnd);
        if ($EndTime < $startTime) { $nextDay = true; }

        $inline_key_work_time = generateWorkingDaysBtnNew($data[1], $day, $hourStart, $minStart, $hourEnd, $minEnd, $nextDay);
        $employee_data = $ezzeTeamsModel->getDetilUserById($user_id);
        $msg = 'Set <strong>'.$employee_data['firstname'].' '.$employee_data['lastname'].'</strong> Working Time For <strong>'.strtoupper(intToDay($day)).'</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key_work_time, $user_data['set_start_time_msg_id'], true);

    }

    /**
     * Setting End Time
     * Decrease Minute
     */
    else if ($data[0] == 'prev-mn-assign-work-end-time') {

        $user_data = $ezzeTeamsModel->getUserByID($result->callback_query->from->id);

        $user_id = $data[1];
        $day = $data[2];
        $hourStart = $data[3];
        $minStart = $data[4];
        $hourEnd = $data[5];
        $minEnd = $data[6];
        $startTime = strtotime($hourStart.":".$minStart);

        $minEnd = $minEnd - 15;
        $nextDay = false;
        if ($minEnd < 0) {
            $minEnd = 45;
            $hourEnd = $hourEnd - 1;
            if ($hourEnd < 0) {
                $hourEnd = 23;
            }
        }
        $EndTime = strtotime($hourEnd.":".$minEnd);
        if ($EndTime < $startTime) { $nextDay = true; }

        $inline_key_work_time = generateWorkingDaysBtnNew($data[1], $day, $hourStart, $minStart, $hourEnd, $minEnd, $nextDay);
        $employee_data = $ezzeTeamsModel->getDetilUserById($user_id);
        $msg = 'Set <strong>'.$employee_data['firstname'].' '.$employee_data['lastname'].'</strong> Working Time For <strong>'.strtoupper(intToDay($day)).'</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key_work_time, $user_data['set_start_time_msg_id'], true);

    }

    /**
     * Setting End Time
     * Increase Minute
     */
    else if ($data[0] == 'next-mn-assign-work-end-time') {

        $user_data = $ezzeTeamsModel->getUserByID($result->callback_query->from->id);

        $user_id = $data[1];
        $day = $data[2];
        $hourStart = $data[3];
        $minStart = $data[4];
        $hourEnd = $data[5];
        $minEnd = $data[6];

        $minEnd = $minEnd + 15;
        $nextDay = false;
        if ($minEnd > 59) {
            $minEnd = 0;
            $hourEnd = $hourEnd + 1;
            if ($hourEnd > 23) {
                $hourEnd = 0;
            }
        }
        $startTime = strtotime($hourStart.":".$minStart);
        $EndTime = strtotime($hourEnd.":".$minEnd);
        if ($EndTime < $startTime) { $nextDay = true; }

        $inline_key_work_time = generateWorkingDaysBtnNew($data[1], $day, $hourStart, $minStart, $hourEnd, $minEnd, $nextDay);
        $employee_data = $ezzeTeamsModel->getDetilUserById($user_id);
        $msg = 'Set <strong>'.$employee_data['firstname'].' '.$employee_data['lastname'].'</strong> Working Time For <strong>'.strtoupper(intToDay($day)).'</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key_work_time, $user_data['set_start_time_msg_id'], true);

    }

    /**
     * Confirm Working Time
     */
    else if ($data[0] == 'confirm-workingtime' || $data[0] == 'dayoff-workingtime') {

        $user_id = $data[1];
        $day = $data[2];
        $hourStart = $data[3];
        $minStart = $data[4];
        $hourEnd = $data[5];
        $minEnd = $data[6];
        $employee_data = $ezzeTeamsModel->getDetilUserById($user_id);

        $msgTest = "user id: ".$user_id .
            "_Name: ".$employee_data['firstname']." ".$employee_data['lastname'] .
            "_day: ".$day." (".intToDay($day).")" .
            "_Start Time: ".sprintf("%02d", $hourStart).":".sprintf("%02d", $minStart) .
            "_End Time: ".sprintf("%02d", $hourEnd).":".sprintf("%02d", $minEnd);

        //save working hour in DB
        if ($day == 1) {
            $ezzeTeamsModel->resetWorkingTimeByUser($user_id);
        }
        if ($data[0] == 'confirm-workingtime') {
            $params = [
                'user_id' => intVal($user_id),
                'day' => intToDay($day, true),
                'start_time' => sprintf("%02d", $hourStart) . ":" . sprintf("%02d", $minStart),
                'end_time' => sprintf("%02d", $hourEnd) . ":" . sprintf("%02d", $minEnd)
            ];
            $ezzeTeamsModel->saveWorkingTime($params);
        }

        $nextDay = $day + 1;
        if ($nextDay > 7) {
            //set employee status to approve
            if ($employee_data['approval_status'] == 'approved') {
                $newSchedule = generateWorkingSchedule($employee_data['user_id']);
                $m = _l('admin_change_working_hour_success')." <strong>".$employee_data['firstname']." ".$employee_data['lastname']."</strong>";
                $m .= $newSchedule;
                prepareMessage(null, $m, null, null, array($result->callback_query->from->id));
                $msg = "Your Working Schedule has been changed by Admin";
                $msg .= $newSchedule;
                prepareMessage(null, $msg, null, null, array($data[1]));
            } else {
                $user_data = $ezzeTeamsModel->getUserByID($data[1]);
                $ezzeTeamsModel->updateUserWhere($data[1], "approval_status = 'approved', step='approved', approved_by=".$result->callback_query->from->id);
                $name = $employee_data['firstname']." ".$employee_data['lastname'];
                prepareMessage(null, _l('admin_approved_registration', [$name]), null, null, array($result->callback_query->from->id));
                setEmployeeMenu($data[1]);

                $msg = _l('admin_approved_registration_to_user');
                $msg .= "__" . _l('admin_info_work_location') . $user_data['branch_name'];
                $msg .= "_" . _l('admin_info_map')." /worklocation";
                $msg .= generateWorkingSchedule($data[1]);
                prepareMessage(generateClockInBtn(), $msg, null, null, array($data[1]));
            }
            return;
        } else {
            //generate new working time button
            $user_data = $ezzeTeamsModel->getUserByID($result->callback_query->from->id);

            $inline_key_work_day = generateWorkingDaysBtnNew($user_id, $nextDay, $hourStart, $minStart, $hourEnd, $minEnd);
            $msg = 'Set <strong>'.$employee_data['firstname'].' '.$employee_data['lastname'].'</strong> Working Time For <strong>'.strtoupper(intToDay($nextDay)).'</strong>';
            prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key_work_day, $user_data['set_start_time_msg_id'], true);
            return;
        }
    }

    /** Change Working Time **/
    else if ($data[0] == 'change-working-time') {
        $inline_key_work_day = generateWorkingDaysBtnNew($data[1], 1, 8, 0, 17, 0);
        $msg = 'Set <strong>'.$data[2].'</strong> Working Time For <strong>'.strtoupper(intToDay(1)).'</strong>';
        prepareMessage(null, $msg, null, null, array($result->callback_query->from->id), null, true, $inline_key_work_day,null, true);
    }

    /** Set employee as Admin */
    else if ($data[0] == 'set-admin') {
        $params['user_id'] = $data[1];
        $params['name'] = ucfirst($data[2]);
        $m = $ezzeTeamsModel->getAdminByUserId($data[1]);
        if (is_array($m) && count($m) > 0) {
            $msg = _l('admin_new_already_exists', [$params['user_id']]);
        } else {
            $s = $ezzeTeamsModel->addAdmin($params);
            $msg = _l('admin_new_success', [$params['user_id'], $params['name']]);
            $ezzeTeamsModel->setAdminStep($data[1], '', '');
            prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), "You have been added to Admin", null, null, [$data[1]]);
        }
        setAdminMenu($data[1]);
        prepareMessage(null, $msg, null, null, array($result->callback_query->from->id));
    }

    /** Remove employee as Admin */
    else if ($data[0] == 'remove-admin') {
        $params['user_id'] = $data[1];
        $params['name'] = ucfirst($data[2]);
        $params['approval'] = 'approved';
        if (in_array($data[1], $admin_id)) {
            $ezzeTeamsModel->removeAdmin($params);
            $msg = _l('admin_new_remove_success', [$params['user_id'], $params['name']]);

            $uData = $ezzeTeamsModel->getDetilUserById($data[1]);
            require_once __DIR__ ."/includes/language/".$uData['lang'].".php";
            $r = getLastStatusByData($data[1]);

            //refresh Employee
            prepareMessage($r['keyboard'], "You have been removed from Admin", null, null, [$data[1]]);
        } else {
            $msg = _l('admin_new_already_exists', [$params['user_id']]);
        }
        setEmployeeMenu($data[1]);
        prepareMessage(null, $msg, null, null, array($result->callback_query->from->id));
    }

    /** Set Inactive employee */
    else if ($data[0] == 'set-inactive') {
        $employee_id = $data[1];
        $where = "approval_status = 'inactive'";
        $ezzeTeamsModel->updateUserWhere($employee_id, $where);
        $msg = _l('admin_inactive_user_success', [$data[1], $data[2]]);
        prepareMessage(null, $msg, null, null, array($result->callback_query->from->id));
    }

    /** Change Work Location from view Employee */
    else if ($data[0] == 'change-work-location') {
        $inline_key_branch = generateBranchBtnEmployeeDetil($data[1], $data[2]);
        $msg = _l('admin_change_work_location', [$data[2]]);
        prepareMessage(null, $msg, null, null, array($result->callback_query->from->id), null, true, $inline_key_branch);
    }

    /* refresh employee status */
    else if ($data[0] == 'refresh-employee') {
        $uData = $ezzeTeamsModel->getDetilUserById($data[1]);
        require_once __DIR__ ."/includes/language/".$uData['lang'].".php";
        $r = getLastStatusByData($data[1]);

        //refresh Employee
        prepareMessage($r['keyboard'], _l('your_account_refreshed'), null, null, [$data[1]]);

        //admin notify already refreshed
        $msg = _l('admin_refresh_success', [$uData['firstname']." ".$uData['lastname']]);
        prepareMessage(null, $msg, null, null, array($result->callback_query->from->id));
    }

    /**  */
    elseif ($data[0] == 'ch-assign-branch') {
        $ezzeTeamsModel->changeWorkLocationEmployee($data[1], $data[3], $data[4]);

        $msg = _l('admin_change_work_location_success', ['/viewEmployee'.$data[1]]);
        prepareMessage(null, $msg, null, null, array($result->callback_query->from->id));

        $msg = "Your Work Location has been changed by Admin";
        $msg .= "__" . _l('admin_info_work_location').' '.$data[4];
        $msg .= "_" . _l('admin_info_map') . " " . _l('button_show_work_location');
        prepareMessage(null, $msg, null, null, array($data[1]));
    }

    /**
     * Admin Confirmed Approval
     */
    else if ($data[0] == 'confirm-approval') {

        $ezzeTeamsModel->updateUserWhere($data[1], "approval_status = 'approved'");
        prepareMessage(null, _l('admin_approved_registration'), null, null, array($result->callback_query->from->id));
        prepareMessage(generateClockInBtn(), _l('admin_approved_registration_to_user'), null, null,  array($data[1]));
    }

    else if ($data[0] == 'reject') {

        $ezzeTeamsModel->updateUserWhere($data[1], "approval_status = 'rejected', approved_by = ".$result->callback_query->from->id);
        prepareMessage(null, _l('admin_reject_registration', [$data[2]]), null, null,  array($result->callback_query->from->id));
        prepareMessage([[_l('button_back_home')]], _l('admin_reject_registration_to_user'), null, null,  array($data[1]));

    }

    else if ($data[0] == 'prev') {

        generateListEmployee(array($result->callback_query->from->id), true, 10, ($limit * ($data[1] - 1)) - $limit, $data[1] - 1);

    }

    else if ($data[0] == 'next') {

        generateListEmployee(array($result->callback_query->from->id), true, 10, ($limit * ($data[1] + 1)) - $limit, $data[1] + 1);

    }

    else if ($data[0] == 'employee-prev') {

        generateApprovedEmployee(array($result->callback_query->from->id), true, 10, ($limit * ($data[1] - 1)) - $limit, $data[1] - 1);

    }

    else if ($data[0] == 'employee-next') {

        generateApprovedEmployee(array($result->callback_query->from->id), true, 10, ($limit * ($data[1] + 1)) - $limit, $data[1] + 1);

    }

    else if ($data[0] == 'scheduleMessageRepeat') {
        $userId = $result->callback_query->from->id;
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], TRUE);
        /**
         * one time = 1
         * repeat = 2
         */
        $tempData['runtime'] = $data[1];

        $ezzeTeamsModel->setAdminStep($userId, $adminUser['step'], json_encode($tempData));
        $inline_key = generateMessageScheduleTime(1, 8, 0);
        $msg = 'Set Schedule Message for <strong>' . strtoupper(intToDay(1)) . '</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key, $tempData['msgEditId'], true);
    }

    else if ($data[0] == 'next-h-schedule-message') {
        $userId = $result->callback_query->from->id;
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], TRUE);

        $day = $data[1];
        $hourStart = $data[2];
        $minStart = $data[3];

        $hourStart = $hourStart + 1;
        if ($hourStart > 23) { $hourStart = 0; }

        $inline_key = generateMessageScheduleTime($day, $hourStart, $minStart);
        $msg = 'Set Schedule Message for <strong>' . strtoupper(intToDay($day)) . '</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key, $tempData['msgEditId'], true);
    }

    else if ($data[0] == 'prev-h-schedule-message') {
        $userId = $result->callback_query->from->id;
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], TRUE);

        $day = $data[1];
        $hourStart = $data[2];
        $minStart = $data[3];

        $hourStart = $hourStart - 1;
        if ($hourStart < 0) { $hourStart = 23; }

        $inline_key = generateMessageScheduleTime($day, $hourStart, $minStart);
        $msg = 'Set Schedule Message for <strong>' . strtoupper(intToDay($day)) . '</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key, $tempData['msgEditId'], true);
    }

    else if ($data[0] == 'next-mn-schedule-message') {
        $userId = $result->callback_query->from->id;
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], TRUE);

        $day = $data[1];
        $hourStart = $data[2];
        $minStart = $data[3];

        $minStart = $minStart + 15;
        if ($minStart > 59) {
            $minStart = 0;
            $hourStart = $hourStart + 1;
            if ($hourStart > 23) {
                $hourStart = 0;
            }
        }

        $inline_key = generateMessageScheduleTime($day, $hourStart, $minStart);
        $msg = 'Set Schedule Message for <strong>' . strtoupper(intToDay($day)) . '</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key, $tempData['msgEditId'], true);
    }

    else if ($data[0] == 'prev-mn-schedule-message') {
        $userId = $result->callback_query->from->id;
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], TRUE);

        $day = $data[1];
        $hourStart = $data[2];
        $minStart = $data[3];

        $minStart = $minStart - 15;
        if ($minStart < 0) {
            $minStart = 45;
            $hourStart = $hourStart - 1;
            if ($hourStart < 0) {
                $hourStart = 23;
            }
        }

        $inline_key = generateMessageScheduleTime($day, $hourStart, $minStart);
        $msg = 'Set Schedule Message for <strong>' . strtoupper(intToDay($day)) . '</strong>';
        prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key, $tempData['msgEditId'], true);
    }

    else if ($data[0] == 'schedule-message-confirm' || $data[0] == 'schedule-message-skip') {
        $userId = $result->callback_query->from->id;
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], TRUE);

        $day = $data[1];
        $hourStart = $data[2];
        $minStart = $data[3];
        $timeFormat = sprintf("%02d", $hourStart) . ":" . sprintf("%02d", $minStart);

        if ($data[0] == 'schedule-message-confirm') {
            $tempData['schedule'][] = ['day' => intToDay($day, true), 'time' => $timeFormat];
            $ezzeTeamsModel->setAdminStep($userId, $adminUser['step'], json_encode($tempData));
        }

        $nextDay = $day + 1;
        if ($nextDay > 7) {
            $ezzeTeamsModel->saveScheduledMessage($userId, $tempData);
            $ezzeTeamsModel->setAdminStep($userId, '', '');
            $msg = "Schedule Messages succesfully saved!";
            prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, false, null, $tempData['msgEditId']);
        } else {
            $msg = 'Set Schedule Message for <strong>' . strtoupper(intToDay($nextDay)) . '</strong>';
            $inline_key = generateMessageScheduleTime($nextDay, $hourStart, $minStart);
            prepareMessage(null, $msg, null, 'editMessageText', array($result->callback_query->from->id), null, true, $inline_key, $tempData['msgEditId'], true);
        }
    }

    else if ($data[0] == 'messages-next') {
        $userId = $result->callback_query->from->id;
        $current_user_data = $ezzeTeamsModel->getUserByID($userId);
        getAllScheduledMessageLists($data[1], $current_user_data['list_emp_msg_id']);
    }

    else if ($data[0] == 'messages-prev') {
        $userId = $result->callback_query->from->id;
        $current_user_data = $ezzeTeamsModel->getUserByID($userId);
        getAllScheduledMessageLists($data[1], $current_user_data['list_emp_msg_id']);
    }

    else if ($data[0] == 's-message-edit') {
        $tempData = ['type' => 'edit', 'id' => $data[1]];
        $ezzeTeamsModel->setAdminStep($result->callback_query->from->id, 'scheduledMessage_setTitle', json_encode($tempData));
        $msg = "Please type a new title";
        prepareMessage(null, $msg, null, null, [$result->callback_query->from->id]);
    }

    else if ($data[0] == 's-message-remove') {
        $ezzeTeamsModel->removeSMessage($data[1]);
        $msg = "schedule Message successfully removed!";
        prepareMessage(null, $msg, null, null, [$result->callback_query->from->id]);
    }

}

else if (isset($action)) {
    $adminUser = $adminDetils[$userId];
    if ($adminUser['step'] == 'admin_branch_edit_name') {
        //prepareMessage(null, ucwords($action));
        $params = json_decode($adminUser['temp'], true);
        $params['pos'] = 'edit_branch_location';
        $params['new_branch_name'] = ucwords($action);
        $ezzeTeamsModel->setAdminStep($userId, 'admin_branch_edit_location', json_encode($params));
        prepareMessage(null, _l('admin_branch_add_location', [ucwords($action)]));
        return;
    }
    else if ($adminUser['step'] == 'admin_branch_remove_confirmation') {
        if (strtolower($action) == strtolower(_l('button_yes'))) {
            $params = json_decode($adminUser['temp'], true);

            $ezzeTeamsModel->removeBranchById($params['id']);
            $ezzeTeamsModel->setAdminStep($userId, '');
            prepareMessage(array(array(_l('button_show_user_table'))), _l('admin_branch_remove_success'));
        } else if (strtolower($action) == strtolower(_l('button_no'))) {
            $ezzeTeamsModel->setAdminStep($userId, '');
            prepareMessage(array(array(_l('button_show_user_table'))), _l('thank_you'));
        }
    }
    else if ($adminUser['step'] == 'admin_branch_edit_confirmation') {
        if (strtolower($action) == strtolower(_l('button_yes'))) {
            $pos = json_decode($adminUser['temp'], true);

            $params = [
                'branch_name' => ucwords($pos['new_branch_name']),
                'lat' => $pos['lat'],
                'long' => $pos['long']
            ];
            $ezzeTeamsModel->editBranchById($pos['id'], $params);
            $ezzeTeamsModel->setAdminStep($userId, '');
            $msg = _l('admin_branch_edit_success');
            prepareMessage(array(array(_l('button_show_user_table'))), $msg);
        } else if (strtolower($action) == strtolower(_l('button_no'))) {
            $ezzeTeamsModel->setAdminStep($userId, '');
            prepareMessage(array(array(_l('button_show_user_table'))), _l('thank_you'));
        }
    }
    else if ($adminUser['step'] == 'set-welcome-message-text') {
        $params = json_decode($adminUser['temp'], true);

        $data['num'] = $params['num'] + 1;
        $data['type'] = $botSettings['lang_active'][$data['num']];
        $data['text'] = $params['text'];
        $data['text'][$params['type']] = $action;
        if ($data['num'] <= (count($botSettings['lang_active']) - 1)) {
            $step = "set-welcome-message-text";
            $ezzeTeamsModel->setAdminStep($userId, $step, json_encode($data));
            $msg = "[".strtoupper($data['type'])."] Please Set a Welcome Messages";
        } else {
            $step = "set-welcome-message";
            $text['message'] = $data['text'];
            $ezzeTeamsModel->setAdminStep($userId, $step, json_encode($data['text']));
            $msg = "Please Send a Logo/Images of your company";
        }

        prepareMessage(null, $msg);
    }
    else if ($adminUser['step'] == 'send_message_all') {
        $tempData = ['message' => $action];
        $ezzeTeamsModel->setAdminStep($userId, 'send_message_add_media', json_encode($tempData));
        $msg = "(Optional) Upload a Media (Images/Video)";
        $btn = [
            [
                [
                    "text" => 'Skip'
                ]
            ]
        ];
        prepareMessage($btn, $msg);
    }
    else if ($adminUser['step'] == 'send_message_add_media' && strtolower($action) == 'skip') {
        $msg = "OK. Messages will be sent to all Bot User";
        prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), $msg);

        $temp = json_decode($adminUser['temp'], true);
        sendMessageToAllUser($temp['message']);
        $ezzeTeamsModel->setAdminStep($userId, '', '');
    }
    else if ($adminUser['step'] == 'scheduledMessage_setTitle') {
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], true);
        $tempData['title'] = $action;
        $ezzeTeamsModel->setAdminStep($userId, 'scheduledMessage_setMessage', json_encode($tempData));
        $msg = "Please type a Message";
        prepareMessage(null, $msg);
    }
    else if ($adminUser['step'] == 'scheduledMessage_setMessage') {
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], true);
        $tempData['message'] = $action;
        $tempData['destination'] = 'all';
        $ezzeTeamsModel->setAdminStep($userId, 'scheduledMessage_addMedia', json_encode($tempData));

        $msg = "(Optional) Upload a Media (Images/Video)";
        $btn = [
            [
                [
                    "text" => 'Skip'
                ]
            ]
        ];
        prepareMessage($btn, $msg);
    }
    else if ($adminUser['step'] == 'scheduledMessage_addMedia' && strtolower($action) == 'skip') {
        $adminUser = $adminDetils[$userId];
        $tempData = json_decode($adminUser['temp'], true);
        $tempData['media_type'] = 'text';
        $tempData['media'] = '';

        $ezzeTeamsModel->setAdminStep($userId, 'scheduledMessage_setConfigurations', json_encode($tempData));
        $msg = "No Media";
        prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), $msg);
        $msg = "Scheduled Message Repeat Configurations";
        $btn = generateRuntimeSchedultMessageButton();
        prepareMessage(null, $msg, null, null, null, null, true, $btn);
    }
    else if ($adminUser['step'] == 'set-schedule-daily-report-time') {
        $x = explode(':', $action);
        if (count($x) == 2) {
            if ( $x[0] >= 0 && $x[0] < 24 && $x[1] >= 0 && $x[1] < 60 ) {
                $tempData = json_decode($adminUser['temp'], true);
                $tempData['time'] = $action;

                $ezzeTeamsModel->setAdminStep($userId, 'set-schedule-daily-report-data-range', json_encode($tempData));
                $msg = _l('admin_schedule_daily_report_set_data_range', [$tempData['time']]);
            } else {
                $msg = _l('admin_time_invalid');
            }
        } else {
            $msg = _l('admin_time_invalid');
        }
        prepareMessage(null, $msg);
    }
    else if ($adminUser['step'] == 'set-schedule-daily-report-data-range') {
        $maxHour = 87600;

        if (is_numeric($action)){
            $action = (int) $action;
            if ($action > 0 && $action <= $maxHour) {
                $tempData = json_decode($adminUser['temp'], true);
                $additionalParams['data-hour-range'] = $action;
                $ezzeTeamsModel->changeCronConfig('daily-report', $tempData['enable'], $tempData['time'], $additionalParams);
                $ezzeTeamsModel->setAdminStep($userId, '', '');
                $msg = _l('admin_schedule_daily_report_success', [$tempData['time'], $action]);
            } else {
                $msg = "Hour must 1-".$maxHour;
            }
        } else {
            $msg = "Hour must 0-".$maxHour;
        }
        prepareMessage(null, $msg);
    }
    else {
        prepareMessage(null, _l('admin_command_not_exists'));
    }
}