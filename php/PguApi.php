<?php

class PguApi
{
    CONST URL = 'https://www.mos.ru/pgu/common/ajax/index.php';

    public static function getWaterMetersInfo($paycode, $flat, $debug)
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

        if (!$result || (isset($result['error']) && !isset($result['code']))) {
            Utils::reportError(__CLASS__, 'Failed to get data from PGU.MOS.RU', $debug);
        } elseif (isset($result['code'])) {
            $error = $result['error'] ?? '';
            $info = $result['info'] ?? '';
            $msg = ($error === $info) ? $error : $error . '. ' . $info;
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, $msg . ' Code: ' . $result['code']);
        }

        return $result;
    }

    public static function sendWaterMetersData($paycode, $flat, $meters, $debug)
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

        if (isset($result['code']) && $result['code'] !== 0) {
            $error = $result['error'] ?? '';
            $info = $result['info'] ?? '';
            $msg = ($error === $info) ? $error : $error . '. ' . $info;
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, $msg . ' Code: ' . $result['code']);
        } elseif (isset($result['info']) && isset($result['code']) && $result['code'] === 0) {
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result['info']);
        } else {
            Utils::reportError(__CLASS__, 'Failed to send data to PGU.MOS.RU', $debug);
        }
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

    public static function checkElectricityPayCode($electricityPayCode)
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
            $result['errorMsg'] = 'Номер лицевого счета указан в неправильном формате.';
            return $result;
        }
        if (isset($res['result']['nn_ans_check_usr']) && $res['result']['nn_ans_check_usr'] != 1) {
            $result['errorMsg'] = 'Лицевой счет ' . $electricityPayCode . ' не зарегистрирован в системе PGU.';
            return $result;
        }
    }

    public static function checkElectricityMeterID($electricityPayCode, $meterID)
    {
        //Проверяем номер счетчика
        $checkMeter = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguCheckShetch',
            'items' => [
                'code'       => $electricityPayCode,
                'nn_schetch' => $meterID
            ],
        ];
        $res = self::sendRequest($checkMeter);

        if (isset($res['error']) && $res['error'] !== 0) {
            $result['errorMsg'] = 'Неизвестная ошибка при проверке номера счетчика.';
            return $result;
        }

        $id_kng = $res['result']['id_kng'] ?? null;
        $schema = $res['result']['schema'] ?? null;

        if ($id_kng == null || $schema == null) {
            $result['errorMsg'] = 'Счетчик номер ' . $meterID . ' не зарегистрирован в системе PGU.';
            return $result;
        };
    }

    public static function getElectricityMeterInfo($electricityPayCode, $meterID)
    {
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
            return $result;
        }
        if (isset($res['result']['error']) || (isset($res['result']['result']) && $res['result']['result'] === 0)) {
            $result['errorMsg'] = 'Не удалось получить адрес установки счетчика и тип счетчика';
            return $result;
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
            return $result;
        }
        if (isset($res['result']['error']) || (isset($res['result']['result']) && $res['result']['result'] === 0)) {
            $result['errorMsg'] = 'Не удалось получить дату установки счетчика';
            return $result;
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
            return $result;
        }
        if (isset($res['result']['error']) || (isset($res['result']['result']) && $res['result']['result'] === 0)) {
            $result['errorMsg'] = 'Не удалось получить год окончания межповерочного интервала';
            return $result;
        }

        $result['MPI'] = $res['result']['dt_mpi'] ?? 'unknown';

        return $result;
    }
}
