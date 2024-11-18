<?php
include "load.php";

$ezzeTeamsModel = new EzzeTeamsModel();

//include __DIR__ ."/includes/language/en.php";

if ($botSettings['dead_man_feature']) {
    //send task to employee
    $dataTasksToSend = $ezzeTeamsModel->getTasksToSendTodayUseLang();
    //echo "<pre>";print_r($dataTasksToSend);die;
    foreach ($dataTasksToSend as $task) {
        $endTask = strtotime($task['task_end']);
        $timeNow = time();
        if ($endTask > $timeNow) {
            $msg = getLangByKeyAndId('dead_man_task_msg', $task['lang']);
            prepareMessage(null, $msg, null, null, [$task['user_id']]);
            $ezzeTeamsModel->markTaskSend($task['id']);
        } else {
            $ezzeTeamsModel->markTaskSend($task['id'], 2);
        }
    }

    include_once __DIR__ ."/includes/language/en.php";
    //notify admin if employee miss their task
    $lists = $ezzeTeamsModel->getTasksNotReplyToday();
    foreach ($lists as $list) {
        $userId = $list['user_id'];
        $user_data = $ezzeTeamsModel->getUser('', false);

        $lastname = ($user_data['lastname'] == '')? 'N/A' : $user_data['lastname'];
        $tgUsername = ($user_data['tg_username'] != '')? '@'.str_replace("_", "###", $user_data['tg_username']) : '' ;
        $phone = ($user_data['phone'] == '')? 'N/A' : $user_data['phone'];
        $eId = ($user_data['email'] == '' || $user_data['email'] == 'Skip and Send')? 'N/A' : $user_data['email'];
        $uMsg = getLangByKeyAndId('ping_test_missed', $user_data['lang']);
        prepareMessage(null, $uMsg, null, null, [$user_data['user_id']]);

        $msg = "<strong>" . $user_data['firstname'] . " " . $user_data['lastname'] . "</strong> " . _l('failed_ping_tasks') .
            "_". _l('admin_info_ping_time') .date("H:i", strtotime($list['task_start'])) . " - " . date("H:i", strtotime($list['task_end'])) .
            "__" . _l('admin_info_job_description') . $user_data['jobdesc'] .
            "_" . _l('admin_info_work_location') . $user_data['branch_name'] .
            "__" . _l('admin_info_first_name') . $user_data['firstname'] .
            "_". _l('admin_info_last_name') . $lastname .
            "_". _l('admin_info_tg_username') . $tgUsername .
            "_". _l('admin_info_phone_number') . $phone .
            "_". _l('admin_info_employee_id') . $eId .
            "__" . _l('admin_info_employee') . "/viewEmployee".$user_data['user_id'];

        $ezzeTeamsModel->markTaskSend($list['id'], 2);
        prepareMessage(null, $msg, null, null, $admin_id);
    }
}