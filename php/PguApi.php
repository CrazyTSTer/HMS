<?php

class PguApi
{
    CONST URL = 'https://www.mos.ru/pgu/common/ajax/index.php';

    //Water
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

        if (!$result || isset($result['error']) || isset($result['code']) || isset($result['info'])) {
            $error = $result['error'] ?? '';
            $info = $result['info'] ?? '';
            $code = $result['code'] ?? '';
            if ($error == '' && $info == '' && $code == '') {
                $msg = 'Failed to get data from PGU.MOS.RU';
            } else {
                $msg = ($error === $info) ? $error : $error . '. ' . $info . ' Code: ' . $code;
            }

            Utils::unifiedExitPoint(Utils::STATUS_FAIL, $msg);
        }

        return $result;
    }

    public static function sendWaterMetersData($paycode, $flat, $meters)
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

        if (isset($result['code']) && $result['code'] === 0) {
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result['info'] ?? 'Data sent successfully');
        } else {
            $error = $result['error'] ?? '';
            $info = $result['info'] ?? '';
            $code = $result['code'] ?? '';
            if ($error == '' && $info == '' && $code == '') {
                $msg = 'Failed to send data to PGU.MOS.RU';
            } else {
                $msg = ($error === $info) ? $error : $error . '. ' . $info . ' Code: ' . $code;
            }

            Utils::unifiedExitPoint(Utils::STATUS_FAIL, $msg);
        }
    }

    //Electricity
    public static function checkElectricityPayCode($electricityPayCode)
    {
        $isNewUser = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguCheckNewUser',
            'items' => [
                'code'   => $electricityPayCode,
            ],
        ];

        $res = self::sendRequest($isNewUser);

        if (isset($res['result']['nn_ans_check_usr'])) {
            if ($res['result']['nn_ans_check_usr'] !== '1') {
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Unknown paycode');
            }
        } else {
            $error = $res['error'] ?? '';
            $result = $res['result'] ?? '';
            if ($result === -99) {
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Invalid paycode format');
            } else {
                if ($error == '' && $result == '') {
                    $msg = 'Failed to check electricityPayCode at PGU.MOS.RU';
                } else {
                    $msg = $error . $result;
                }
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, $msg);
            }
        }
    }

    public static function checkElectricityMeterID($electricityPayCode, $meterID)
    {
        $checkMeter = [
            'ajaxModule' => 'Mosenergo',
            'ajaxAction' => 'qMpguCheckShetch',
            'items' => [
                'code'       => $electricityPayCode,
                'nn_schetch' => $meterID
            ],
        ];

        $res = self::sendRequest($checkMeter);

        if (isset($res['result']['id_kng']) && isset($res['result']['schema'])) {
            if ($res['result']['id_kng'] && $res['result']['schema']) {
                return $res['result'];
            } else {
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Invalid meterID');
            }
        } else {
            $error = $res['error'] ?? '';
            $result = $res['result'] ?? '';

            if ($result === -99) {
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Invalid paycode format');
            } elseif (!$result && $error === 0) {
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Invalid meterID');
            } else {
                if ($error == '' && $result == '') {
                    $msg = 'Failed to check electricityMeterID at PGU.MOS.RU';
                } else {
                    $msg = $error . $result;
                }
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, $msg);
            }
        }
    }

    public static function getElectricityMeterInfo($electricityPayCode, $schema, $id_kng)
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

        $result['address'] = preg_replace('/\s+/', ' ', $res['result']['addr'] ?? null);
        $result['meterType'] = $res['result']['nm_uchet'] ?? null;

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

        $result['setupDate'] = $res['result']['sh_ust'] ?? null;

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

        $result['MPI'] = $res['result']['dt_mpi'] ?? null;

        $result = array_filter($result);

        if (count($result) != 4) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Failed to get main electricityMeter info');
        }

        return $result;
    }

    //Common
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
}
