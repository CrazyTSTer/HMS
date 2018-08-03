<?php

class Settings
{
    private $debug;
    /** @var  Config */
    private $cfg;

    public function __construct($debug)
    {
        $this->debug = $debug;
        $this->cfg = Config::getConfig('Water');
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
                        'checkup' => date("d-m-Y",strtotime($value['checkup'])) ?? '-',
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
        $dataToSave = Vars::get('dataToSave', null);
        $this->cfg->set(null, $dataToSave);
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Saved');
    }

    public function actionResetWaterSettings()
    {
        $this->cfg->drop('payCode');
        $this->cfg->drop('flat');
        $this->cfg->drop('address');
        $this->cfg->drop('meters');
        $this->cfg->set('save_status', false);
        $this->cfg->save();

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, '');
    }

    public function actionGetSettingsFromConfig()
    {
        /*$ret = [
            'address' => $this->cfg->get('address'),
            'meters'  => $this->cfg->get('meters'),
            'paycode' => $this->cfg->get('paycode'),
            'flat'    => $this->cfg->get('flat'),
        ];*/
        $ret = $this->cfg->get();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }
}