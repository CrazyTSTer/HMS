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
    const GET_CURRENT_DATE_TIME      = 'getCurrentDateTime';

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
        self::GET_CURRENT_DATE_TIME      => '21',
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

    /**
     * @cmdNames commands array
     * @return array
     */
    public function executeCommands($cmdNames, $attempts = 10)
    {
        foreach ($cmdNames as $cmdName) {
            if ($cmdName == self::GET_POWER_VALUES_BY_MONTH) {
                foreach (self::CMD_MONTH_SUBCODE as $month => $subCode) {
                    $result[$cmdName][$month] = $this->sendRequest($this->cfg->get(ElectricityMetersSettings::COMMANDS . '/' . $cmdName . '/' . $month), $attempts);
                }
            } else {
                $result[$cmdName] = $this->sendRequest($this->cfg->get(ElectricityMetersSettings::COMMANDS . '/' . $cmdName), $attempts);
            }
        }

        return $result;
    }

    private function sendRequest($cmd, $attempts)
    {
        $result = NULL;
        for ($i = 0; $i < $attempts; $i++) {
            $fp = fsockopen($this->cfg->get(ElectricityMetersSettings::HOST), $this->cfg->get(ElectricityMetersSettings::PORT), $errno, $errstr, 30);
            if (!$fp) {
                continue;
                //Utils::reportError(__CLASS__, 'Can\'t connect to Electricity meter. ' . $errno . ':' . $errstr, $this->debug);
            }
            fwrite($fp, hex2bin($cmd));
            $response = fgets($fp);
            fclose($fp);

            $responseDecoded = strtoupper(bin2hex($response));

            $responseCRC = substr($responseDecoded, -4);
            $calcResponseCRC = Utils::crc16_modbus(substr($responseDecoded, 0, strlen($responseDecoded) - 4));

            if ($responseCRC == $calcResponseCRC) {
                $result = substr($responseDecoded, 10, -4);
                break;
            }
        }
        return $result;
    }
}
