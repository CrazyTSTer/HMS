<?php

class Electricity
{
    const CMD_CODE = [
        'getSerialNumber'            => "2F",
        'getManufacturedDate'        => "66",
        'getFirmWareVersion'         => "28",
        'getBatteryVoltage'          => "29",
        'getLastSwitchOn'            => "2C",
        'getLastSwitchOff'           => "2B",
        'getCurrentCircuitValues'    => "63",
        'getCurrentPowerValues'      => "27",
        'getCurrentPower'            => "26",
        //'getPowerValuesByMonth'      => "32",
    ];

    const CFG_NAME = 'ElectricityMeterInfo';

    /** @var  Config */
    private $cfg;

    public function actionESPWhoAmI()
    {
        $host = Vars::get('host', null);
        $port = Vars::get('port', null);

        if (!$host || !$port) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::STATUS_FAIL);
        }
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, Utils::STATUS_SUCCESS, true);

        $this->cfg = Config::getConfig(self::CFG_NAME);
        $this->cfg->set('host', $host);
        $this->cfg->set('port', $port);
        $this->cfg->save();
    }
}