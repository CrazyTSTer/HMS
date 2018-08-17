<?php

class PguApi
{
    private static $url = 'https://www.mos.ru/pgu/common/ajax/index.php';

    public static function getWaterMetersInfo($paycode, $flat)
    {
        $getParams = [
            'ajaxModule' => 'Guis',
            'ajaxAction' => 'getCountersInfo',
            'items' => [
                'paycode' => $paycode,
                'flat'    => $flat,
            ]
        ];

        $result = self::sendRequest($getParams);
        return $result;
    }

    public static function sendMetersData($meters)
    {
        $setParams = [
            'ajaxModule' => 'Guis',
            'ajaxAction' => 'addCounterInfo',
            'items' => [
                'paycode' => '3130158061',
                'flat' => '324',
                'indications' => [
                    //ColdWater
                    [
                        'counterNum' => '1168941',
                        'counterVal' => '231,123',
                        'num'        => "\u{2116}" . '335699',
                        'period'     => ''//date('Y-m-t'),
                    ],
                    //HotWater
                    [
                        'counterNum' => '1168942',
                        'counterVal' => '177,123',
                        'num'        => "\u{2116}" . '318542',
                        'period'     => ''//date('Y-m-t'),
                    ]
                ],
            ]
        ];
    }

    private static function sendRequest($params)
    {
        $result = file_get_contents(
            self::$url,
            false,
            stream_context_create(
                [
                    'http' => [
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded; charset=UTF-8',
                        'content' => http_build_query($params),
                    ]
                ]
            )
        );
        return json_decode($result, true);
    }
}
