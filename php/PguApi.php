<?php

class PguApi
{
    const COOKIE_FILE = 'cookie.tmp';
    const CURL_SETTINGS = [
        CURLOPT_HEADER         => false,
        CURLOPT_NOBODY         => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEJAR      => self::COOKIE_FILE,
        CURLOPT_COOKIEFILE     => self::COOKIE_FILE,
    ];


    const MY_PGU = 'https://my.mos.ru/my';
    const URL = 'https://www.mos.ru/pgu/common/ajax/index.php';
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

        $result = self::sendRequest($getParams, true);

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

        $result = self::sendRequest($setParams, true);

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

        if (isset($res['result']['sh_ust'])) {
            $dt = new DateTime($res['result']['sh_ust']);
            $result['setupDate'] = $dt->format('d-m-Y');
        } else {
            $result['setupDate'] = null;
        }


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
        $result['numberOfDigit'] = $res['result']['sh_znk'] ?? null;

        $result = array_filter($result);

        if (count($result) != 5) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Failed to get main electricityMeter info');
        }

        return $result;
    }

    //Common
    private static function sendRequest($params, $isAuthNeed = false)
    {
        if (!$isAuthNeed) {
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
        } else {
            //Clear cookies
            file_put_contents(self::COOKIE_FILE, "");
            //Get first link follow to auth page
            $res = file_get_contents(self::MY_PGU);
            preg_match('/value="(.*)"/', $res, $url);

            //Init cURL session
            $ch = curl_init();
            curl_setopt_array($ch, self::CURL_SETTINGS);
            //Go to auth page
            curl_setopt($ch, CURLOPT_URL, $url[1]);
            curl_exec($ch);
            //Logging in
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'login=crazytster@gmail.com&password=dbybkfwtnfn2');
            curl_exec($ch);
            //curl_close($ch);

            //Authorized OK, send request now
            //$ch = curl_init();
            //curl_setopt_array($ch, self::CURL_SETTINGS);
            curl_setopt($ch, CURLOPT_URL, self::URL);
            //curl_setopt($ch, CURLOPT_HEADER, false);
            //curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $result = curl_exec($ch);
            curl_close($ch);
            file_put_contents(self::COOKIE_FILE, "");
        }

        return json_decode($result, true);
    }
}
