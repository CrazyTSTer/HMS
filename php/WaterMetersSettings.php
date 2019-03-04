<?php
/**
 * Created by PhpStorm.
 * User: igribkov
 * Date: 01.03.19
 * Time: 16:22
 */
class WaterMetersSettings extends CommonSettings
{
    const CFG_NAME = 'WaterMetersConfig';

    private $debug;

    public function __construct($debug)
    {
        $this->debug = $debug;
        parent::__construct(self::CFG_NAME);
    }

    public function actionGetWaterMetersInfoFromPgu()
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

        $result = PguApi::getWaterMetersInfo($paycode, $flat);

        $address['district'] = ($result['address']['okrug'] ?? '-') . ' / ' . ($result['address']['district'] ?? '-');
        $address['street'] = $result['address']['street'] ?? '-';
        $address['house'] =  $result['address']['house'] ?? '-';
        $address['building'] = $result['address']['korpus'] ?? '-';
        $address['flat'] = $result['address']['flat'] ?? '-';

        if (isset($result['counter'])) {
            foreach ($result['counter'] as $value) {
                $meters[] = [
                    'counterNum' => $value['counterId'] ?? '-',
                    'num'        => $value['num'] ?? '-',
                    'type'       => $value['type'] ?? '-',
                    'checkup'    => date("d-m-Y", strtotime($value['checkup'])) ?? '-',
                ];
            }
        } else {
            $meters = [];
        }

        $ret = [
            'paycode' => $result['paycode'],
            'flat'    => $result['address']['flat'],
            'address' => $address,
            'meters'  => $meters,
        ];

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }
}
