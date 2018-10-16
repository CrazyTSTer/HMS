<?php

class PguApi
{
    CONST URL = 'https://www.mos.ru/pgu/common/ajax/index.php';

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
            self::URL,
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

    public static function getElectricityMeterInfo($electricityPayCode, $meterID)
    {
        $getParams = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguCheckShetch',
            'items' => [
                'code'       => $electricityPayCode,
                'nn_schetch' => $meterID,
            ]
        ];

        $result = self::sendRequest($getParams);

        if (isset($result['error']) && $result['error'] != 0) return 0;

        $id_kng = $result['result']['id_kng'] ?? null;
        $schema = $result['result']['schema'] ?? null;

        if ($id_kng == null || $schema == null) return 0;

        $getParams = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguGeneralInfo',
            'items' => [
                'code'   => $electricityPayCode,
                'sсhema' => $schema,
                'id_kng' => $id_kng,
            ]
        ];

        $result = self::sendRequest($getParams);

        return $result;
    }
}
