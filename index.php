<?php
include "load.php";

date_default_timezone_set("Asia/Phnom_Penh");

global $mysqli;

$result = json_decode(file_get_contents('php://input'));
$action = $result->message->text;
$userId = $result->message->from->id;
$first_name = $result->message->from->first_name;
$last_name = isset($result->message->from->last_name) ? $result->message->from->last_name : "";
$tgUsername = (isset($result->message->from->username)) ? "@".$result->message->from->username : "";
$tgUsernamePlain = (isset($result->message->from->username)) ? $result->message->from->username : "";
$tgUsernameForChat = (isset($result->message->from->username)) ? "@".str_replace("_", "###", $result->message->from->username) : "";

$ezzeTeamsModel = new EzzeTeamsModel();

$logID = $ezzeTeamsModel->saveUserBotRequest2($userId, $result);

$uData = $ezzeTeamsModel->getDetilUserById($userId);

if (isset($uData['lang']) && file_exists(__DIR__ .'/includes/language/'.$uData['lang'].'.php')) {
    $userLang = $uData['lang'];
    include __DIR__ ."/includes/language/".$uData['lang'].".php";
} else {
    $userLang = "en";
    include __DIR__ ."/includes/language/en.php";
}

/*** Admin */
if (in_array($userId, $admin_id) || isset($result->callback_query->data)) {

    include "admin.php";

} else {
    /** User */

    /** Reply Validation */
    $u = $ezzeTeamsModel->isUserIdExists($userId);
    if ($u){
        $userCommandss = json_decode(json_encode($result), true);
        $user_data = $u;

        if ($tgUsernamePlain == '') {
            $m = "You don't have a valid username. Please set your telegram username.";
            prepareMessage(null, $m);
            //exit;
        } elseif ($tgUsernamePlain != $user_data['tg_username']){
            $ezzeTeamsModel->updateTgUsername($userId, $tgUsernamePlain);
        }

        if ( !isset($userCommandss['message']['entities'][0]['type']) ||
            (isset($userCommandss['message']['entities'][0]['type']) && $userCommandss['message']['entities'][0]['type'] != 'bot_command') ) {

            $button = false;
            if (isset($userCommandss['message']['photo'])) {
                $pos = 'image';
            } elseif (isset($userCommandss['message']['location'])) {
                if (isset($userCommandss['message']['location']['live_period'])) {
                    $pos = 'live_location';
                } else {
                    $pos = 'location';
                }
            } else {
                if (in_array($userCommandss['message']['text'], $langBase)) {
                    $pos = 'button';
                    $button = $userCommandss['message']['text'];
                } else {
                    $pos = 'freetext';
                }
            }

            include __DIR__ . "/includes/functions/userReplyConfigurations.php";
            $isCorrect = isCorrectReply($user_data['step'], $pos, $button);
            if ($isCorrect === false) {
                prepareMessage(null, _l('incorrect_reply'));
                $userStatus = $ezzeTeamsModel->isUserIdExists($userId);
                $ezzeTeamsModel->incorrectReplyUserStatus($logID, $userStatus);
                exit;
            }
        }
    }

    /*** Step 1:
     * Startup + Menu Command -> Go To Select Language **/
    if ( in_array($action, array(_l('button_start'), _l('menu_refresh'), _l('button_back_home'), _l('button_cancel'))) ) {

        $is_join = $ezzeTeamsModel->getUser();
        $user_data = $ezzeTeamsModel->getUser('', false);
        $welcomeMsg = json_decode($botSettings['welcome_msg']);
        $welcomeMsgLang = $welcomeMsg->{$userLang};
        if($welcomeMsg->type == 'video') {
            $logoImg = $botSettings['welcome_img'];
            $msgtype = 'video';
        } else {
            $logoImg = BASE_URL."images/".$botSettings['welcome_img'].".jpg";
            $msgtype = 'image';
        }

        if ($is_join <= 0) {
            $pattern = '/\\\u[(0-9a-fA-F)]{3}/';
            if (preg_match($pattern, json_encode($first_name)) || preg_match($pattern, json_encode($last_name))) {
                prepareMessage(null, 'Please change your Name to Alphanumeric and type /start to continue');
                exit;
            }
            if ($tgUsername == "") {
                $m = "You don't have a valid username. Please set your telegram username and try again";
                prepareMessage(null, $m);
                exit;
            }
            $ezzeTeamsModel->insertUserStep('select_lang');
            $nonUserWelcomeMsg = "";
            foreach($langActive as $la){
                if (isset($welcomeMsg->{$la})) {
                    $nonUserWelcomeMsg .= $welcomeMsg->{$la}."__";
                }
            }
            $selectLang = collectAllLangValue('select_language');
            if ($msgtype == 'video') {
                prepareMessage(null, $nonUserWelcomeMsg, null, 'sendVideo', array($userId), null, false, null, null, false, null, $logoImg);
            } else {
                prepareMessage(null, $nonUserWelcomeMsg, $logoImg, 'sendPhoto', array($userId));
            }
            prepareMessage(array(array(_l('button_lang_khmer'), _l('button_lang_english'))), $selectLang);
        }

        else if ($user_data['approval_status'] == 'approved') {
            $v = (int) 1;
            $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);
            $r = getLastStatusByData($user_data['user_id']);
            if ($msgtype == 'video') {
                prepareMessage($r['keyboard'], $welcomeMsgLang, null, 'sendVideo', array($userId), null, false, null, null, false, null, $logoImg);
            } else {
                prepareMessage($r['keyboard'], $welcomeMsgLang, $logoImg, 'sendPhoto', array($userId));
            }

            /** set menu button for user */
            setEmployeeMenu($userId);
            exit;
        }
        else if ($user_data['approval_status'] == 'inactive') {
            prepareMessage(array(array(_l('button_back_home'))), _l('restricted'));
        }
        else if ($user_data['step'] == 'waiting') {
            prepareMessage(null, _l('already_registered'));exit();
        }

        else {
            prepareMessage(array(array(_l('button_lang_khmer'), _l('button_lang_english'))), _l('select_language'));
        }

    }

    /***
     * Change Language After Clock In **/
    else if (strtolower($action) == strtolower(_l('button_change_language')) || strtolower($action) == strtolower(_l('command_change_lang'))) {
        $ezzeTeamsModel->updateUserWhere($userId, "step = 'select_lang'");
        prepareMessage(array(array(_l('button_lang_khmer'), _l('button_lang_english'))), _l('select_language'));
    }

    /*** Step 2:
     * Select Khmer Language -> Back To English **/
    else if ($action == strtolower(_l('button_lang_khmer'))) {
        $is_join = $ezzeTeamsModel->getUser();
        $user_data = $ezzeTeamsModel->getUser('', false);

        if ($is_join <= 0) {
            $pattern = '/\\\u[(0-9a-fA-F)]{3}/';
            if (preg_match($pattern, json_encode($first_name)) || preg_match($pattern, json_encode($last_name))) {
                prepareMessage(null, 'សូមប្តូរឈ្មោះរបស់អ្នកទៅជាអក្សរក្រមលេខ ហើយវាយ /ចាប់ផ្តើម ដើម្បីបន្ត');
                exit;
            }
            $ezzeTeamsModel->insertUserStep('select_lang');
        }

        $user_data['lang'] = 'kh';
        $m = $ezzeTeamsModel->userChangeLang($userId, $user_data['lang']);
        include __DIR__ ."/includes/language/kh.php";
        if ($user_data['approval_status'] == 'approved') {
            $st = getLastStatusByData($user_data['user_id']);
            prepareMessage($st['keyboard'], '<strong>'._l('you_have_selected_kh').'</strong>');

            /** set menu button for user */
            setEmployeeMenu($userId);
            exit;
            //prepareMessage(array(array('Clock In'), array('Change Language')), '<strong>You have selected 🇺🇸 English</strong>');
        }

        else if ($user_data['step'] == 'waiting') {
            prepareMessage(null, _l('already_registered'));exit();
        }

        else {
            prepareMessage(array(array(array('text' => _l('button_share_contact'), 'request_contact' => true))), _l('share_contact_msg'));
        }

        //prepareMessage(array(array(_l('button_back_to_english'))), _l('lang_not_available'));
    }

    /*** Step 3:
     * Select English Language -> Request Share Telegram Profile **/
    else if (in_array(strtolower($action), array(strtolower(_l('button_lang_english')), strtolower(_l('button_back_to_english'))))) {

        $is_join = $ezzeTeamsModel->getUser();
        $user_data = $ezzeTeamsModel->getUser('', false);

        if ($is_join <= 0) {
            $pattern = '/\\\u[(0-9a-fA-F)]{3}/';
            if (preg_match($pattern, json_encode($first_name))) {
                prepareMessage(null, 'Please change your Name to Alphanumeric and type /start to continue');
                exit;
            }
            $ezzeTeamsModel->insertUserStep('select_lang');
        }

        $user_data['lang'] = 'en';
        $m = $ezzeTeamsModel->userChangeLang($userId, $user_data['lang']);
        include __DIR__ ."/includes/language/".$user_data['lang'].".php";
        if ($user_data['approval_status'] == 'approved') {
            $st = getLastStatusByData($user_data['user_id']);
            prepareMessage($st['keyboard'], '<strong>'._l('you_have_selected_eng').'</strong>');

            /** set menu button for user */
            setEmployeeMenu($userId);
            exit;
            //prepareMessage(array(array('Clock In'), array('Change Language')), '<strong>You have selected 🇺🇸 English</strong>');
        }

        else if ($user_data['step'] == 'waiting') {
            prepareMessage(null, _l('already_registered'));exit();
        }

        else {
            prepareMessage(array(array(array('text' => _l('button_share_contact'), 'request_contact' => true))), _l('share_contact_msg'));
        }

    }

    /*** Step 4:
     * Share Contact Information -> Request Share Selfie **/
    else if (isset($result->message->contact->phone_number)) {
        $fChar = substr($result->message->contact->phone_number, 0, 1);
        if ($fChar != '+' && $fChar != '0') {
            $phone = '+'.$result->message->contact->phone_number;
        } else {
            $phone = $result->message->contact->phone_number;
        }
        $ezzeTeamsModel->updateUserProfiles($phone);
        $ezzeTeamsModel->updateUserWhere($userId, "step = 'register_req_photo'");
        prepareMessage(array(array(_l('button_cancel'))), _l('please_send_selfie'));
    }

    /*** Step 5:
     * Share Selfie -> Request Input ID/Code/Email Employee **/
    else if (isset($result->message->photo) || (isset($result->message->document->mime_type) && in_array($result->message->document->mime_type, array('image/png', 'image/jpeg')))) {
        $user_data = $ezzeTeamsModel->getUser('', false);

        if (count($user_data) > 0 && ($user_data['approval_status'] == 'approved' || $user_data['approval_status'] == '') ) {

            if ($user_data['step'] == 'clock_in_req_selfie' || $user_data['step'] == 'clock_out_share_selfie' ||
                $user_data['step'] == 'start_break_req_selfie' || $user_data['step'] == 'end_break_req_selfie' ||
                $user_data['step'] == 'start_visit_req_selfie') {
                $selfie_id = $result->message->photo[0]->file_id;

                if ($user_data['step'] == 'clock_out_share_selfie') {

                    $ezzeTeamsModel->updateClockOutSet("clock_in_selfie_msg_id = '$selfie_id'");

                    $v = (int) 1;
                    $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);

                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_out_done'");

                    //clock out data
                    $clock_in_data = $ezzeTeamsModel->getClockIn("and is_clock_in = 'clock_out'", false);
                    $dateClock = date('H:i', strtotime($clock_in_data['created_at']));
                    $dateClockStr = strtotime($dateClock);
                    // = $ezzeTeamsModel->getUserWorkingSchedule($userId);
                    $workEnd = DateTime::createFromFormat('H:i:s', $clock_in_data['work_start_time'])->format("H:i");
                    $workEndStr = strtotime($workEnd) + ($botSettings['time_tolerance'] * 60);
                    if ($dateClockStr > $workEndStr) {
                        $ezzeTeamsModel->changeClockTimeStatus($clock_in_data['id'], 'LATE CLOCK OUT');
                        $clock_in_data['clock_in_time_status'] = 'LATE CLOCK OUT';
                    }

                    $clockData = $ezzeTeamsModel->getClockData('clock_out');
                    $lastBreak = $ezzeTeamsModel->getLastRecordBreak();
                    if ($lastBreak['break_action'] == 'start_break') {
                        $params = [];
                        $params['user_id'] = $clockData['user_id'];
                        $params['day'] = date('D');
                        $params['break_time'] = date("H:i:s");
                        $params['location_status'] = $clockData['clock_in_location_status'];
                        $params['location_lat'] = $clockData['clock_in_lat'];
                        $params['location_lon'] = $clockData['clock_in_lon'];
                        $params['location_msg_id'] = $clockData['clock_in_location_msg_id'];
                        $params['location_distance'] = $clockData['clock_in_distance'];
                        $params['selfie_msg_id'] = $clockData['clock_in_selfie_msg_id'];
                        $params['action'] = "end_break";
                        $ezzeTeamsModel->userBreak($params);
                    }

                    $lastVisit = $ezzeTeamsModel->getLastRecordVisit($userId);
                    if ($lastVisit['visit_action'] == 'start_visit') {
                        $params = [];
                        $params['user_id'] = $userId;
                        $params['visit_day'] = date('D');
                        $params['visit_time'] = date("Y-m-d H:i:s");
                        $params['visit_lat'] = $clockData['clock_in_lat'];
                        $params['visit_lon'] = $clockData['clock_in_lon'];
                        $params['visit_location_msg_id'] = $clockData['clock_in_location_msg_id'];
                        $params['selfie_msg_id'] = $clockData['clock_in_selfie_msg_id'];
                        $params['visit_notes'] = 'Employe clocked out before ending visit';
                        $params['action'] = 'end_visit';
                        $ezzeTeamsModel->userVisit($params);
                    }

                    $ezzeTeamsModel->markDeadManRemainOnClockOut($clockData['user_id']);
                    $ezzeTeamsModel->setForceClockOutReminder($clockData['user_id'], 'Clocked OUT');

                    $sentNotifications = false;
                    if ($clock_in_data['clock_in_location_status'] != 'OK' && $clock_in_data['clock_in_time_status'] != 'OK') {
                        if ($clock_in_data['clock_in_time_status'] == 'LATE CLOCK OUT') {
                            $m = _l('clock_out_wrong_and_late', [$first_name . ' ' . $last_name]);
                        } else {
                            $m = _l('clock_out_wrong_and_early', [$first_name . ' ' . $last_name]);
                        }
                        $employeeMsg = _l('clock_out_received');
                        $sentNotifications = true;
                    } else if ($clock_in_data['clock_in_time_status'] != 'OK') {
                        if ($clock_in_data['clock_in_time_status'] == 'EARLY CLOCK OUT') {
                            $m = _l('clock_out_early', [$first_name . ' ' . $last_name]);
                        } else {
                            $m = _l('clock_out_late', [$first_name . ' ' . $last_name]);
                        }
                        $employeeMsg = _l('clock_out_received');
                        $sentNotifications = true;
                    } else if ($clock_in_data['clock_in_location_status'] != 'OK') {
                        $m = _l('clock_out_wrong_location', [$first_name . ' ' . $last_name]);
                        $employeeMsg = _l('clock_out_received');
                        $sentNotifications = true;
                    } else {
                        $m = _l('clock_out_ok', [$first_name . ' ' . $last_name]);
                        $employeeMsg = _l('success_clock_out');
                    }
                    prepareMessage(array(array(_l('button_clock_in'))), '<strong>'.$employeeMsg.'</strong>');

                    if ($sentNotifications){
                        $clock_in_data['tgusername'] = $tgUsernameForChat;
                        generateClockOutStatusMSG($clock_in_data, $m);
                    }
                }
                elseif ($user_data['step'] == 'start_break_req_selfie') {
                    $break = $ezzeTeamsModel->updateMsgIdBreak($userId, $selfie_id);
                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'on_break'");
                    $user_data['step'] = 'on_break';
                    $r = getMsgByStep($user_data);
                    prepareMessage($r['keyboard'], _l('break_started'));
                }
                elseif ($user_data['step'] == 'end_break_req_selfie') {
                    $break = $ezzeTeamsModel->updateMsgIdBreak($userId, $selfie_id);
                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_in_done'");
                    $user_data['step'] = 'clock_in_done';
                    $r = getMsgByStep($user_data);
                    prepareMessage($r['keyboard'], _l('break_end'));
                }
                elseif ($user_data['step'] == 'start_visit_req_selfie'){
                    $visit = $ezzeTeamsModel->updateMsgIdVisit($userId, $selfie_id);
                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'start_visit_req_note'");
                    prepareMessage(null, _l('type_note'));
                }
                else {

                    $ezzeTeamsModel->updateClockInSet("clock_in_selfie_msg_id = '$selfie_id'");

                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_in_done'");
                    $clock_in_data = $ezzeTeamsModel->getClockIn("and is_clock_in='clock_in'", false);
                    $dateClock = date('H:i', strtotime($clock_in_data['created_at']));
                    $dateClockStr = strtotime($dateClock);
                    $workingHourData = $ezzeTeamsModel->getUserWorkingSchedule($userId);
                    $workStart = DateTime::createFromFormat('H:i', $workingHourData['start_time'])->format("H:i");
                    $workStartStr = strtotime($workStart) - ($botSettings['time_tolerance'] * 60);
                    if ($dateClockStr < $workStartStr) {
                        $ezzeTeamsModel->changeClockTimeStatus($clock_in_data['id'], 'EARLY CLOCK IN');
                        $clock_in_data['clock_in_time_status'] = 'EARLY CLOCK IN';
                    }

                    if ($botSettings['dead_man_feature'] == 1 && $user_data['ping_module'] == 1) {
                        initDeadManFeature($userId, $botSettings['dead_man_task_time']);
                    }

                    $sentNotifications = false;
                    if ($clock_in_data['clock_in_location_status'] != 'OK' && $clock_in_data['clock_in_time_status'] != 'OK') {
                        if ($clock_in_data['clock_in_time_status'] == 'EARLY CLOCK IN') {
                            $m = _l('clock_in_wrong_and_early', [$first_name . ' ' . $last_name]);
                        } else {
                            $m = _l('clock_in_wrong_and_late', [$first_name . ' ' . $last_name]);
                        }
                        $employeeMsg = _l('clock_in_received');
                        $sentNotifications = true;
                    } else if ($clock_in_data['clock_in_time_status'] != 'OK') {
                        if ($clock_in_data['clock_in_time_status'] == 'EARLY CLOCK IN') {
                            $m = _l('clock_in_early', [$first_name . ' ' . $last_name]);
                        } else {
                            $m = _l('clock_in_late', [$first_name . ' ' . $last_name]);
                        }
                        $employeeMsg = _l('clock_in_received');
                        $sentNotifications = true;
                    } else if ($clock_in_data['clock_in_location_status'] != 'OK') {
                        $m = _l('clock_in_wrong_location', [$first_name . ' ' . $last_name]);
                        $employeeMsg = _l('clock_in_received');
                        $sentNotifications = true;
                    } else {
                        $m = _l('clock_in_ok', [$first_name . ' ' . $last_name]);
                        $employeeMsg = _l('clock_in_success');
                    }
                    $user_data['step'] = 'clock_in_done';
                    $r = getMsgByStep($user_data);
                    prepareMessage($r['keyboard'], $employeeMsg);

                    if ($sentNotifications) {
                        $clock_in_data['tgusername'] = $tgUsernameForChat;
                        generateClockInStatusMSG($clock_in_data, $m);
                    }
                }

                $v = (int) 1;
                $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);
                exit;
            } else if ($user_data['step'] == 'register_req_photo') {
                $ezzeTeamsModel->updateUserPhoto($result->message->message_id);
                $ezzeTeamsModel->updateUserPhotoID($result->message->photo[0]->file_id);
                $ezzeTeamsModel->updateUserWhere($userId, "step = 'register_req_id'");
                prepareMessage(array(array(_l('skip_and_send')), array(_l('button_cancel'))), _l('registration_enter_id'));
                exit;
            } else {
                //$r = getLastStatusByData($user_data['user_id']);
                prepareMessage(null, _l('command_not_exists'));
                exit;
            }
        } else {
            prepareMessage(array(array(_l('button_back_home'))), _l('restricted'));
            exit;
        }
    }

    else if (strtolower($action) == strtolower(_l('my_schedule')) || strtolower($action) == strtolower(_l('button_my_schedule')) || strtolower($action) == strtolower(_l('menu_schedule'))) {
        $user_data = $ezzeTeamsModel->getUser('', false);
        if ($user_data['approval_status'] == 'approved') {
            $msg = _l('admin_info_work_location').' '.$user_data['branch_name'];
            $msg .= "_" . _l('admin_info_map') . " " . _l('button_show_work_location');
            $msg .= generateWorkingSchedule($userId);
            prepareMessage(null, $msg);exit;
        } else {
            prepareMessage(array(array(_l('button_back_home'))), _l('restricted'));
        }
    }

    else if (strtolower($action) == strtolower(_l('menu_edit_profile'))) {
        prepareMessage(null, _l('menu_coming_soon_desc'));
        exit;
    }

    else if (strtolower($action) == strtolower(_l('button_show_work_location')) || strtolower($action) == strtolower(_l('menu_show_work_location'))) {
        $branch = $ezzeTeamsModel->getEmployeeBranchByUserId($userId);
        prepareLocationMessage([$userId], $branch['branch_lat'], $branch['branch_lon']);
        exit;
    }

    else if (strtolower($action) == strtolower(_l('clock_out_now')) || strtolower($action) == strtolower(_l('button_yes')) || strtolower($action) == strtolower(_l('button_no'))) {
        $user_data = $ezzeTeamsModel->getUser('', false);
        if ( strtolower($action) == strtolower(_l('clock_out_now')) || strtolower($action) == strtolower(_l('button_yes')) ) {
            $m = $ezzeTeamsModel->getLastClock2($userId);
            $canClockOut = true;
            if ($m == NULL || $m['is_clock_in'] == 'clock_out') {
                $v = (int) 1;
                $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);
                $r = getLastStatusByData($user_data['user_id']);

                prepareMessage($r['keyboard'], _l('not_clock_in'));
            } else {
                $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_out_live_location'");
                prepareMessage(null, _l('share_live_location'));
            }
            exit;
        } else {
            $v = (int) 1;
            $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);
            $r = getLastStatusByData($user_data['user_id']);
            prepareMessage($r['keyboard'], _l('thank_you'));
            exit;
        }
    }

    else if (strtolower($action) == strtolower(_l('button_clock_in')) || strtolower($action) == strtolower(_l('button_clock_out'))) {
        $user_data = $ezzeTeamsModel->getUser('', false);
        if (!$user_data['is_step_complete']) {
            prepareMessage(null, _l('not_completed_step'));exit;
        }

        if (count($user_data) > 0 && $user_data['approval_status'] == 'approved') {
            if (isTimeWorkingNow($user_data['user_id'])) {
                if (strtolower($action) == strtolower(_l('button_clock_out'))) {
                    if ($user_data['step'] != 'clock_in_done' && $user_data['step'] != 'on_break' && $user_data['step'] != 'on_visit' ) {
                        prepareMessage(null, _l('not_clock_in'));exit;
                    } else if ($ezzeTeamsModel->isClockOut() < 1) {
                        //$ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_out_live_location'");
                        $v = (int) 0;
                        $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);
                        $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_out_yes_no'");
                        $keyboard_config['resize'] = true;
                        $keyboard_config['one_time'] = true;
                        $keyboard_config['force_reply'] = true;
                        prepareMessage(array(array(_l('button_yes')), array(_l('button_no'))), _l('clock_out_confirmation'), null, null, array($userId), $keyboard_config);
                        exit;
                    } else {
                        $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_out_done'");
                        $user_data['step'] = 'clock_out_done';
                        $r = getMsgByStep($user_data);
                        prepareMessage($r['keyboard'], _l('already_clock_out'));
                        exit;
                    }
                } else if (strtolower($action) == strtolower(_l('button_clock_in'))) {
                    if ($ezzeTeamsModel->isClockIn() < 1) {
                        $v = (int) 0;
                        $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);
                        $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_in_live_location'");
                    } else {
                        $r = getMsgByStep($user_data);
                        prepareMessage(generateClockInBtn(), _l('already_clock_in'));
                        exit;
                    }
                }
                prepareMessage(null, _l('share_live_location'));
            } else {
                if (strtolower($action) == strtolower(_l('button_clock_in'))) {
                    prepareMessage(null, _l('failed_clock_in'));
                } else {
                    prepareMessage(null, _l('failed_clock_out'));
                }
            }
        } else {
            prepareMessage(array(array(_l('button_back_home'))), _l('restricted'));
        }

    }

    else if (strtolower($action) == strtolower(_l('button_start_break')) || strtolower($action) == strtolower(_l('button_end_break'))) {
        $user_data = $ezzeTeamsModel->getUser('', false);
        $r = getMsgByStep($user_data);
        if ($botSettings['module_break'] == 1) {
            if (!$user_data['is_step_complete']) {
                prepareMessage(null, _l('not_completed_step'));
                exit;
            }

            if (count($user_data) > 0 && $user_data['approval_status'] == 'approved') {
                if ($user_data['can_break'] == 1) {
                    $v = (int)0;
                    $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);

                    if (strtolower($action) == strtolower(_l('button_start_break'))) {
                        if ($user_data['break_step'] == 1) {
                            if ($user_data['step'] == 'clock_in_done' || $user_data['step'] == 'start_break_req_location' || $user_data['step'] == 'start_break_req_selfie') {
                                $ezzeTeamsModel->updateUserWhere($userId, "step = 'start_break_req_location'");
                                prepareMessage(null, _l('share_live_location'));
                            } elseif ($user_data['step'] == 'on_break') {
                                $r = getMsgByStep($user_data);
                                prepareMessage($r['keyboard'], _l('already_break'));
                            } else {
                                prepareMessage(array(array(_l('button_clock_in'))), _l('not_clock_in'));
                            }
                        } else {
                            $ezzeTeamsModel->updateUserWhere($userId, "step = 'on_break'");
                            $params = [];
                            $params['user_id'] = $userId;
                            $params['day'] = date('D');
                            $params['break_time'] = date("H:i:s");
                            $params['location_status'] = "BOT SETTING DISABLED";
                            $params['location_lat'] = "BOT SETTING DISABLED";
                            $params['location_lon'] = "BOT SETTING DISABLED";
                            $params['location_msg_id'] = "BOT SETTING DISABLED";
                            $params['location_distance'] = "BOT SETTING DISABLED";
                            $params['selfie_msg_id'] = "";
                            $params['action'] = "start_break";
                            $ezzeTeamsModel->userBreak($params);
                            $user_data['step'] = 'on_break';
                            $r = getMsgByStep($user_data);
                            prepareMessage($r['keyboard'], _l('start_break'));
                            exit;
                        }
                    } elseif (strtolower($action) == strtolower(_l('button_end_break'))) {
                        if ($user_data['break_step'] == 1) {
                            if ($user_data['step'] == 'on_break' || $user_data['step'] == 'end_break_req_location' || $user_data['step'] == 'end_break_req_selfie') {
                                $ezzeTeamsModel->updateUserWhere($userId, "step = 'end_break_req_location'");
                                prepareMessage(null, _l('share_live_location'));
                            } elseif ($user_data['step'] == 'clock_out_done') {
                                prepareMessage(array(array(_l('button_clock_in'))), _l('not_clock_in'));
                            } else {
                                prepareMessage(null, _l('not_in_break'));
                            }
                        } else {
                            $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_in_done'");
                            $params = [];
                            $params['user_id'] = $userId;
                            $params['day'] = date('D');
                            $params['break_time'] = date("H:i:s");
                            $params['location_status'] = "BOT SETTING DISABLED";
                            $params['location_lat'] = "BOT SETTING DISABLED";
                            $params['location_lon'] = "BOT SETTING DISABLED";
                            $params['location_msg_id'] = "BOT SETTING DISABLED";
                            $params['location_distance'] = "BOT SETTING DISABLED";
                            $params['selfie_msg_id'] = "";
                            $params['action'] = "end_break";
                            $ezzeTeamsModel->userBreak($params);
                            $user_data['step'] = 'clock_in_done';
                            $r = getMsgByStep($user_data);
                            prepareMessage($r['keyboard'], _l('success_break'));
                            exit;
                        }
                    } else {
                        prepareMessage(null, _l('command_not_exists'));
                    }
                } else {
                    prepareMessage(null, _l('employee_disabled_module'));
                }

            } else {
                prepareMessage(null, _l('restricted'));
            }
        } else {
            prepareMessage(null, _l('restricted_module'));
        }
    }

    else if (strtolower($action) == strtolower(_l('button_start_visit')) || strtolower($action) == strtolower(_l('button_end_visit'))) {
        $user_data = $ezzeTeamsModel->getUser('', false);
        $r = getMsgByStep($user_data);
        if ($botSettings['module_visit'] == 1) {
            if (!$user_data['is_step_complete']) {
                prepareMessage(null, _l('not_completed_step'));
                exit;
            }
            if (count($user_data) > 0 && $user_data['approval_status'] == 'approved') {
                if ($user_data['can_visit'] == 1) {
                    $v = (int)0;
                    $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);

                    if (strtolower($action) == strtolower(_l('button_start_visit'))) {
                        if ($user_data['step'] == 'clock_in_done' || $user_data['step'] == 'start_visit_req_location' || $user_data['step'] == 'start_visit_req_selfie') {
                            $ezzeTeamsModel->updateUserWhere($userId, "step = 'start_visit_req_location'");
                            prepareMessage(null, _l('share_live_location'));
                        } elseif ($user_data['step'] == 'on_visit') {
                            $r = getMsgByStep($user_data);
                            prepareMessage($r['keyboard'], _l('already_visit'));
                        } else {
                            prepareMessage(array(array(_l('button_clock_in'))), _l('not_clock_in'));
                        }
                    } elseif (strtolower($action) == strtolower(_l('button_end_visit'))) {
                        if ($user_data['step'] == 'on_visit') {
                            $ezzeTeamsModel->updateUserWhere($userId, "step = 'end_visit_req_location'");
                            prepareMessage(null, _l('share_live_location'));
                            //prepareMessage(null, json_encode($user_data));exit;
                        } else {
                            $r = getMsgByStep($user_data);
                            prepareMessage($r['keyboard'], _l('not_in_visit'));
                        }
                    } else {
                        prepareMessage(null, _l('command_not_exists'));
                    }
                } else {
                    prepareMessage(null, _l('employee_disabled_module'));
                }
            } else {
                prepareMessage(null, _l('restricted'));
            }
        } else {
            prepareMessage(null, _l('restricted_module'));
        }
    }

    /** reminder feature */
    else if (strtolower($action) == strtolower(_l('button_remind_later'))){
        $dateNow = date("Y-m-d H:i");
        $reminderData = $ezzeTeamsModel->getLastReminderNeedReply($dateNow);
        $user_data = $ezzeTeamsModel->getUser('', false);
        $r = getMsgByStep($user_data);
        if (is_array($reminderData) && count($reminderData) > 1) {
            $data['id'] = $reminderData['id'];
            $data['reply'] = 1;
            $data['response'] = $action;
            $ezzeTeamsModel->setReminderResponse($data);

            $msg = _l('remind_me_again', [$botSettings['clockout_reminder_interval']]);
            prepareMessage($r['keyboard'], $msg);
        } else {
            prepareMessage(null, "Already Mark as No Response");
            //prepareMessage($r['keyboard'], "Already Mark as No Response");
        }
    }

    /*** Step 6:
     * Share Selfie -> Request Input ID/Code/Email Employee **/
    else if (isset($action)) {
        $user_data = $ezzeTeamsModel->getUser('', false);
        if (count($user_data) > 0 && $user_data['approval_status'] != 'rejected') {
            if ($user_data['step'] == 'register_req_photo') {
                prepareMessage(null, _l('need_share_selfie'));
            }
            else if ($user_data['step'] == 'register_req_id') {
                $ezzeTeamsModel->updateUserWhere($userId, "approval_status = null");

                /**  update employee ID and get all data from user to send message */
                $ezzeTeamsModel->updateUserEmail($action);

                /**  send success message after finish registration */
                prepareMessage(array(array(_l('button_back_home'))), _l('success_registration'));

                $ezzeTeamsModel->updateUserWhere($userId, "step = 'waiting'");

                /**  send notification message to admin after user submit registration */
                foreach ($admin_id as $id) {
                    $user_data_notification = $ezzeTeamsModel->getUserNotification($id);
                    if (isset($user_data_notification)) {
                        deleteMessage($user_data_notification['notification_new_user_msg_id'], $id);
                    }
                }

                prepareMessage(array(array(_l('button_show_user_table'), _l('button_show_employee'))), getReceivedNewApplicationNotificationTxt(), null, null, $admin_id);
                //    foreach ($admin_id as $id) {
                //        $ezzeTeamsModel->updateUserNotificationNewUserMSGID();
                //    }
            }
            elseif ($user_data['step'] == 'start_visit_req_note') {
                $visit_id = $ezzeTeamsModel->updateNoteVisit($userId, $action);
                $ezzeTeamsModel->updateUserWhere($userId, "step = 'on_visit'");
                $user_data['step'] = 'on_visit';
                $v = (int) 1;
                $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);

                $r = getMsgByStep($user_data);
                prepareMessage($r['keyboard'], _l('start_visit'));
                if ($user_data['can_visit'] == 1 && $user_data['visit_alert'] == 1) {
                    generateVisitMsg($visit_id, $tgUsernameForChat);
                }
            }
            elseif ($user_data['step'] == 'end_visit_req_note') {
                $visit_id = $ezzeTeamsModel->updateNoteVisit($userId, $action);
                $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_in_done'");
                $user_data['step'] = 'clock_in_done';
                $v = (int) 1;
                $ezzeTeamsModel->changeCompleteUserStep($user_data['user_id'], $v);

                $r = getMsgByStep($user_data);
                prepareMessage($r['keyboard'], _l('end_visit'));
                if ($user_data['can_visit'] == 1 && $user_data['visit_alert'] == 1) {
                    generateVisitMsg($visit_id, $tgUsernameForChat);
                }
            }
            elseif ($user_data['approval_status'] == 'inactive') {
                prepareMessage(array(array(_l('button_back_home'))), _l('restricted'));
            } else {
                prepareMessage(null, _l('command_not_exists'));
            }
        } else {
            prepareMessage(array(array(_l('button_back_home'))), _l('restricted'));
        }
    }

    else if (isset($result->message->location->live_period)) {
        $user_data = $ezzeTeamsModel->getUser('', false);

        if (count($user_data) > 0 && $user_data['approval_status'] == 'approved') {
            $tasks = $ezzeTeamsModel->getDailyTaskThisTime($user_data['user_id'], $botSettings['dead_man_task_time']);
            $missedTask = $ezzeTeamsModel->getLastMissedDailyTask($user_data['user_id']);
            if ($user_data['step'] == 'clock_in_live_location' || $user_data['step'] == 'clock_out_live_location' ||
                $user_data['step'] == 'start_break_req_location' || $user_data['step'] == 'end_break_req_location' ||
                $user_data['step'] == 'start_visit_req_location' || $user_data['step'] == 'end_visit_req_location') {
                $is_clock_out = false;
                $user_step = $user_data['step'];
                $clock_in_day = date('D');
                $location_status = 'WRONG LOCATION';
                $location_msg_id = $result->message->message_id;
                $clock_in_time_status = 'LATE';
                $clock_in_time = date('H:i');
                $bot_setting = $ezzeTeamsModel->getSettings();
                $time_tolerance = "+" . $bot_setting['time_tolerance'] . " minutes";
                $get_work_start_time = $ezzeTeamsModel->getWorkday($userId, '', false, "and work_day = '$clock_in_day'")[0]['start_time'];
                $user_start_time = strtotime($get_work_start_time . $time_tolerance);
                $current_time = strtotime(date("H:i"));
                $lat = $result->message->location->latitude;
                $lon = $result->message->location->longitude;

                if ($user_step == 'clock_out_live_location') {

                    $clock_in_time_status = 'EARLY CLOCK OUT';
                    //$get_work_start_time = $ezzeTeamsModel->getWorkday($userId, '', false, "and work_day ='$clock_in_day'")[0]['end_time'];
                    $wTime = isTimeWorkingNow($user_data['user_id'], true);
                    $get_work_start_time = $wTime['end_time'];
                    $user_start_time = strtotime($get_work_start_time);

                    if ($current_time >= $user_start_time) {
                        $clock_in_time_status = 'OK';
                    }
                } else {
                    /**
                     * Check Time Tolerance
                     */
                    if ($current_time <= $user_start_time) {
                        $clock_in_time_status = 'OK';
                    }
                }

                /**
                 * Check Location Tolerance
                 */
                $loc_tolerance = checkLocationTolerance($lat, $lon);
                if ($loc_tolerance[0]) {
                    $location_status = 'OK';
                }

                if ($user_step == 'clock_out_live_location') {
                    /**
                     * Insert Location and Time Data in Database
                     */
                    if ($ezzeTeamsModel->isClockOut() <= 0) {
                        $ezzeTeamsModel->insertClockIn($clock_in_day, $location_status, $location_msg_id, $clock_in_time_status, $clock_in_time, $get_work_start_time, $loc_tolerance[1], $lat, $lon, 'clock_out');
                    }

                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_out_share_selfie'");
                }
                elseif ($user_step == 'start_break_req_location') {
                    $params = [];
                    $params['user_id'] = $userId;
                    $params['day'] = $clock_in_day;
                    $params['break_time'] = date("H:i:s");
                    $params['location_status'] = $location_status;
                    $params['location_lat'] = $lat;
                    $params['location_lon'] = $lon;
                    $params['location_msg_id'] = $location_msg_id;
                    $params['location_distance'] = $loc_tolerance[1];
                    $params['selfie_msg_id'] = "";
                    $params['action'] = "start_break";
                    $ezzeTeamsModel->userBreak($params);
                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'start_break_req_selfie'");
                }
                elseif ($user_step == 'end_break_req_location') {
                    $params = [];
                    $params['user_id'] = $userId;
                    $params['day'] = $clock_in_day;
                    $params['break_time'] = date("H:i:s");
                    $params['location_status'] = $location_status;
                    $params['location_lat'] = $lat;
                    $params['location_lon'] = $lon;
                    $params['location_msg_id'] = $location_msg_id;
                    $params['location_distance'] = $loc_tolerance[1];
                    $params['selfie_msg_id'] = "";
                    $params['action'] = "end_break";
                    $ezzeTeamsModel->userBreak($params);
                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'end_break_req_selfie'");
                }
                elseif ($user_step == 'start_visit_req_location') {
                    $params = [];
                    $params['user_id'] = $userId;
                    $params['visit_day'] = $clock_in_day;
                    $params['visit_time'] = date("Y-m-d H:i:s");
                    $params['visit_lat'] = $lat;
                    $params['visit_lon'] = $lon;
                    $params['visit_location_msg_id'] = $location_msg_id;
                    $params['selfie_msg_id'] = "";
                    $params['visit_notes'] = "";
                    $params['action'] = "start_visit";
                    $ezzeTeamsModel->userVisit($params);
                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'start_visit_req_selfie'");
                }
                elseif ($user_step == 'end_visit_req_location') {
                    $params = [];
                    $params['user_id'] = $userId;
                    $params['visit_day'] = $clock_in_day;
                    $params['visit_time'] = date("Y-m-d H:i:s");
                    $params['visit_lat'] = $lat;
                    $params['visit_lon'] = $lon;
                    $params['visit_location_msg_id'] = $location_msg_id;
                    $params['selfie_msg_id'] = "";
                    $params['visit_notes'] = "";
                    $params['action'] = "end_visit";
                    $ezzeTeamsModel->userVisit($params);
                    $ezzeTeamsModel->updateUserWhere($userId, "step = 'end_visit_req_note'");
                    prepareMessage(null, _l('type_note'));exit;
                }
                else {
                    /**
                     * Insert Location and Time Data in Database
                     */
                    if ($ezzeTeamsModel->isClockIn() <= 0) {
                        $abs = $ezzeTeamsModel->getAllClockDataByUserListsAndDate($userId, date("Y-m-d"), 'absent');
                        if (isset($abs['id'])) {
                            $params = [];
                            $params['location_status'] = $location_status;
                            $params['location_lat'] = $lat;
                            $params['location_lon'] = $lon;
                            $params['location_msg_id'] = $location_msg_id;
                            $params['location_distance'] = $loc_tolerance[1];
                            $params['time_status'] = $clock_in_time_status;
                            $params['clocked_time'] = $clock_in_time;
                            $params['work_time'] = $get_work_start_time;
                            $params['action'] = "clock_in";
                            $params['data_id'] = $abs['id'];
                            $ezzeTeamsModel->changeClockAbsent($params);
                        } else {
                            $ezzeTeamsModel->insertClockIn($clock_in_day, $location_status, $location_msg_id, $clock_in_time_status, $clock_in_time, $get_work_start_time, $loc_tolerance[1], $lat, $lon, 'clock_in');
                        }
                        $ezzeTeamsModel->updateUserWhere($userId, "step = 'clock_in_req_selfie'");
                    }
                }

                prepareMessage(null, _l('please_send_selfie'));
            }
            elseif (($tasks > 0 || $missedTask > 0) && $botSettings['dead_man_feature']) {
                $params = [];
                $params['task_reply'] = (bool) 1;
                if ($tasks > 0) {
                    $params['task_status'] = 'OK';
                } else {
                    $params['task_status'] = 'MISS';
                }
                $params['reply_time'] = date("Y-m-d H:i:s");
                //lat & lon
                $lat = $result->message->location->latitude;
                $lon = $result->message->location->longitude;
                $params['reply_location'] = $lat.", ".$lon;
                //location status
                $location_status = 'WRONG LOCATION';
                $loc_tolerance = checkLocationTolerance($lat, $lon);
                if ($loc_tolerance[0]) {
                    $location_status = 'OK';
                }
                $params['reply_location_status'] = $location_status;
                $params['reply_location_distance'] = $loc_tolerance[1];
                $params['reply_location_msg_id'] = $result->message->message_id;
                prepareMessage(null, _l('deadman_task_received'));
                if ($tasks > 0) {
                    $ezzeTeamsModel->updateDailyTask($tasks['id'], $params);
                    if ($location_status == 'WRONG LOCATION') {
                        //notify admin wrong location

                        $lastname = ($user_data['lastname'] == '')? 'N/A' : $user_data['lastname'];
                        $phone = ($user_data['phone'] == '')? 'N/A' : $user_data['phone'];
                        $eId = ($user_data['email'] == '' || $user_data['email'] == 'Skip and Send')? 'N/A' : $user_data['email'];
                        $distance = ($tasks['reply_location_distance'] < 1)? ($tasks['reply_location_distance'] * 1000)." m" : $tasks['reply_location_distance']." km";

                        $msg = "<strong>" . $user_data['firstname'] . " " . $user_data['lastname'] . "</strong> " . _l('failed_ping_tasks') .
                            "_". _l('admin_info_ping_time') .date("H:i", strtotime($tasks['task_start'])) . " - " . date("H:i", strtotime($tasks['task_end'])) .
                            "__" . _l('admin_info_job_description') . $user_data['jobdesc'] .
                            "_" . _l('admin_info_work_location') . $user_data['branch_name'] .
                            "__" . _l('admin_info_first_name') . $user_data['firstname'] .
                            "_". _l('admin_info_last_name') . $lastname .
                            "_". _l('admin_info_phone_number') . $phone .
                            "_". _l('admin_info_employee_id') . $eId .
                            "__". _l('admin_info_distance') . $distance .
                            "_". _l('admin_info_map') . "/showMapPing".$tasks['id'] .
                            "_" . _l('admin_info_employee') . "/viewEmployee".$user_data['user_id'];
                        /*
                        $msg = "<strong>".$user_data['firstname']." ".$user_data['lastname']." Sent Wrong Location Task:</strong>" .
                            "__<strong>Task Time: </strong>" . date("H:i", strtotime($tasks['task_start'])) ." - ". date("H:i", strtotime($tasks['task_end'])) .
                            "_<strong>Location: </strong>";*/

                        prepareMessage(null, $msg, null,  null, $admin_id);
                        //prepareLocationMessage($admin_id, $lat, $lon);
                    }
                } /*elseif ($missedTask > 0){
                    $ezzeTeamsModel->updateDailyTask($missedTask['id'], $params);

                    //notify admin user missed the task
                    $msg = "<strong>".$user_data['firstname']." ".$user_data['lastname']." Miss Him/Her Task:</strong>" .
                        "__<strong>Task Time: </strong>" . date("H:i", strtotime($missedTask['task_start'])) ." - ". date("H:i", strtotime($missedTask['task_end']));
                    prepareMessage(null, $msg, null, null, $admin_id);
                }*/
            }
            else {
                prepareMessage(null, _l('select_action'));
            }
        } else {
            prepareMessage(array(array(_l('button_back_home'))), _l('restricted'));
        }
    }

    else if ( isset($result->message->location->latitude) && isset($result->message->location->longitude) ) {
        $user_data = $ezzeTeamsModel->getUser('', false);
        if ($user_data['step'] == 'clock_in_live_location' || $user_data['step'] == 'clock_out_live_location' ||
            $user_data['step'] == 'share_live_location' || $user_data['step'] == 'start_break_req_location' ||
            $user_data['step'] == 'end_break_req_location' ) {
            prepareMessage(null, _l('use_live_not_share_location'));
        } else {
            prepareMessage(null, _l('command_not_exists'));
        }
    }
}
exit;
?>