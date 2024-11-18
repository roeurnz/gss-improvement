<?php
class EzzeTeamsModel
{
    function updateTgUsername($userId, $tgUsername) {
        global $mysqli;

        if (empty($userId)) {
            return false;
        }
        $sql = "UPDATE user_profiles SET 
                tg_username = ?
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $tgUsername, $userId);
        return $stmt->execute();
    }

    function incorrectReplyUserStatus($logID, $userStatus){
        global $mysqli;

        if (is_null($logID)) {
            return false;
        }

        $param = json_encode($userStatus, JSON_UNESCAPED_UNICODE);
        $sql = "UPDATE request_reply_log SET 
                wrong_reply_user_stat = ?
                WHERE  
                id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $param, $logID);
        return $stmt->execute();
    }

    function isUserIdExists($id){
        global $mysqli;

        $sql = "SELECT *
                FROM user_profiles
                WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function markMessageLastRun($dayNow, $timeNow) {
        global $mysqli;

        $sql = "UPDATE scheduled_messages m 
                INNER JOIN scheduled_messages_time t ON t.message_id = m.id
                SET 
                    m.last_run = ?,
                    t.is_run = 1
                WHERE 
                    t.day = ? AND t.time = ?
                    AND ((m.runtime = 1 AND m.last_run = '0000-00-00 00:00:00') OR (m.runtime = 2))
                ";
        $dateNow = date("Y-m-d H:i:s");
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sss', $dateNow, $dayNow, $timeNow);
        return $stmt->execute();
    }

    function getSMessageToSendNow($dayNow, $timeNow) {
        global $mysqli;

        $sql = "SELECT t.*, m.id as msg_id, m.title, m.destination, m.message, m.media_type, m.media, m.runtime
                FROM scheduled_messages_time t 
                LEFT JOIN scheduled_messages m ON m.id = t.message_id
                WHERE 
                t.day = ? AND t.time = ? AND is_run = 0 
                AND ((m.runtime = 1 AND m.last_run = '0000-00-00 00:00:00') OR (m.runtime = 2)) ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $dayNow, $timeNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function removeSMessage($id) {
        global $mysqli;

        $sql = "DELETE m.*, t.*
            FROM scheduled_messages m 
            LEFT JOIN scheduled_messages_time t ON t.message_id = m.id
            WHERE m.id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    function getScheduledMessageById($id) {
        global $mysqli;

        $sql = "SELECT m.*, u.firstname, u.lastname
                FROM scheduled_messages m
                LEFT JOIN user_profiles u ON m.created_by = u.user_id
                WHERE m.id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $msg = $result->fetch_assoc();
        if (!$msg) {
            return false;
        }

        $msg['schedule'] = [];
        $sql = "SELECT *
                FROM scheduled_messages_time
                WHERE message_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $msg['schedule'][] = $row;
        }
        return $msg;
    }

    function getAllscheduledMessage($offset, $limit){
        global $mysqli;

        $sql = "SELECT COUNT(*) FROM scheduled_messages";
        $stmt = $mysqli->query($sql);
        $num = $stmt->fetch_row();
        
        $page = ceil($num[0] / $limit);
            
        $sql = "SELECT id, title FROM scheduled_messages LIMIT ? OFFSET ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return ['data' => $result->fetch_all(MYSQLI_ASSOC), 'page' => $page];
    }

    function saveScheduledMessage($userId, $data) {
        global $mysqli;

        if ($data['type'] == 'addnew') {
            $sql = "INSERT INTO scheduled_messages SET
                title = ?,
                message = ?,
                destination = ?,
                media_type = ?,
                media = ?,
                runtime = ?,
                last_run = ?,
                created_by = ?,
                created_at = ?
                ";
            $dateNow = date("Y-m-d H:i:s");
            $lastRun = '0000-00-00';
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sssssisis', $data['title'], $data['message'], $data['destination'],
                $data['media_type'], $data['media'], $data['runtime'], $lastRun, $userId, $dateNow);
            $stmt->execute();
            $message_id = $stmt->insert_id;
            if (count($data['schedule']) > 0) {
                foreach($data['schedule'] as $schedule) {
                    $sql = "INSERT INTO scheduled_messages_time SET
                            message_id = ?,
                            day = ?,
                            time = ?
                            ";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param('iss', $message_id, $schedule['day'], $schedule['time']);
                    $stmt->execute();
                }
            }
            return $message_id;
        } else if ($data['type'] == 'edit') {
            $sql = "UPDATE scheduled_messages SET
                    title = ?,
                    message = ?,
                    destination = ?,
                    media_type = ?,
                    media = ?,
                    runtime = ?,
                    created_by = ?,
                    created_at = ?
                WHERE 
                    id = ?
                ";
            $dateNow = date("Y-m-d H:i:s");
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sssssiisi', $data['title'], $data['message'], $data['destination'],
                $data['media_type'], $data['media'], $data['runtime'], $userId, $dateNow, $data['id']);
            $stmt->execute();

            if (count($data['schedule']) > 0) {
                $sql = "DELETE FROM scheduled_messages_time WHERE message_id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('i', $data['id']);
                $stmt->execute();

                foreach($data['schedule'] as $schedule) {
                    $sql = "INSERT INTO scheduled_messages_time SET
                            message_id = ?,
                            day = ?,
                            time = ?
                            ";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param('iss', $data['id'], $schedule['day'], $schedule['time']);
                    $stmt->execute();
                }
            }
            return $data['id'];
        }
        return false;
    }

    function getUserIdAllApprovedUser(){
        global $mysqli;

        $sql = "SELECT user_id 
                FROM user_profiles
                WHERE approval_status = ? 
                ORDER BY id ASC";

        $approved = 'approved';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $approved);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getBreadCrumbDataByDate($dateStart, $dateEnd){
        global $mysqli;

        $sql = "SELECT created, day, u.user_id, u.firstname, u.lastname, b.branch_name, a_action as crumbs, lat, lon
            FROM (
                SELECT user_id, clock_in_day as day, is_clock_in as a_action, clock_in_lat as lat, clock_in_lon as lon, created_at as created
                FROM user_clock_in_out
                WHERE is_clock_in = 'clock_in' OR is_clock_in = 'clock_out'
                UNION ALL
                SELECT user_id, break_day as day, break_action as a_action, location_lat as lat, location_lon as lon, created_at as created
                FROM user_break
                WHERE break_action = 'start_break' 
                UNION ALL 
                SELECT user_id, visit_day as day, visit_action as a_action, visit_lat as lat, visit_lon as lon, created_at as created
                FROM user_visits
                WHERE visit_action = 'start_visit' 
            ) c
            LEFT JOIN user_profiles u ON u.user_id = c.user_id 
            LEFT JOIN branch b ON b.branch_id = u.branch_id
            WHERE u.approval_status = 'approved'
                AND NOT EXISTS (
                    SELECT ad.user_id
                    FROM bot_admin ad
                    WHERE c.user_id = ad.user_id
                )
                AND DATE_FORMAT(c.created, '%Y-%m-%d %H:%i') BETWEEN ? AND ? 
            ORDER BY c.created ASC ";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getAllPINGDataByDate($dateStart, $dateEnd) {
        global $mysqli;

        $sql = "SELECT c.*, u.firstname, u.lastname, b.branch_name
                FROM user_daily_tasks c
                LEFT JOIN user_profiles u ON u.user_id = c.user_id 
                LEFT JOIN branch b ON b.branch_id = u.branch_id
                WHERE u.approval_status = 'approved'
                    AND DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i') 
                BETWEEN ? AND ? 
                ORDER BY c.id ASC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getAllVisitDataByDate($dateStart, $dateEnd) {
        global $mysqli;

        $sql = "SELECT c.*, u.firstname, u.lastname, b.branch_name
                FROM user_visits c
                LEFT JOIN user_profiles u ON u.user_id = c.user_id 
                LEFT JOIN branch b ON b.branch_id = u.branch_id
                WHERE u.approval_status = 'approved'
                    AND DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i') 
                BETWEEN ? AND ? 
                ORDER BY c.id ASC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getAllBreakDataByDate($dateStart, $dateEnd){
        global $mysqli;

        $sql = "SELECT c.*, u.firstname, u.lastname, b.branch_name
                FROM user_break c
                LEFT JOIN user_profiles u ON u.user_id = c.user_id 
                LEFT JOIN branch b ON b.branch_id = u.branch_id
                WHERE u.approval_status = 'approved'
                    AND DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i') 
                BETWEEN ? AND ? 
                ORDER BY c.id ASC";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    function calculateUserVisitByDay($user_id, $date, $date2 = false){
        global $mysqli;

        $dateEnd = $date;
        if ($date2) {
            $dateEnd = $date2;
        }

        $sql = "SELECT *
        FROM user_visits 
        WHERE user_id = ? 
        AND DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') BETWEEN ? AND ?
        ORDER BY id ASC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iss', $user_id, $date, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $startTime = '';
        $totalTime = '00:00';
        $totalVisit = 0;
        foreach($res as $r){
            if ($r['visit_action'] == 'start_visit') {
                $startTime = $r['visit_time'];
                $totalVisit++;
            } elseif ($r['visit_action'] == 'end_visit') {
                $startTimeStr = new DateTime($startTime);
                $endTimeStr = new DateTime($r['visit_time']);
                $interval = $startTimeStr->diff($endTimeStr);
                $time = $interval->format('%H:%i:%s');
                $totalTime = sum_the_time($totalTime, $time);
            }
        }
        return ['total_time' => $totalTime, 'total_visit' => $totalVisit];
    }

    function getAllClockDataByUserListsAndDateTime($dateStart, $dateEnd){
        global $mysqli;

        $sql = "SELECT c.*, u.firstname, u.lastname, b.branch_name
                FROM user_clock_in_out c
                LEFT JOIN user_profiles u ON u.user_id = c.user_id 
                LEFT JOIN branch b ON b.branch_id = u.branch_id
                LEFT JOIN bot_admin ad ON ad.user_id != c.user_id
                WHERE u.approval_status = 'approved'
                    AND DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i') BETWEEN ? AND ? 
                ORDER BY c.id ASC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    function setForceClockOutReminder($user_id, $response) {
        global $mysqli;

        $sent = 2;
        $dateNow = date("Y-m-d");
        $sql = "UPDATE reminder SET 
                sent= ?, 
                response = ?
                WHERE  
                user_id = ? AND DATE_FORMAT(created_at, '%Y-%m-%d') >= ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isis', $sent, $response, $user_id, $dateNow);
        return $stmt->execute();
    }

    function getEndReminderNow($dateNow) {
        global $mysqli;

        $sql = "SELECT r.*, u.lang FROM reminder r 
                LEFT JOIN user_profiles u ON u.user_id = r.user_id
                WHERE DATE_FORMAT(r.end_time, '%Y-%m-%d %H:%i') = ? 
                AND r.sent = 1 AND r.reply = 0
                ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $dateNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function forceClockOut($data){
        global $mysqli;

        $sql = "INSERT INTO user_clock_in_out SET
                user_id = ?,
                clock_in_day = ?,
                clock_in_time_status = ?,
                is_clock_in = ?,
                created_at = ?
                ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('issss', $data['user_id'], $data['clock_in_day'], $data['clock_in_time_status'],
            $data['is_clock_in'], $data['created_at']);
        return $stmt->execute();
    }
    
    function getLastFiveReminderNotReply($user_id, $dateNow) {
        global $mysqli;

        $sql = "SELECT * FROM reminder 
                WHERE DATE_FORMAT(end_time, '%Y-%m-%d %H:%i') <= ? AND user_id = ? ORDER BY reminder_num DESC LIMIT 5 ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $dateNow, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getReminderCount($dateNow){
        global $mysqli;

        $sql = "
                SELECT user_id as user_id, COUNT(*) as n FROM reminder 
                WHERE 
                    DATE_FORMAT(end_time, '%Y-%m-%d %H:%i') <= ? AND sent = 1 AND reply = 0 
                    GROUP BY user_id
                ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $dateNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function setReminderResponse($data){
        global $mysqli;

        $sql = "UPDATE reminder SET 
                reply = ?, 
                response = ? 
                WHERE  
                id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isi', $data['reply'], $data['response'], $data['id']);
        return $stmt->execute();
    }

    function getLastReminderNeedReply($dateNow){
        global $mysqli;

        $sql = "SELECT * FROM reminder 
                WHERE 
                    DATE_FORMAT(end_time, '%Y-%m-%d %H:%i') >= ? AND sent = 1 AND reply = 0
                ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $dateNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function setReminderSent($id) {
        global $mysqli;

        $sent = 1;
        $sql = "UPDATE reminder SET 
                sent= ? 
                WHERE  
                id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $sent, $id);
        return $stmt->execute();
    }

    function getReminderNow($dateNow) {
        global $mysqli;

        $sql = "SELECT r.*, u.lang FROM reminder r 
                LEFT JOIN user_profiles u ON u.user_id = r.user_id
                WHERE DATE_FORMAT(r.start_time, '%Y-%m-%d %H:%i') = ? 
                AND r.sent = 0 AND r.reply = 0
                ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $dateNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function createRemider($data){
        global $mysqli;

        $created_at = date('Y-m-d H:i:s');
        if ($data['reminder_num'] == 1){
            $sent = 1;
            $sql = "INSERT INTO reminder SET 
                user_id = ?,
                type = ?,
                start_time = ?,
                sent = ?,
                end_time = ?,
                reminder_msg = ?,
                reminder_button = ?,
                reminder_num = ?,
                created_at = ?                
                ";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('ississsis', $data['user_id'], $data['type'], $data['start_time'], $sent, $data['end_time'],
                $data['reminder_msg'], $data['reminder_button'], $data['reminder_num'], $created_at);
        } else {
            $sql = "INSERT INTO reminder SET 
                user_id = ?,
                type = ?,
                start_time = ?,
                end_time = ?,
                reminder_msg = ?,
                reminder_button = ?,
                reminder_num = ?,
                created_at = ?                
                ";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('isssssis', $data['user_id'], $data['type'], $data['start_time'], $data['end_time'],
                $data['reminder_msg'], $data['reminder_button'], $data['reminder_num'], $created_at);
        }
        return $stmt->execute();
    }
    
    function getLastRecordBreakUseId($userId){
        global $mysqli;
        $sql = "SELECT * FROM user_break WHERE 
                    user_id=".$userId." AND DATE_FORMAT(created_at, '%Y-%m-%d') = '".date("Y-m-d")."' 
                    ORDER BY id DESC LIMIT 1";
        $break = $mysqli->query($sql)->fetch_assoc();
        return $break;
    }

    function setUserStepUseId($userId, $step){
        global $mysqli;

        $sql = "UPDATE user_profiles SET 
                step= ? 
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $step, $userId);
        return $stmt->execute();
    }

    function userChangeLang($user_id, $lang) {
        global $mysqli;
        
        $sql = "UPDATE user_profiles SET 
                lang = ? WHERE 
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $lang, $user_id);
        $stmt->execute();
        return true;
    }

    function getLastRecordVisit($user_id) {
        global $mysqli;

        $dateNow = date("Y-m-d");
        $sql = "SELECT * FROM user_visits 
                WHERE 
                    user_id = ? AND DATE_FORMAT(created_at, '%Y-%m-%d') = ? 
                    ORDER BY id DESC LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('is', $user_id, $dateNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    function saveUserBotRequest($user_id, $params){
        global $mysqli;

        $param = json_encode($params);

        $dateNow = date('Y-m-d H:i:s');
        $sql = "INSERT INTO user_bot_request SET 
                user_id = ?,
                params = ?, 
                created_at = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iss', $user_id, $param, $dateNow);
        $stmt->execute();
        return $stmt->insert_id;
    }

    function saveUserBotRequest2($user_id, $params){
        global $mysqli;

        $params = json_decode(json_encode($params), true);

        if (isset($params['message']['text'])){
            $userReq = "Send Text: ".$params['message']['text'];
        } elseif (isset($params['message']['photo'])){
            $userReq = "Send Images. Images ID: ".$params['message']['photo'][0]['file_id'].". ";
            if (isset($params['message']['caption'])) {
                $userReq .= "Caption: ".$params['message']['caption'];
            }
        } elseif (isset($params['message']['location'])) {
            $lat = $params['message']['location']['latitude'].", ".$params['message']['location']['longitude'];
            if (isset($params['message']['location']['live_period'])) {
                $userReq = "Send Live Location: ".$lat;
            } else {
                $userReq = "Send Location: ".$lat;
            }
        } elseif (isset($params['message']['document'])){
            $userReq = "Send Document name: ".$params['message']['document']['file_name'].". Document ID: ".$params['message']['document']['file_id'];
            if (isset($params['message']['caption'])) {
                $userReq .= ". Caption: ".$params['message']['caption'];
            }
        } elseif (isset($params['message']['video'])){
            $userReq = "Send Video name: ".$params['message']['video']['file_name'].". Video ID: ".$params['message']['video']['file_id'];
            if (isset($params['message']['caption'])) {
                $userReq .= ". Caption: ".$params['message']['caption'];
            }
        } else {
            if (isset($params['message'])) {
                $userReq = json_encode($params['message']);
            } else {
                $userReq = "";
            }
        }

        $dateNow = date('Y-m-d H:i:s');
        $userStatus = self::isUserIdExists($user_id);
        $uStat = json_encode($userStatus);
        $sql = "INSERT INTO request_reply_log SET 
                user_id = ?,
                user_request = ?,
                wrong_reply_user_stat = ?, 
                created_at = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isss', $user_id, $userReq, $uStat, $dateNow);
        $stmt->execute();
        return $stmt->insert_id;
    }

    function logReply($logID = null, $url, $params, $response) {
        global $mysqli, $api_key;

        if (is_null($logID)) {
            return false;
        }

        $sql = "SELECT *
                FROM request_reply_log  
            WHERE id = ? AND bot_reply IS NULL";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $logID);
        $stmt->execute();
        $result = $stmt->get_result();
        $log = $result->fetch_all(MYSQLI_ASSOC);
        
        if (count($log) > 0) {

            $param = json_encode($params, JSON_UNESCAPED_UNICODE);
            $respons = json_encode(json_decode($response), JSON_UNESCAPED_UNICODE);
            $sql = "UPDATE request_reply_log SET 
                bot_reply = ?, 
                api_request_url = ?,
                api_response = ?
                WHERE  
                id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sssi', $param, $url, $respons, $logID);
            return $stmt->execute();
        } else {
            self::requestLog($url, $params, $response);
        }
        return false;
    }

    function getAllVisitDataByUserListsAndDate($userLists, $dateStart){
        global $mysqli;

        $sql = "SELECT *
                FROM user_visits  
            WHERE user_id = ? 
            AND DATE_FORMAT(created_at, '%Y-%m-%d') = ? ORDER BY id ASC ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $userLists, $dateStart);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getEmployeeBranchByUserId($user_id) {
        global $mysqli;

        $sql = "SELECT 
                    branch.* 
                FROM 
                    user_profiles 
                LEFT JOIN branch ON branch.branch_id = user_profiles.branch_id
                WHERE 
                    user_profiles.user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function getPingDataById($id) {
        global $mysqli;

        $sql = "SELECT c.*
                FROM user_daily_tasks c
                WHERE c.id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function getEmployeeByUserId($user_id) {
        global $mysqli;

        $sql = "SELECT *
                FROM user_profiles
                WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function getClockDataById($id){
        global $mysqli;

        $sql = "SELECT c.*
                FROM user_clock_in_out c
                WHERE c.id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function changeWorkLocationEmployee($user_id, $branch_id, $branch_name) {
        global $mysqli;

        $sql = "UPDATE user_profiles SET 
                branch_id = ?, 
                branch_name = ?
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isi', $branch_id, $branch_name, $user_id);
        return $stmt->execute();
    }

    function getUserAlreadyAbsentToday() {
        global $mysqli;

        $dateNow = date("Y-m-d");
        $sql = "SELECT c.*, u.firstname, u.lastname, u.trigger_alarm
                FROM user_clock_in_out c 
                LEFT JOIN user_profiles u ON u.user_id = c.user_id 
                WHERE 
                c.is_clock_in = 'absent' AND 
                DATE_FORMAT(c.created_at, '%Y-%m-%d') = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $dateNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getVisitDataById($id) {
        global $mysqli;

        $sql = "SELECT a.*, u.firstname, u.lastname 
                FROM user_visits a 
                LEFT JOIN user_profiles u ON u.user_id = a.user_id 
                WHERE a.id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function updateNoteVisit($userId, $note){
        global $mysqli;

        $lastVisit = self::getLastVisitUserDataToday($userId);

        $sql = "UPDATE user_visits SET 
                visit_notes = ? WHERE 
                id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $note, $lastVisit['id']);
        $stmt->execute();
        return $lastVisit['id'];
    }

    function getLastVisitUserDataToday($userId) {
        global $mysqli;

        $today = date("Y-m-d");
        $sql = "SELECT * FROM user_visits
                WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m-%d') = ? 
                ORDER BY id DESC LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('is', $userId, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function updateMsgIdVisit($userId, $selfieId){
        global $mysqli;

        $lastVisit = self::getLastVisitUserDataToday($userId);
        $sql = "UPDATE user_visits SET 
                visit_selfie_msg_id = ? WHERE 
                id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $selfieId, $lastVisit['id']);
        return $stmt->execute();
    }

    function userVisit($params){
        global $mysqli;

        $created_at = date("Y-m-d H:i:s");
        $sql = "INSERT INTO user_visits SET 
                user_id = ?, visit_day = ?, visit_time = ?, 
                visit_lat = ?, visit_lon = ?, 
                visit_location_msg_id = ?, visit_selfie_msg_id = ?, visit_notes = ?,
                visit_action = ?, created_at = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("issssissss",
            $params['user_id'], $params['visit_day'], $params['visit_time'],
            $params['visit_lat'], $params['visit_lon'],
            $params['visit_location_msg_id'], $params['selfie_msg_id'], $params['visit_notes'],
            $params['action'], $created_at);
        return $stmt->execute();
    }

    function setBotSetting($key, $value) {
        global $mysqli;

        $sql = "UPDATE bot_settings SET 
                " . $key . " = ?
                WHERE  
                id = 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $value);
        return $stmt->execute();
    }

    function setNotesEmployee($user_id, $notes) {
        global $mysqli;

        $sql = "UPDATE user_profiles 
                    SET notes = ? 
                WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $notes, $user_id);
        return $stmt->execute();
    }

    function setJobDescEmployee($user_id, $jobdesc) {
        global $mysqli;

        $sql = "UPDATE user_profiles 
                    SET jobdesc = ? 
                WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $jobdesc, $user_id);
        return $stmt->execute();
    }

    function listEmployeeClockInToday($step) {
        global $mysqli;

        $dateNow = date("Y-m-d");
        $dayNow = date("D");
        $status = 'approved';
        /*$sql = "SELECT c.*, p.firstname, p.lastname, w.start_time, w.end_time
                FROM user_clock_in_out c
                    INNER JOIN user_clock_in_out d ON
                            d.user_id != c.user_id AND d.is_clock_in = 'clock_out' AND
                            DATE_FORMAT(STR_TO_DATE(SUBSTRING(d.created_at FROM 1 FOR CHAR_LENGTH(d.created_at) - 2), '%d/%m/%Y %H:%i:%s'), '%Y-%m-%d') = ?
                    LEFT JOIN user_profiles p ON c.user_id = p.user_id
                    LEFT JOIN user_working_hour w ON w.user_id = c.user_id AND w.work_day = ?
                WHERE
                    c.is_clock_in = 'clock_in' AND
                    DATE_FORMAT(STR_TO_DATE(SUBSTRING(c.created_at FROM 1 FOR CHAR_LENGTH(c.created_at) - 2), '%d/%m/%Y %H:%i:%s'), '%Y-%m-%d') = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sss', $dateNow, $dayNow, $dateNow);*/

        $sql = "SELECT c.*, w.start_time, w.end_time
                FROM user_profiles c
                LEFT JOIN user_working_hour w ON w.user_id = c.user_id AND w.work_day = ?
                WHERE c.step = ? AND c.approval_status = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sss', $dayNow,$step, $status);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function changeClockAbsent($params) {
        global $mysqli, $userId;

        $created_at = date("Y-m-d H:i:s");
        $sql = "UPDATE user_clock_in_out SET 
                    clock_in_location_status = ?, 
                    clock_in_location_msg_id = ?, 
                    clock_in_time_status = ?, 
                    clock_in_time = ?, 
                    work_start_time = ?, 
                    clock_in_distance = ?, 
                    clock_in_lat = ?, 
                    clock_in_lon = ?, 
                    is_clock_in = ?, 
                    created_at = ?
                WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssssssssssi', $params['location_status'], $params['location_msg_id'],
            $params['time_status'], $params['clocked_time'], $params['work_time'], $params['location_distance'],
            $params['location_lat'], $params['location_lon'], $params['action'], $created_at, $params['data_id']);
        return $stmt->execute();
    }

    function getUserSchedule($user_id) {
        global $mysqli;

        $sql = "SELECT * FROM user_working_hour 
                WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getApprovedUser($offset = 0, $limit = 3) {
        global $mysqli, $admin_id;

        $approved = 'approved';
        $admin_list = implode( "," , $admin_id );

        $sql = "SELECT * FROM user_profiles 
                WHERE (approval_status = ? OR (approval_status IS NULL AND user_id IN ($admin_list) )) 
                ORDER BY id 
                LIMIT ? OFFSET ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sii', $approved,  $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function requestLog($url, $params, $respond) {
        global $mysqli;


        if ($url != '' || !is_null($url)) {
            if (isset($params['chat_id'])) {
                $param = json_encode($params, JSON_UNESCAPED_UNICODE);
                $respons = json_encode(json_decode($respond), JSON_UNESCAPED_UNICODE);
                $user_id = $params['chat_id'];

                $dateNow = date('Y-m-d H:i:s');
                $sql = "INSERT INTO request_reply_log SET 
                user_id = ?, 
                api_request_url = ?,
                bot_reply = ?, 
                api_response = ?,
                created_at = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('issss', $user_id, $url, $param, $respons, $dateNow);
                $stmt->execute();
                return $stmt->insert_id;
            }
        }
        return false;
    }

    function getLastClock2($userId){
        global $mysqli;

        $dateNow = date("Y-m-d");
        $sql = "SELECT * FROM user_clock_in_out WHERE 
                user_id= ?  
                ORDER BY id DESC LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function resetWorkingTimeByUser($user_id) {
        global $mysqli;

        $sql = "DELETE FROM 
                user_working_hour 
                WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $user_id);
        return $stmt->execute();
    }

    function saveWorkingTime($params){
        global $mysqli;

        $dateNow = date("Y-m-d H:i:s");
        $sql = "INSERT INTO user_working_hour SET 
                user_id = ?,
                work_day = ?, 
                start_time = ?, 
                end_time = ?, 
                created_at = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('issss', $params['user_id'], $params['day'], $params['start_time'], $params['end_time'], $dateNow);
        $stmt->execute();
        return $stmt->insert_id;
    }

    function changeClockTimeStatus($id, $status) {
        global $mysqli;

        $sql = "UPDATE user_clock_in_out SET 
                clock_in_time_status = ?
                WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $status, $id);
        return $stmt->execute();
    }

    function getBranchById($id) {
        global $mysqli;

        $sql = "SELECT * FROM branch WHERE branch_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function removeBranchById($id) {
        global $mysqli;

        $sql = "DELETE FROM 
                branch 
                WHERE branch_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    function editBranchById($id, $params) {
        global $mysqli;

        $sql = "UPDATE branch SET 
                branch_name = ?,
                branch_lat = ?,
                branch_lon = ?
                WHERE branch_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssi', $params['branch_name'], $params['lat'], $params['long'], $id);
        return $stmt->execute();
    }

    function changeCompleteUserStep($user_id, $val) {
        global $mysqli;

        $sql = "UPDATE user_profiles SET 
                is_step_complete = ? WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $val, $user_id);
        return $stmt->execute();
    }

    function setBranchLatLong($id, $lat, $long){
        global $mysqli;

        $sql = "UPDATE branch SET 
                branch_lat = ?,
                branch_lon = ?
                WHERE branch_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $lat, $long, $id);
        return $stmt->execute();
    }

    function addBranchName($branchName) {
        global $mysqli;

        $sql = "INSERT INTO branch SET branch_name = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $branchName);
        $stmt->execute();
        return $stmt->insert_id;
    }

    function getAdminStep($user_id) {
        global $mysqli;

        $sql = "SELECT * 
                    FROM bot_admin 
                    WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function setAdminStep($user_id, $step, $params = ''){
        global $mysqli;

        $sql = "UPDATE bot_admin SET 
                step = ?, 
                temp = ? 
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $step, $params, $user_id);
        return $stmt->execute();
    }

    function changeCronConfig($title, $val, $time, $additional = array()) {
        global $mysqli;

        //get data
        $sql = "SELECT * FROM bot_cron 
                WHERE title=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $title);
        $stmt->execute();
        $result = $stmt->get_result();
        $cron = $result->fetch_assoc();

        if (count($cron) > 0) {
            $config = json_decode($cron['cron_config'], true);
            $config['runtime']['time'] = $time;
            if (count($additional) > 0){
                $config['additional'] = $additional;
            }
            $newConfig = json_encode($config);
            $sql = "UPDATE bot_cron SET 
                        cron_active = ?, cron_config = ? WHERE title = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('iss', $val, $newConfig, $title);
            return $stmt->execute();
        }
        return false;
    }

    function changeCronActive($title, $val) {
        global $mysqli;

        $sql = "UPDATE bot_cron SET 
                cron_active = ? WHERE title = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('is', $val, $title);
        return $stmt->execute();
    }

    function CronRunUpdate($id) {
        global $mysqli;

        $dateNow = date("Y-m-d H:i:s");
        $sql = "UPDATE bot_cron SET 
                last_run = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $dateNow, $id);
        return $stmt->execute();
    }

    function getCronLists() {
        global $mysqli;

        $cron_status = 1;
        $sql = "SELECT * FROM bot_cron WHERE cron_active = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $cron_status);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getDetilUserById($user_id){
        global $mysqli;

        $sql = "SELECT * FROM user_profiles 
                WHERE user_id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function updateUserVisitAlert($user_id, $value){
        global $mysqli;

        $sql = "UPDATE user_profiles SET 
                visit_alert = ?
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $value, $user_id);

        return $stmt->execute();
    }

    function updateUserPingModule($user_id, $value){
        global $mysqli;

        $sql = "UPDATE user_profiles SET 
                ping_module = ?
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $value, $user_id);

        return $stmt->execute();
    }

    function updateUserVisitTrigger($user_id, $value){
        global $mysqli;

        $sql = "UPDATE user_profiles SET 
                can_visit = ?
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $value, $user_id);

        return $stmt->execute();
    }

    function updateUserBreakTrigger($user_id, $value){
        global $mysqli;

        $sql = "UPDATE user_profiles SET 
                can_break = ?
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $value, $user_id);

        return $stmt->execute();
    }

    function updateUserBreakStep($user_id, $value){
        global $mysqli;

        $sql = "UPDATE user_profiles SET 
                break_step = ?
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $value, $user_id);

        return $stmt->execute();
    }

    function updateUserTriggerAlaram($user_id, $value){
        global $mysqli;

        $sql = "UPDATE user_profiles SET 
                trigger_alarm = ?
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $value, $user_id);

        return $stmt->execute();
    }

    function getAdminByUserId($user_id) {
        global $mysqli;

        $sql = "SELECT * FROM bot_admin WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function getAllAdmin() {
        global $mysqli;

        $sql = "SELECT * FROM bot_admin";
        $stmt = $mysqli->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function addAdmin($params) {
        global $mysqli;

        $user = self::getDetilUserById($params['user_id']);
        $sql = "INSERT INTO bot_admin SET
                user_id = ?,
                admin_name = ?
                ";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('is', $params['user_id'], $user['firstname']);
        $stmt->execute();

        if (!isset($user['id'])) {
            $sql2 = "INSERT INTO user_profiles SET
                user_id = ?,
                 firstname = ?;
                ";

            $stmt2 = $mysqli->prepare($sql2);
            $stmt2->bind_param('is', $params['user_id'], $user['firstname']);
            return $stmt2->execute();
        } else {
            $sql3 = "UPDATE user_profiles SET 
                approval_status = NULL
                WHERE user_id = ?
                ";

            $stmt3 = $mysqli->prepare($sql3);
            $stmt3->bind_param('i', $params['user_id']);
            return $stmt3->execute();
        }
    }

    function removeAdmin($params) {
        global $mysqli;

        $sql = "DELETE FROM bot_admin WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $params['user_id']);
        $r = $stmt->execute();

        if (array_key_exists('approval', $params)) {
            $sql = "UPDATE user_profiles SET approval_status = ? WHERE user_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('si', $params['approval'], $params['user_id']);
            $r = $stmt->execute();
        }
        return $r;
    }

    function setWelcomeMessage($msg, $img) {
        global $mysqli;

        $sql = "UPDATE bot_settings SET 
                welcome_msg = ?, 
                welcome_img = ? 
                WHERE id = 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ss", $msg, $img);
        return $stmt->execute();
    }

    function changeBotSetting($key, $value){
        global $mysqli;

        $sql = "UPDATE bot_settings SET 
                " . $key . " = ?
                WHERE  
                id = 1";
        $stmt = $mysqli->prepare($sql);
        if ($key == 'location_tolerance') {
            $stmt->bind_param('d', $value);
        } else {
            $stmt->bind_param('i', $value);
        }
        return $stmt->execute();
    }

    function setDeadManFeature($value, $time = 0){
        global $mysqli;

        $sql = "UPDATE bot_settings SET 
                dead_man_feature = ?,
                dead_man_task_time = ?
                WHERE  
                id = 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $value, $time);
        return $stmt->execute();
    }

    function markDeadManRemainOnClockOut($user_id) {
        global $mysqli;

        $reply = 2;
        $status = "INIT";
        $sql = "UPDATE user_daily_tasks SET 
                    task_reply = ? 
                WHERE 
                    user_id = ? AND 
                    task_status = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iis', $reply, $user_id, $status);
        return $stmt->execute();
    }

    function updateDailyTask($task_id, $params) {
        global $mysqli;

        $sql = "UPDATE user_daily_tasks SET 
                task_reply = ?,
                task_status = ?,
                reply_time = ?,
                reply_location = ?,
                reply_location_status = ?,
                reply_location_distance = ?,
                reply_location_msg_id = ?
                WHERE  
                id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isssssii', $params['task_reply'], $params['task_status'], $params['reply_time'],
            $params['reply_location'], $params['reply_location_status'], $params['reply_location_distance'],
            $params['reply_location_msg_id'], $task_id);
        return $stmt->execute();
    }

    function getLastMissedDailyTask($user_id){
        global $mysqli;

        $dateStart = date("Y-m-d")." 00:00";
        $dateEnd = date("Y-m-d H:i");
        $sql = "SELECT * FROM user_daily_tasks 
                WHERE 
                user_id = ? AND
                task_send = 1 AND 
                task_reply = 0 AND 
                (DATE_FORMAT(task_end, '%Y-%m-%d %H:%i') BETWEEN ? AND ?)
                ORDER BY id DESC LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iss', $user_id, $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function getDailyTaskThisTime($user_id, $minutes = 30){
        global $mysqli;

        $dateStart = date("Y-m-d H:i", strtotime('-'.$minutes.' minutes'));
        $dateEnd = date("Y-m-d H:i");
        $sql = "SELECT * FROM user_daily_tasks 
                WHERE 
                user_id = ? AND
                task_send = 1 AND 
                task_reply = 0 AND 
                (DATE_FORMAT(task_start, '%Y-%m-%d %H:%i') BETWEEN ? AND ?)
                ORDER BY id DESC LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iss', $user_id, $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function markTaskSend($task_id, $reply = 0){
        global $mysqli;

        $send = 1;
        $status = "MISS";
        $sql = "UPDATE user_daily_tasks SET 
                task_send = ?, task_status = ?, task_reply = ?
                WHERE  
                id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isii', $send, $status, $reply, $task_id);
        return $stmt->execute();
    }

    function getTasksNotReplyToday(){
        global $mysqli;

        $dateStart = date("Y-m-d")." 00:00";
        $dateEnd = date("Y-m-d H:i");

        $sql = "SELECT * FROM user_daily_tasks 
                WHERE 
                task_send = 1 AND task_reply = 0 AND 
                (DATE_FORMAT(task_end, '%Y-%m-%d %H:%i') BETWEEN ? AND ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getTasksToSendToday(){
        global $mysqli;

        $dateStart = date("Y-m-d")." 00:00";
        $dateEnd = date("Y-m-d H:i");
        $task_send = 0;

        $sql = "SELECT * FROM user_daily_tasks 
                WHERE 
                task_send = ? AND 
                task_reply = ? AND 
                (DATE_FORMAT(task_start, '%Y-%m-%d %H:%i') BETWEEN ? AND ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iiss', $task_send, $task_send, $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getTasksToSendTodayUseLang(){
        global $mysqli;

        $dateStart = date("Y-m-d")." 00:00";
        $dateEnd = date("Y-m-d H:i");
        $task_send = 0;

        $sql = "SELECT user_daily_tasks.*, u.lang FROM user_daily_tasks 
                LEFT JOIN user_profiles u ON user_daily_tasks.user_id = u.user_id 
                WHERE 
                task_send = ? AND 
                task_reply = ? AND 
                (DATE_FORMAT(task_start, '%Y-%m-%d %H:%i') BETWEEN ? AND ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iiss', $task_send, $task_send, $dateStart, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function initDailyTask($params){
        global $mysqli;

        $status = "INIT";
        $sql = "INSERT INTO user_daily_tasks SET
                user_id = ?,
                task_start = ?,
                task_end = ?,
                task_status = ?
                ";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isss', $params['user_id'], $params['task_start'], $params['task_end'], $status);
        $m = $stmt->execute();
        var_dump($m);
        return $m;
    }

    function getWorkingDayDataByUser($user_id, $day){
        global $mysqli;

        $sql = "SELECT * FROM  user_working_hour 
                WHERE user_id=? AND work_day=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('is', $user_id, $day);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function calculateUserBreakByDay($user_id, $date, $date2 = false){
        global $mysqli;

        $dateEnd = $date;
        if ($date2) {
            $dateEnd = $date2;
        }

        $sql = "SELECT *
        FROM user_break 
        WHERE user_id = ? 
        AND DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') BETWEEN ? AND ?
        ORDER BY id ASC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iss', $user_id, $date, $dateEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_all(MYSQLI_ASSOC);

        $startTime = '';
        $totalTime = '00:00';
        $totalBreak = 0;
        foreach($res as $r){
            if ($r['break_action'] == 'start_break') {
                $startTime = $r['created_at'];
                $totalBreak++;
            } elseif ($r['break_action'] == 'end_break') {
                $startTimeStr = new DateTime($startTime);
                $endTimeStr = new DateTime($r['created_at']);
                $interval = $startTimeStr->diff($endTimeStr);
                $time = $interval->format('%H:%i:%s');
                $totalTime = sum_the_time($totalTime, $time);
            }
        }
        return ['total_time' => $totalTime, 'total_break' => $totalBreak];
    }

    function getAllBreakDataByUserListsAndDate($userLists, $dateStart){
        global $mysqli;

        $sql = "SELECT *, break_time AS user_time
                FROM user_break  
            WHERE user_id = ? 
            AND DATE_FORMAT(created_at, '%Y-%m-%d') = ? ORDER BY id ASC ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $userLists, $dateStart);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getAllClockDataByUserListsAndDate($userLists, $dateStart, $clock_in = 'clock_in'){
        global $mysqli;

        $sql = "SELECT *
                FROM user_clock_in_out 
            WHERE user_id = ? 
            AND DATE_FORMAT(created_at, '%Y-%m-%d') = ? 
            AND is_clock_in = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sss', $userLists, $dateStart, $clock_in);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function getBranchByName($branchName){
        global $mysqli;

        $sql = "SELECT * FROM branch WHERE branch_name = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $branchName);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function getAllApprovedUsersByBranch($branch_id){
        global $mysqli;

        $approved = 'approved';
        $sql = "SELECT * FROM user_profiles WHERE approval_status = ? AND branch_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $approved, $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getClockDataByDate($date){
        global $mysqli;

        $dateParam = $date."%";
        $status = 'approved';

        $sql = "SELECT u.firstname, u.lastname, c.*
                FROM user_clock_in_out c
                LEFT JOIN user_profiles u ON u.user_id = c.user_id
                WHERE DATE_FORMAT(c.created_at, '%Y-%m-%d') = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $dateParam);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getUserWorkingSchedule($user_id){
        global $mysqli;

        $day = date('D');
        $sql = "SELECT * FROM  user_working_hour 
                WHERE user_id=? AND work_day=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('is', $user_id, $day);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function insertUserAbsent($params){
        global $mysqli;

        $created_at = (isset($params['created_at'])) ? $params['created_at'] : date("Y-m-d H:i:s");
        //$created_at = date("d/m/Y H:i:s a");
        $sql = "INSERT INTO user_clock_in_out SET
                user_id = ?,
                clock_in_day = ?,
                is_clock_in = ?,
                created_at = ?
                ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isss', $params['user_id'], $params['clock_in_day'], $params['is_clock_in'], $created_at);
        return $stmt->execute();
    }

    function getNotClockOut($tolerance){
        global $mysqli;

        $timeNow = date("H:i", strtotime("-".$tolerance." minutes"));
        $date = new DateTime();
        //$dateNow = date("d/m/Y")."%";
        $dateNow = $date->format('Y-m-d');
        $dayNow = $date->format("D");
        $yesterday = $date->modify("-1 day")->format('D');
        $status = 'approved';
        $alarm = 1;

        /*
        $sql = "SELECT user_working_hour.*, user_profiles.*
            FROM user_working_hour
            LEFT JOIN user_profiles ON user_profiles.user_id = user_working_hour.user_id
            WHERE
                user_working_hour.work_day = ? AND
                user_working_hour.end_time = ? AND
                user_profiles.approval_status = ? AND
	            user_working_hour.user_id NOT IN (
                    SELECT d.user_id
                    FROM user_clock_in_out d
                    WHERE d.created_at LIKE ? AND (is_clock_in = 'clock_out' OR is_clock_in = 'absent'))";
        */
        $sql = "SELECT user_working_hour.*, user_profiles.* 
                FROM user_working_hour 
                LEFT JOIN user_profiles ON user_profiles.user_id = user_working_hour.user_id
                WHERE 
                    (
                        (IF(user_working_hour.end_time < user_working_hour.start_time, 1, 0) = 1 AND user_working_hour.work_day = ?) OR 
                        (IF(user_working_hour.end_time < user_working_hour.start_time, 1, 0) = 0 AND user_working_hour.work_day = ?)
                    ) AND 
                    end_time = ? AND 
                    user_profiles.approval_status = ? AND 
                    user_working_hour.user_id NOT IN (
                        SELECT d.user_id 
                        FROM user_clock_in_out d 
                        WHERE 
                            DATE_FORMAT(created_at, '%Y-%m-%d') = ? AND 
                            (is_clock_in = 'clock_out' OR is_clock_in = 'absent'))
        ";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssss', $yesterday, $dayNow, $timeNow, $status, $dateNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getAbsentDailyToday($tolerance){
        global $mysqli;

        $timeNow = date("H:i", strtotime("-".$tolerance." minutes"));
        $dayNow = date("D");
        $dateNow = date("Y-m-d");
        $status = 'approved';
        $alarm = 1;

        $sql = "SELECT user_working_hour.*, user_profiles.* 
            FROM user_working_hour 
            LEFT JOIN user_profiles ON user_profiles.user_id = user_working_hour.user_id
            WHERE 
                user_working_hour.work_day = ? AND 
                user_working_hour.start_time = ? AND
                user_profiles.approval_status = ? AND 
	            user_working_hour.user_id NOT IN (
                    SELECT d.user_id
                    FROM user_clock_in_out d
                    WHERE DATE_FORMAT(d.created_at, '%Y-%m-%d') = ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssss', $dayNow, $timeNow, $status, $dateNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function setUserStep($step){
        global $mysqli, $userId;

        $sql = "UPDATE user_profiles SET 
                step= ? 
                WHERE  
                user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $step, $userId);
        return $stmt->execute();
    }

    function getLastClock($userId){
        global $mysqli, $userId;

        $dateNow = date("Y-m-d");
        $sql = "SELECT * FROM user_clock_in_out WHERE 
                user_id= ? AND 
                DATE_FORMAT(created_at, '%Y-%m-%d') = ? 
                ORDER BY id DESC LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('is', $userId, $dateNow);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    function getLastRecordBreak(){
        global $mysqli, $userId;
        $sql = "SELECT * FROM user_break WHERE 
                    user_id=".$userId." AND DATE_FORMAT(created_at, '%Y-%m-%d') = '".date("Y-m-d")."' 
                    ORDER BY id DESC LIMIT 1";
        $break = $mysqli->query($sql)->fetch_assoc();
        return $break;
    }

    function updateMsgIdBreak($userId, $selfieId){
        global $mysqli;
        $user = self::getUserByID($userId);
        if ($user['step'] == 'start_break_req_selfie') {
            $action = "start_break";
        } else {
            $action = "end_break";
        }

        $sql = "SELECT * FROM user_break WHERE 
                    user_id=".$userId." AND DATE_FORMAT(created_at, '%Y-%m-%d') = '".date("Y-m-d")."' 
                    ORDER BY id DESC LIMIT 1";
        $break = $mysqli->query($sql)->fetch_assoc();

        $sql = "UPDATE user_break SET selfie_msg_id=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $selfieId, $break['id']);
        $stmt->execute();
        return $break['id'];
    }

    function userBreak($params){
        global $mysqli, $userId;

        $created_at = date("Y-m-d H:i:s");
        $sql = "INSERT INTO user_break SET 
                user_id = ?, break_day = ?, break_time = ?, 
                location_status = ?, location_lat = ?, location_lon = ?, 
                location_msg_id = ?, location_distance = ?, break_action = ?,
                selfie_msg_id = ?, created_at = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isssssissss",
            $params['user_id'], $params['day'], $params['break_time'],
            $params['location_status'], $params['location_lat'], $params['location_lon'],
            $params['location_msg_id'], $params['location_distance'], $params['action'],
            $params['selfie_msg_id'], $created_at);
        return $stmt->execute();
    }

    function getClockData($clockStatus){
        global $mysqli, $userId;
        $sql = "SELECT * FROM user_clock_in_out WHERE 
                    user_id=".$userId." AND DATE_FORMAT(created_at, '%Y-%m-%d') = '".date("Y-m-d")."' 
                    AND is_clock_in = '".$clockStatus."' 
                    ORDER BY id DESC LIMIT 1";
        return $mysqli->query($sql)->fetch_assoc();
    }

    function getClockIn($where = '', $count = true)
    {
        global $mysqli, $userId;
        $created_at = date("Y-m-d");
        if (!$count) return $mysqli->query("select * from user_clock_in_out where user_id = $userId and DATE_FORMAT(created_at, '%Y-%m-%d') = '".$created_at."' $where")->fetch_assoc();
        return mysqli_num_rows($mysqli->query("select * from user_clock_in_out where user_id = $userId $where"));
    }

    function isClockIn($where = '') {
        global $mysqli, $userId;
        $created_at = date("Y-m-d");
        return mysqli_num_rows($mysqli->query("select * from user_clock_in_out where user_id = $userId and is_clock_in = 'clock_in' and DATE_FORMAT(created_at, '%Y-%m-%d') = '$created_at' $where"));
    }

    function isClockOut($where = '') {
        global $mysqli, $userId;
        $created_at = date("Y-m-d");
        return mysqli_num_rows($mysqli->query("select * from user_clock_in_out where user_id = $userId and is_clock_in = 'clock_out' and DATE_FORMAT(created_at, '%Y-%m-%d') = '$created_at' $where"));
    }

    function insertClockIn($clock_in_day = null, $clock_in_location_status = null, $clock_in_location_msg_id = null, $clock_in_time_status = null, $clock_in_time = null, $work_start_time = null, $distance = null, $lat = null, $lon = null, $is_clock_in = null)
    {
        global $mysqli, $userId;
        $created_at = date("Y-m-d H:i:s");
        return $mysqli->query("insert into user_clock_in_out set user_id = $userId, clock_in_day = '$clock_in_day', clock_in_location_status = '$clock_in_location_status', clock_in_location_msg_id = '$clock_in_location_msg_id', clock_in_time_status = '$clock_in_time_status', clock_in_time = '$clock_in_time', work_start_time = '$work_start_time', clock_in_distance = '$distance', clock_in_lat = '$lat', clock_in_lon = '$lon', is_clock_in = '$is_clock_in', created_at = '$created_at'");
    }

    function updateClockInSet($set = '')
    {
        global $mysqli, $userId;
        $created_at = date("Y-m-d");
        return $mysqli->query("update user_clock_in_out set $set where DATE_FORMAT(created_at, '%Y-%m-%d') = '".$created_at."' and user_id = $userId and is_clock_in = 'clock_in'");
    }

    function updateClockOutSet($set = '')
    {
        global $mysqli, $userId;
        $created_at = date("Y-m-d");
        return $mysqli->query("update user_clock_in_out set $set where DATE_FORMAT(created_at, '%Y-%m-%d') = '".$created_at."' and user_id = $userId and is_clock_in = 'clock_out'");
    }

    function updateClockIn($clock_in_day = null, $clock_in_location_status = null, $clock_in_location_msg_id = null, $clock_in_time_status = null, $clock_in_time = null, $work_start_time = null)
    {
        global $mysqli, $userId;
        $created_at = date("Y-m-d");
        return $mysqli->query("update user_clock_in_out set clock_in_day = '$clock_in_day', clock_in_location_status = '$clock_in_location_status', clock_in_location_msg_id = '$clock_in_location_msg_id', clock_in_time_status = '$clock_in_time_status', clock_in_time = '$clock_in_time', work_start_time = '$work_start_time' where DATE_FORMAT(created_at, '%Y-%m-%d') = '".$created_at."' and user_id = $userId");
    }

    function getUser($where = '', $count = true)
    {
        global $mysqli, $userId;
        if (!$count) return $mysqli->query("select * from user_profiles where user_id = $userId $where")->fetch_assoc();
        return mysqli_num_rows($mysqli->query("select * from user_profiles where user_id = $userId $where"));
    }

    function getUserByID($user_id = '')
    {
        global $mysqli;
        return $mysqli->query("select * from user_profiles where user_id = $user_id and approval_status is null")->fetch_assoc();
    }

    function getAllUser($offset = 0, $limit = 3)
    {
        global $mysqli, $admin_id;
        $sql = "select * from user_profiles where approval_status is null and user_id not in (" . implode( "," , $admin_id ) . ") order by id desc limit $limit offset $offset";
        return $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    function getUserNotification($user_id = '')
    {
        global $mysqli;
        return $mysqli->query("select * from user_profiles where user_id = $user_id")->fetch_assoc();
    }

    function getPhotoMessage($where = '')
    {
        global $mysqli, $userId;
        return $mysqli->query("select * from user_profiles where user_id = $userId $where")->fetch_assoc();
    }

    function insertUserStep($step = '', $phone = '', $lang = '')
    {
        global $first_name, $last_name, $mysqli, $userId;
        $created_at = date("Y-m-d H:i:s");

        $fname = parseText($first_name);
        $lname = parseText($last_name);
        /*
        return $mysqli->query("insert into user_profiles set user_id = $userId, firstname = '$fname', lastname = '$lname', step = '$step', phone = '$phone', created_at = '$created_at'");
        */

        $sql = "INSERT INTO user_profiles SET
                user_id = ?,
                firstname = ?,
                lastname = ?,
                step = ?,
                phone = ?,
                created_at = ?
                ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isssss', $userId, $first_name, $last_name, $step, $phone, $created_at);
        $stmt->execute();
    }

    function updateUserWhere($user_id = '', $where = '')
    {
        global $mysqli;
        return $mysqli->query("update user_profiles set $where where user_id = $user_id");
    }

    function getWorkday($user_id = '', $day = '', $count = false, $where = '')
    {
        global $mysqli;
        if (!$count) return $mysqli->query("select * from user_working_hour where user_id = $user_id $where")->fetch_all(MYSQLI_ASSOC);
        return mysqli_num_rows($mysqli->query("select * from user_working_hour where work_day = '$day' and user_id = $user_id"));
    }

    function insertWorkday($user_id = '', $day = '')
    {
        global $mysqli;
        $created_at = date("Y-m-d H:i:s");
        return $mysqli->query("insert into user_working_hour set user_id = $user_id, work_day = '$day', created_at = '$created_at'");
    }

    function updateWorkStartTime($user_id = '', $day = '', $h = '', $mn = '')
    {
        global $mysqli;
        return $mysqli->query("update user_working_hour set start_time = '$h:$mn' where user_id = $user_id and work_day = '$day'");
    }

    function updateWorkEndTime($user_id = '', $day = '', $h = '', $mn = '')
    {
        global $mysqli;
        return $mysqli->query("update user_working_hour set end_time = '$h:$mn' where user_id = $user_id and work_day = '$day'");
    }

    function deleteWorkday($user_id = '', $day = '')
    {
        global $mysqli;
        return $mysqli->query("delete from user_working_hour where user_id = $user_id and work_day = '$day'");
    }

    function updateUserProfiles($phone = '')
    {
        global $first_name, $last_name, $mysqli, $userId;
        $created_at = date("Y-m-d H:i:s");

        $fname = parseText($first_name);
        $lname = parseText($last_name);
        return $mysqli->query("update user_profiles set firstname = '$fname', lastname = '$lname', phone = '$phone', created_at = '$created_at' where user_id = $userId");
    }

    function updateUserPhoto($photo_msg_id = '')
    {
        global $first_name, $last_name, $mysqli, $userId;
        $created_at = date("Y-m-d H:i:s");

        $fname = parseText($first_name);
        $lname = parseText($last_name);
        return $mysqli->query("update user_profiles set firstname = '$fname', lastname = '$lname', photo_message_id = '$photo_msg_id', created_at = '$created_at' where user_id = $userId");
    }

    function updateUserPhotoID($photo_id = null) {
        global $first_name, $last_name, $mysqli, $userId;
        $created_at = date("Y-m-d H:i:s");

        $fname = parseText($first_name);
        $lname = parseText($last_name);
        return $mysqli->query("update user_profiles set firstname = '$fname', lastname = '$lname', photo_id = '$photo_id', created_at = '$created_at' where user_id = $userId");
    }

    function updateUserEmail($email = '')
    {
        global $first_name, $last_name, $mysqli, $userId;
        $created_at = date("Y-m-d H:i:s");

        $fname = parseText($first_name);
        $lname = parseText($last_name);
        return $mysqli->query("update user_profiles set firstname = '$fname', lastname = '$lname', email = '$email', created_at = '$created_at' where user_id = $userId");
    }

    function updateUserBranch($user_id = null, $branch_id = null, $branch_name = null)
    {
        global $mysqli;
        return $mysqli->query("update user_profiles set branch_id = $branch_id, branch_name = '$branch_name' where user_id = $user_id");
    }

    function updateUserNotificationNewUserMSGID($notificationNewUserMSGID = '', $user_id = null)
    {
        global $mysqli;
        return $mysqli->query("update user_profiles set notification_new_user_msg_id = '$notificationNewUserMSGID' where user_id = $user_id");
    }

    function updateUserListEmpMSGID($list_emp_msg_id = '', $user_id = null)
    {
        global $mysqli;
        return $mysqli->query("update user_profiles set list_emp_msg_id = '$list_emp_msg_id' where user_id = $user_id");
    }

    function updateUserDaySelectedMSGID($day_msg_id = '', $user_id = null)
    {
        global $mysqli;
        return $mysqli->query("update user_profiles set day_selected_msg_id = '$day_msg_id' where user_id = $user_id");
    }

    function updateUserStartTimeMSGID($start_msg_id = '', $user_id = null)
    {
        global $mysqli;
        return $mysqli->query("update user_profiles set set_start_time_msg_id = '$start_msg_id' where user_id = $user_id");
    }

    function updateUserEndTimeMSGID($end_msg_id = '', $user_id = null)
    {
        global $mysqli;
        return $mysqli->query("update user_profiles set set_end_time_msg_id = '$end_msg_id' where user_id = $user_id");
    }

    function getSettings() {
        global $mysqli;
        return $mysqli->query("select * from bot_settings")->fetch_assoc();
    }

    function getBranch($branch_id) {
        global $mysqli;
        return $mysqli->query("select * from branch where branch_id = $branch_id")->fetch_assoc();
    }

    function getAllBranch() {
        global $mysqli;
        return $mysqli->query("select * from branch")->fetch_all(MYSQLI_ASSOC);
    }
}