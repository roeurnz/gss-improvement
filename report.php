<?php
include "load.php";

$ezzeTeamsModel = new EzzeTeamsModel();

if (strtolower(strip_tags($_GET['command'])) == 'daily-presence') {
    if ($botSettings['module_alert'] == 1) {
        $lists = $ezzeTeamsModel->getAbsentDailyToday($botSettings['time_tolerance']);
        $i = 0;
        if (count($lists) > 0) {
            $msg = "<strong><u>List of Absent Employee </u></strong>";

            $alreadyAbsent = $ezzeTeamsModel->getUserAlreadyAbsentToday();
            $n = 0;
            foreach ($alreadyAbsent as $u) {
                if ($u['trigger_alarm'] == 1) {
                    $i++;
                    $msg .= "__<strong>" . $i . "</strong>. " . $u['firstname'] . ' ' . $u['lastname']. ' /viewEmployee'.$u['user_id'];
                }
            }

            foreach ($lists as $list) {
                if ($list['trigger_alarm'] == 1) {
                    $i++;
                    $n++;
                    $msg .= "__<strong>" . $i . "</strong>. " . $list['firstname'] . ' /viewEmployee'.$list['user_id'];
                }
                $params = [];
                $params['user_id'] = $list['user_id'];
                $params['clock_in_day'] = date("D");
                $params['is_clock_in'] = 'absent';
                $ezzeTeamsModel->insertUserAbsent($params);
            }
            if ($n > 0) {
                prepareMessage(null, $msg, null, null, $admin_id);
            }
            return true;
        }
    }
} elseif (strtolower(strip_tags($_GET['command'])) == 'daily-clock-out') {
    $lists = $ezzeTeamsModel->getNotClockOut($botSettings['time_tolerance']);
    $i = 0;
    if ( count($lists) > 0 ) {
        $msg  = "<strong><u>List of Not Clocked-Out Employee </u></strong>";
        foreach ($lists as $list) {
            $i++;
            $msg .= "__<strong>" . $i . "</strong>. " . $list['firstname'] . ' ' . $list['lastname'] . ' /viewEmployee'.$list['user_id'];
            if (file_exists(__DIR__ ."/includes/language/".$list['lang'].".php")) {
                include_once __DIR__ ."/includes/language/".$list['lang'].".php";
            } else {
                include_once __DIR__ ."/includes/language/en.php";
            }
            $e_msg = _l('reminder_to_clock_out');
            $btn = generateButtonReminder();
            //send 1st reminder to employee
            $dateClockOutSchedule = strtotime(date("Y-m-d H:i:s", strtotime($list['end_time'])));
            prepareMessage($btn, $e_msg, null, null, [$list['user_id']]);
            //create reminder schedule
            createReminder($list['user_id'], 'clock_out', $botSettings['max_reminder'], $dateClockOutSchedule, $e_msg, $btn);
        }
        if ($i > 0) {
            prepareMessage(null, $msg, null, null, $admin_id);
        }
        return true;
    }
} elseif (strtolower(strip_tags($_GET['command'])) == 'attendance') {
    if (strtolower(strip_tags($_GET['time'])) == 'daily'){
        $dateStart = date("Y-m-d");
        $dateEnd = date("Y-m-d");
        $branches = $ezzeTeamsModel->getAllBranch();
        foreach($branches as $branch){
            $msg = "Hi Admin, This Report Daily Attendance ".$branch['branch_name'];
            $cs = getReportByDate($branch['branch_id'], $branch['branch_name'], $dateStart, $dateEnd);

            prepareMessage(null, $msg, null, 'sendDocument', $admin_id, null, false, null, null, true, $cs);
            unlink($cs);
        }
    } elseif (strtolower(strip_tags($_GET['time'])) == 'weekly') {
        $dateStart = date("Y-m-d", strtotime("last week monday"));
        $dateEnd = date("Y-m-d", strtotime("last week sunday"));
        $branches = $ezzeTeamsModel->getAllBranch();
        foreach($branches as $branch){
            $msg = "Hi Admin, This Report Weekly Attendance ".$branch['branch_name']." From ".date("d/m/Y", strtotime($dateStart))." To ".date("d/m/Y", strtotime($dateEnd));
            $cs = getReportByDate($branch['branch_id'], $branch['branch_name'], $dateStart, $dateEnd);

            prepareMessage(null, $msg, null, 'sendDocument', $admin_id, null, false, null, null, true, $cs);
            unlink($cs);
        }
    } elseif (strtolower(strip_tags($_GET['time'])) == 'monthly') {
        $dateStart = date("Y-m-d", strtotime("first day of last month"));
        $dateEnd = date("Y-m-d", strtotime("last day of last month"));
        $branches = $ezzeTeamsModel->getAllBranch();
        foreach($branches as $branch){
            $msg = "Hi Admin, This Report Monthly Attendance ".$branch['branch_name']." From ".date("d/m/Y", strtotime($dateStart))." To ".date("d/m/Y", strtotime($dateEnd));
            $cs = getReportByDate($branch['branch_id'], $branch['branch_name'], $dateStart, $dateEnd);

            prepareMessage(null, $msg, null, 'sendDocument', $admin_id, null, false, null, null, true, $cs);
            unlink($cs);
        }
    }
    return true;
}