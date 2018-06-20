<?php
/**
 * Created by PhpStorm.
 * User: crazytster
 * Date: 20.06.18
 * Time: 16:40
 */

$url = 'https://www.mos.ru/pgu/common/ajax/index.php';
$params_to_get = [
    'ajaxModule' => 'Guis',
    'ajaxAction' => 'getCountersInfo',
    'items' => [
        'paycode' => '3130158061',
        'flat' => '324',
    ]
];

$params_to_set = [
    'ajaxModule' => 'Guis',
    'ajaxAction' => 'addCounterInfo',
    'items' => [
        'paycode' => '3130158061',
        'flat' => '324',
        'indications' => [
            //ColdWater
            [
                'counterNum' => '1168941',
                'counterVal' => '221.069',
                'num'        => "\u{2116}" . '335699',
                'period'     => date('Y-m-t'),
            ],
            //HotWater
            [
                'counterNum' => '1168942',
                'counterVal' => '174.313',
                'num'        => "\u{2116}" . '318542',
                'period'     => date('Y-m-t'),
            ]
        ],
    ]
];

$result = file_get_contents($url, false, stream_context_create(array(
    'http' => array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded; charset=UTF-8',
        'content' => http_build_query($params_to_get)
    )
)));

echo $result;

var_export(json_decode($result, true));