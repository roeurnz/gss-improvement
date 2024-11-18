<?php
error_log(0);
date_default_timezone_set("Asia/Phnom_Penh");
include "vendor/autoload.php";
include __DIR__ . '/database/dbconfig.php';
include __DIR__ . '/api_key.php';
//include __DIR__ . '/includes/admin.php';

define('MAX_REMINDER', 5);
define('MAX_NOT_REPLY_REMINDER', 5);

//load models
$path = __DIR__ . "/includes/models";
$files = array_diff(scandir($path), array('.', '..'));
foreach($files as $file){
    $m = explode('.', $file);
    $x = end($m);
    if ($x == 'php') {
        include $path."/".$file;
    }
}

//load functions
include __DIR__ . "/includes/functions/functions.php";

$langActive = ['kh', 'en'];
/*$userCommands = json_encode(array(
    array(
        "command" => "/start",
        "description" => "Refresh Your Status"
    ),
    array(
        "command" => "/worklocation",
        "description" => "Show Work Location Map"
    ),
    array(
        "command" => "/checkschedule",
        "description" => "See My Working Schedule"
    ),
    array(
        "command" => "/changelanguage",
        "description" => "Change Language"
    ),
    array(
        "command" => "/editprofile",
        "description" => "Coming Soon"
    )
));*/

$adminCommands = json_encode(array(

));

$ez = new EzzeTeamsModel();
$botSettings = $ez->getSettings();
$botSettings['lang_active'] = $langActive;
$botSettings['max_reminder'] = MAX_REMINDER;
$botSettings['max_not_reply_reminder'] = MAX_NOT_REPLY_REMINDER;

$lang_allow = array('🇰🇭 ភាសាខ្មែរ', '🇺🇸 English');

$admins = $ez->getAllAdmin();
$admin_id = [];
$adminDetils = [];
foreach($admins as $adm) {
    array_push($admin_id, $adm['user_id']);
    $adminDetils[$adm['user_id']] = $adm;
}
