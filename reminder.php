<?php
include "load.php";

$ezzeTeamsModel = new EzzeTeamsModel();

if (strtolower(strip_tags($_GET['command'])) == 'clock-out') {
    $dateNow = date("Y-m-d H:i");
    $lists = $ezzeTeamsModel->getReminderNow($dateNow);
    foreach ($lists as $list) {
        if ($list['reminder_num'] > 1) {
            if (file_exists(__DIR__ . "/includes/language/" . $list['lang'] . ".php")) {
                include_once __DIR__ . "/includes/language/" . $list['lang'] . ".php";
            } else {
                include_once __DIR__ . "/includes/language/en.php";
            }
            if ($list['reminder_num'] < $botSettings['max_reminder']) {
                $canReply = isLastFiveReminderNotReplyByUser($list['user_id'], $dateNow);
                /*if (!$canReply) {
                    //send note force clock out cause not reply 5 times
                    $btn = generateButtonClockOutNow();
                    prepareMessage($btn, _l('remind_force_clock_out_not_reply'), null, null, [$list['user_id']]);
                } else {*/
                    $btn = null;
                    if ($list['reminder_button'] != '') {
                        $btn = json_decode($list['reminder_button']);
                    }
                    prepareMessage($btn, $list['reminder_msg'], null, null, [$list['user_id']]);
                    $ezzeTeamsModel->setReminderSent($list['id']);
                //}
            } else {
                //send last reminder
                $ezzeTeamsModel->setReminderSent($list['id']);
                $btn = generateButtonClockOutNow();
                prepareMessage($btn, _l('remind_clockout_last'), null, null, [$list['user_id']]);
            }
        }
    }
    
    //check for clock out if not respond force to clock out
    $lists = $ezzeTeamsModel->getEndReminderNow($dateNow);
    foreach ($lists as $list) {
        if ($list['type'] == 'clock_out') {
            if (file_exists(__DIR__ . "/includes/language/" . $list['lang'] . ".php")) {
                include_once __DIR__ . "/includes/language/" . $list['lang'] . ".php";
            } else {
                include_once __DIR__ . "/includes/language/en.php";
            }

            $data['user_id'] = $list['user_id'];
            $data['clock_in_day'] = date("D");
            $data['clock_in_time_status'] = "FAILED CLOCK OUT";
            $data['is_clock_in'] = "clock_out";
            $data['created_at'] = date("Y-m-d H:i:s");

            if ($list['reminder_num'] >= $botSettings['max_reminder']) {
                $btn = generateClockInBtn();
                forceClockOut($data);
                prepareMessage($btn, _l('remind_force_clock_out'), null, null, [$list['user_id']]);
            } else {
                /*$canReply = isLastFiveReminderNotReplyByUser($list['user_id'], $dateNow);
                if (!$canReply) {
                    //send note force clock out cause not reply 5 times
                    $btn = generateButtonClockOutNow();
                    forceClockOut($data);
                    prepareMessage(generateClockInBtn(), _l('remind_force_clock_out_not_reply'), null, null, [$list['user_id']]);
                }*/
                forceClockOut($data);
                prepareMessage(generateClockInBtn(), _l('remind_force_clock_out'), null, null, [$list['user_id']]);
            }
        }
    }
}