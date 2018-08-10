<?php

class Settings
{
    private $debug;
    /** @var  Config */
    private $cfg;

    public function __construct($debug, $cfgName)
    {
        $this->debug = $debug;
        $this->cfg = Config::getConfig($cfgName);
    }

    public function actionGetWaterMetersInfo()
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
                    $meters[] = [
                        'id'      => $value['counterId'] ?? '-',
                        'type'    => $value['type'] ?? '-',
                        'number'  => $value['num'] ?? '-',
                        'checkup' => date("d-m-Y", strtotime($value['checkup'])) ?? '-',
                    ];
                }
            } else {
                $meters = [];
            }

            $ret = [
                'address' => $address,
                'meters'  => $meters,
                'paycode' => $paycode,
                'flat'    => $flat,
            ];

            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
        }

        Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Failed to get data from PGU.MOS.RU');
    }

    public function actionSaveWaterSettings()
    {
        $dataToSave = json_decode(Vars::get('dataToSave', null), true);
        $this->cfg->set(null, $dataToSave);
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Saved');
    }

    public function actionResetWaterSettings()
    {
        $this->cfg->drop();
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, '');
    }

    public function actionGetSettingsFromConfig()
    {
        $ret = $this->cfg->get();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }
}