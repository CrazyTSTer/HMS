<?php
/**
 * Created by PhpStorm.
 * User: crazytster
 * Date: 10.07.18
 * Time: 17:45
 */

class Settings
{
    private $debug;

    public function __construct($debug)
    {
        $this->debug = $debug;
    }

    public function actionGetPayCodeInfo()
    {
        if (!Vars::check('paycode')) {
            Utils::reportError(__CLASS__, 'PayCode should be passed', $this->debug);
        }

        $paycode = Vars::getPostVar('paycode', null);
        if (!$paycode) {
            Utils::reportError(__CLASS__, 'Passed empty PayCode', $this->debug);
        }

        if (!Vars::check('flat')) {
            Utils::reportError(__CLASS__, 'Flat number should be passed', $this->debug);
        }

        $flat = Vars::getPostVar('flat', null);
        if (!$flat) {
            Utils::reportError(__CLASS__, 'Passed empty Flat number', $this->debug);
        }

        $url = 'https://www.mos.ru/pgu/common/ajax/index.php';
        $params = [
            'ajaxModule' => 'Guis',
            'ajaxAction' => 'getCountersInfo',
            'items' => [
                'paycode' => $paycode,
                'flat' => $flat,
            ]
        ];

        $result = file_get_contents($url, false, stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded; charset=UTF-8',
                'content' => http_build_query($params)
            )
        )));

        $result = json_decode($result, true);

        if (isset($result['code']) || isset($result['error'])) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, $result['info']);
        }

        if ($result) {
            $address['district'] = ($result['address']['okrug'] ?? '-') . ' / ' . ($result['address']['district'] ?? '-');
            $address['street'] =  $result['address']['street'] ?? '-';
            $address['house'] =  $result['address']['house'] ?? '-';
            $address['building'] =  $result['address']['korpus'] ?? '-';
            $address['flat'] =  $result['address']['flat'] ?? '-';

            if (isset($result['counter'])) {
                foreach($result['counter'] as $value) {
                    $meters[]['id'] = $value['counterId'] ?? '-';
                    $meters[]['type'] = $value['type'] ?? '-';
                    $meters[]['number'] = $value['num'] ?? '-';
                    $meters[]['checkup'] = $value['checkup'] ?? '-';
                }
            } else {
                $meters = [];
            }

            $ret = [
                'address' => $address,
                'meters'  => $meters
            ];
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
        }

        Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Failed to get data from PGU.MOS.RU');
    }
}