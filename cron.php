<?php
include "load.php";

$ezzeTeamsModel = new EzzeTeamsModel();
$cronLists = $ezzeTeamsModel->getCronLists();
$runLists = [];

//config special
$weekly_send = ['day' => 'Sun'];

$timeNow = date("H:i");
$dateNow = date("Y-m-d");
foreach ($cronLists as $cron) {
    $params = [];
    $config = json_decode($cron['cron_config'], true);
    if ($config['runtime']['schedule'] == 'daily') {
        if ($config['runtime']['time'] == 'everyminutes'){
            $params['command'] = $cron['cron_command'];
            $params = array_merge($params, $config['params']);
            run_cron(BASE_URL.$cron['cron_file']."?".http_build_query($params), $cron['id']);
        } else {
            $x = explode(':', $config['runtime']['time']);
            if (count($x) == 2) {
                if ( $x[0] >= 0 && $x[0] < 24 && $x[1] >= 0 && $x[1] < 60 ) {
                    if (strtotime($config['runtime']['time']) == strtotime($timeNow)) {
                        $params['command'] = $cron['cron_command'];
                        $params = array_merge($params, $config['params']);
                        if (isset($config['additional'])) {
                            $params = array_merge($params, $config['additional']);
                        }
                        run_cron(BASE_URL.$cron['cron_file']."?".http_build_query($params), $cron['id']);
                    }
                }
            }
        }
    } else if ($config['runtime']['schedule'] == 'weekly') {
        if (date('D') == $weekly_send['day']) {
            $x = explode(':', $config['runtime']['time']);
            if (count($x) == 2) {
                if ( $x[0] >= 0 && $x[0] < 24 && $x[1] >= 0 && $x[1] < 60 ) {
                    if (strtotime($config['runtime']['time']) == strtotime($timeNow)) {
                        $params['command'] = $cron['cron_command'];
                        $params = array_merge($params, $config['params']);
                        run_cron(BASE_URL.$cron['cron_file']."?".http_build_query($params), $cron['id']);
                    }
                }
            }
        }
    } else if ($config['runtime']['schedule'] == 'monthly') {
        $dateEndMonth = date("Y-m-d", strtotime("last day of last month"));
        if (strtotime($dateNow) == strtotime($dateEndMonth)) {
            $x = explode(':', $config['runtime']['time']);
            if (count($x) == 2) {
                if ( $x[0] >= 0 && $x[0] < 24 && $x[1] >= 0 && $x[1] < 60 ) {
                    if (strtotime($config['runtime']['time']) == strtotime($timeNow)) {
                        $params['command'] = $cron['cron_command'];
                        $params = array_merge($params, $config['params']);
                        run_cron(BASE_URL.$cron['cron_file']."?".http_build_query($params), $cron['id']);
                    }
                }
            }
        }
    }
}