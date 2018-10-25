<?php

class Electricity
{
    const CMD_CODE = [
        'getSerialNumber'            => 0x2F,
        'getManufacturedDate'        => 0x66,
        'getFirmWareVersion'         => 0x28,
        'getBatteryVoltage'          => 0x29,
        'getLastSwitchOn'            => 0x2C,
        'getLastSwitchOff'           => 0x2B,
        'getCurrentCircuitValues'    => 0x63,
        'getCurrentPowerValues'      => 0x27,
        'getCurrentPower'            => 0x26,
        //'getPowerValuesByMonth'      => 0x32,
    ];

    /** @var  Config */
    private $cfg;

    public static function generateCommand($hexAddress, $cmd_code)
    {
        $tmp = $hexAddress;
        $tmp[] = $cmd_code;

        $crc = Utils::crc16_modbus($tmp);
        foreach ($crc as $byte) {
            $tmp[] = $byte;
        }
        array_walk($tmp, function (&$item) {
            $item = sprintf("%02X", $item);
        });

        $res = '\x' . implode('\x', $tmp);
        return $res;
    }

    public function test()
    {
        echo "TEST\n";
    }

    public function actionWhoAmI()
    {
        $host = Vars::getPostVar('host', null);
        $port = Vars::getPostVar('port', null);

        if (!$host && !$port) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'TEST DATA');
        }

        $this->cfg = Config::getConfig('ElectricityMeterInfo.cfg');
        $this->cfg->set('host', $host);
        $this->cfg->set('port', $port);
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'TEST DATA');
    }
}