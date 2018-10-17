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
        //Проверяем лицевой счет
        $isNewUser = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguCheckNewUser',
            'items' => [
                'code'   => $electricityPayCode,
            ],
        ];
        $res = self::sendRequest($isNewUser);

        if (isset($res['error']) && $res['error'] !== 0) {
            $result['errorCode'] = 0;
            $result['errorMsg'] = 'Номер лицевого счета указан в неправильном формате.';
            return $result;
        }
        if (isset($res['result']['nn_ans_check_usr']) && $res['result']['nn_ans_check_usr'] != 1) {
            $result['errorCode'] = 0;
            $result['errorMsg'] = 'Лицевой счет ' . $electricityPayCode . ' не зарегистрирован в системе PGU.';
            return $result;
        }

        //Проверяем номер счетчика
        $checkMeter = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguCheckShetch',
            'items' => [
                'code'       => '50344-037-29',
                'nn_schetch' => $meterID
            ],
        ];
        $res = self::sendRequest($checkMeter);

        if (isset($res['error']) && $res['error'] !== 0) {
            $result['errorCode'] = 1;
            $result['errorMsg'] = 'Неизвестная ошибка при проверке номера счетчика.';
            return $result;
        }

        $id_kng = $res['result']['id_kng'] ?? null;
        $schema = $res['result']['schema'] ?? null;

        if ($id_kng == null || $schema == null) {
            $result['errorCode'] = 1;
            $result['errorMsg'] = 'Счетчик номер ' . $meterID . ' не зарегистрирован в системе PGU.';
            return $result;
        };

        //Получаем информацию об адресе установки и типе счетчика
        $getGeneralInfo = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguGeneralInfo',
            'items' => [
                'code'   => $electricityPayCode,
                'sсhema' => $schema,
                'id_kng' => $id_kng,
            ]
        ];
        $res = self::sendRequest($getGeneralInfo);

        if (isset($res['error']) && $res['error'] !== 0) {
            $result['errorMsg'] = 'Неизвестная ошибка при получении адреса установки счетчика и типа счетчика.';
        }
        if (isset($res['result']['error']) || (isset($res['result']['result']) && $res['result']['result'] === 0)) {
            $result['errorMsg'] = 'Не удалось получить адрес установки счетчика и тип счетчика';
        }

        $result['address'] = $res['result']['addr'] ?? 'unknown';
        $result['meterType'] = $res['result']['nm_uchet'] ?? 'unknown';

        //Получаем информацию о дате установки счетчика
        $getSetupDate = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguGetLastPok',
            'items' => [
                'code'   => $electricityPayCode,
                'sсhema' => $schema,
                'id_kng' => $id_kng,
            ]
        ];
        $res = self::sendRequest($getSetupDate);

        if (isset($res['error']) && $res['error'] !== 0) {
            $result['errorMsg'] = 'Неизвестная ошибка при получении даты установки счетчика';
        }
        if (isset($res['result']['error']) || (isset($res['result']['result']) && $res['result']['result'] === 0)) {
            $result['errorMsg'] = 'Не удалось получить дату установки счетчика';
        }

        $result['setupDate'] = $res['result']['sh_ust'] ?? 'unknown';

        //Получаем информацию о межповерочном интервале
        $getMPI = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguGetSchetch',
            'items' => [
                'code'   => $electricityPayCode,
                'sсhema' => $schema ,
                'id_kng' => $id_kng,
            ]
        ];
        $res = self::sendRequest($getMPI);

        if (isset($res['error']) && $res['error'] !== 0) {
            $result['errorMsg'] = 'Неизвестная ошибка при получении года окончания межповерочного интервала';
        }
        if (isset($res['result']['error']) || (isset($res['result']['result']) && $res['result']['result'] === 0)) {
            $result['errorMsg'] = 'Не удалось получить год окончания межповерочного интервала';
        }

        $result['MPI'] = $res['result']['dt_mpi'] ?? 'unknown';

        return $result;
    }
}
