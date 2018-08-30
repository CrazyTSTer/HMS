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

    public static function sendMetersData($paycode, $flat, $meters)
    {
        $setParams = [
            'ajaxModule' => 'Guis',
            'ajaxAction' => 'addCounterInfo',
            'items' => [
                'paycode' => $paycode,
                'flat' => $flat,
                'indications' => $meters
            ]
        ];

        $result = self::sendRequest($setParams);
        return $result;
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
