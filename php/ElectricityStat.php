<?php

class ElectricityStat
{
    const GET_SERIAL_NUMBER          = 'getSerialNumber';
    const GET_MANUFACTURED_DATE      = 'getManufacturedDate';
    const GET_FIRMWARE_VERSION       = 'getFirmWareVersion';
    const GET_BATTERY_VOLTAGE        = 'getBatteryVoltage';
    const GET_LAST_SWITCH_ON         = 'getLastSwitchOn';
    const GET_LAST_SWITCH_OFF        = 'getLastSwitchOff';
    const GET_CURRENT_CIRCUIT_VALUES = 'getCurrentCircuitValues';
    const GET_CURRENT_POWER_VALUES   = 'getCurrentPowerValues';
    const GET_CURRENT_POWER          = 'getCurrentPower';
    const GET_POWER_VALUES_BY_MONTH  = 'getPowerValuesByMonth';

    const CMD_CODE = [
        self::GET_SERIAL_NUMBER          => '2F',
        self::GET_MANUFACTURED_DATE      => '66',
        self::GET_FIRMWARE_VERSION       => '28',
        self::GET_BATTERY_VOLTAGE        => '29',
        self::GET_LAST_SWITCH_ON         => '2C',
        self::GET_LAST_SWITCH_OFF        => '2B',
        self::GET_CURRENT_CIRCUIT_VALUES => '63',
        self::GET_CURRENT_POWER_VALUES   => '27',
        self::GET_CURRENT_POWER          => '26',
        self::GET_POWER_VALUES_BY_MONTH  => [
            'jan' => '3201',
            'feb' => '3202',
            'mar' => '3203',
            'apr' => '3204',
            'may' => '3205',
            'jun' => '3206',
            'jul' => '3207',
            'aug' => '3208',
            'sep' => '3209',
            'oct' => '320A',
            'nov' => '320B',
            'dec' => '320C',
        ],
    ];

    const CFG_NAME = 'ElectricityMetersConfig';

    const COMMANDS = 'commands';

    private $debug;

    /** @var  Config */
    public $cfg;

    public function __construct($debug)
    {
        $this->debug = $debug;
        $this->cfg = Config::getConfig(self::CFG_NAME);
    }

    public function executeCommand($command, $attempts = 10)
    {
        $exec = $this->cfg->get(ElectricityMetersSettings::COMMANDS . '/' . $command);
        $fp = fsockopen($this->cfg->get(ElectricityMetersSettings::HOST), $this->cfg->get(ElectricityMetersSettings::PORT), $errno, $errstr, 30);
        if (!$fp) {
            Utils::reportError(__CLASS__, 'Can\'t connect to Electricity meter. ' . $errno . ':' . $errstr, $this->debug);
        }

        for ($i = 0; $i < $attempts; $i++) {
            fwrite($fp, hex2bin($exec));
            $response = fgets($fp);

            $responseCRC = bin2hex(substr($response, -2));
            $calcResponseCRC = Utils::crc16_modbus(substr($response, 0, strlen($response) - 2), false);
            var_export($responseCRC);
            var_export($calcResponseCRC);
            //die();
            if ($responseCRC == $calcResponseCRC) {
                $result = [
                    'address'  => bin2hex(substr($response, 0, 4)),
                    'cmd_code' => bin2hex(substr($response, 4, 1)),
                    'data'     => substr($response, 5, -2),
                ];
                break;
            } else {
                $result = NULL;
            }
        }

        fclose($fp);
        return $result;
    }
}
