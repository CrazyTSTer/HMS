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

    //Water
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

        $result = PguApi::getWaterMetersInfo($paycode, $flat, $this->debug);

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


    public function actionGetWaterMetersInfoFromConfig()
    {
        $ret = $this->cfg->get();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }

    public function actionSaveWaterMetersInfoToConfig()
    {
        $dataToSave = json_decode(Vars::get('dataToSave', null), true);
        $this->cfg->set(null, $dataToSave);
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Saved');
    }

    public function actionEraseWaterMetersInfoFromConfig()
    {
        $this->cfg->drop();
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Erased');
    }

    //Electricity
    public function actionGetElectricityMeterInfoFromPgu()
    {
        if (!Vars::check('electricityPayCode')) {
            Utils::reportError(__CLASS__, 'PayCode should be passed', $this->debug);
        }

        $electricityPayCode = Vars::getPostVar('electricityPayCode', null);
        if (!$electricityPayCode) {
            Utils::reportError(__CLASS__, 'Passed empty PayCode', $this->debug);
        }

        if (!Vars::check('meterID')) {
            Utils::reportError(__CLASS__, 'meterID should be passed', $this->debug);
        }

        $meterID = Vars::getPostVar('meterID', null);
        if (!$meterID) {
            Utils::reportError(__CLASS__, 'Passed empty meterID', $this->debug);
        }

        $result = PguApi::getElectricityMeterInfo($electricityPayCode, $meterID);

        if (isset($result['errorMsg'])) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, $result['errorMsg']);
        }

        var_export($result);
    }
}
