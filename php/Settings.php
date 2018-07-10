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
        /*if (!Vars::check('paycode')) {
            Utils::reportError(__CLASS__, 'PayCode should be passed', $this->debug);
        }*/

        $paycode = Vars::get('paycode', null);
        /*if (!$paycode) {
            Utils::reportError(__CLASS__, 'Passed empty PayCode', $this->debug);
        }

        if (!Vars::check('flat')) {
            Utils::reportError(__CLASS__, 'Flat number should be passed', $this->debug);
        }*/

        $flat = Vars::get('flat', null);
        /*if (!$flat) {
            Utils::reportError(__CLASS__, 'Passed empty Flat number', $this->debug);
        }*/

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
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, $result);
        }

        if ($result) {
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result);
        }

        Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Failed to get data from PGU.MOS.RU');
    }
}