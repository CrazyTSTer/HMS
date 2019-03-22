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
        self::GET_POWER_VALUES_BY_MONTH  => '32',
    ];

    const CMD_MONTH_SUBCODE = [
        'jan' => '00',
        'feb' => '01',
        'mar' => '02',
        'apr' => '03',
        'may' => '04',
        'jun' => '05',
        'jul' => '06',
        'aug' => '07',
        'sep' => '08',
        'oct' => '09',
        'nov' => '0A',
        'dec' => '0B',
        /*'month0C' => '0C',
        'month0D' => '0D',
        'month0E' => '0E',
        'month0F' => '0F',
        'month10' => '10',
        'month11' => '11',
        'month12' => '12',
        'month13' => '13',
        'month14' => '14',
        'month15' => '15',
        'month16' => '16',
        'month17' => '17',
        'month18' => '18',
        'month19' => '19',
        'month1A' => '1A',
        'month1B' => '1B',
        'month1C' => '1C',
        'month1E' => '1D',
        'month1F' => '1E',*/
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

    public function executeCommand($cmdName, $attempts = 10)
    {
        if ($cmdName == self::GET_POWER_VALUES_BY_MONTH) {
            foreach (self::CMD_MONTH_SUBCODE as $month => $subCode) {
                $result[$cmdName][$month] = $this->sendRequest($this->cfg->get(ElectricityMetersSettings::COMMANDS . '/' . $cmdName . '/' . $month));
            }
        } else {
            $result[$cmdName] = $this->sendRequest($this->cfg->get(ElectricityMetersSettings::COMMANDS . '/' . $cmdName));
        }

        return $result;
    }

    public function sendRequest($cmd, $attempts = 10)
    {
        for ($i = 0; $i < $attempts; $i++) {
            $fp = fsockopen($this->cfg->get(ElectricityMetersSettings::HOST), $this->cfg->get(ElectricityMetersSettings::PORT), $errno, $errstr, 30);
            if (!$fp) {
                Utils::reportError(__CLASS__, 'Can\'t connect to Electricity meter. ' . $errno . ':' . $errstr, $this->debug);
            }
            fwrite($fp, hex2bin($cmd));
            $response = fgets($fp);
            fclose($fp);

            $responseCRC = bin2hex(substr($response, -2));
            $calcResponseCRC = Utils::crc16_modbus(substr($response, 0, strlen($response) - 2), false);

            if ($responseCRC == $calcResponseCRC) {
                $result = [
                    'address'  => bin2hex(substr($response, 0, 4)),
                    'cmd_code' => bin2hex(substr($response, 4, 1)),
                    'data'     => /*bin2hex(*/substr($response, 5, -2)//),

                ];
                break;
            } else {
                $result = NULL;
            }
        }
        return $result;
    }
}
