<?php
/**
 * Created by PhpStorm.
 * User: igribkov
 * Date: 01.03.19
 * Time: 16:27
 */
class ElectricityMetersSettings extends CommonSettings
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
    const GET_CURRENT_DATE_TIME      = 'getCurrentDateTime';
    const GET_POWER_VALUES_BY_MONTH  = 'getPowerValuesByMonth';

    const CMD_CODE = [
        self::GET_CURRENT_DATE_TIME      => '21',
        self::GET_CURRENT_POWER          => '26',
        self::GET_CURRENT_POWER_VALUES   => '27',
        self::GET_FIRMWARE_VERSION       => '28',
        self::GET_BATTERY_VOLTAGE        => '29',
        self::GET_LAST_SWITCH_OFF        => '2B',
        self::GET_LAST_SWITCH_ON         => '2C',
        self::GET_SERIAL_NUMBER          => '2F',
        self::GET_POWER_VALUES_BY_MONTH  => '32',
        self::GET_CURRENT_CIRCUIT_VALUES => '63',
        self::GET_MANUFACTURED_DATE      => '66',
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

    const CFG_NAME         = 'ElectricityMetersConfig';
    const HOST             = 'host';
    const PORT             = 'port';
    const COMMANDS         = 'commands';
    const PAY_CODE         = 'paycode';
    const METER_ID         = 'meterID';
    const REQUEST_ATTEMPTS = 'requestAttempts';

    private $debug;

    public function __construct($debug)
    {
        $this->debug = $debug;
        parent::__construct(self::CFG_NAME);
    }

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

        PguApi::checkElectricityPayCode($electricityPayCode);
        $result = PguApi::checkElectricityMeterID($electricityPayCode, $meterID);
        $result = PguApi::getElectricityMeterInfo($electricityPayCode, $result['schema'], $result['id_kng']);

        $result[self::PAY_CODE] = $electricityPayCode;
        $result[self::METER_ID] = $meterID;

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result);
    }

    public function actionGenerateElectricityMeterCommands()
    {
        $meterID = $this->cfg->get(self::METER_ID);
        if ($meterID) {
            $addr = substr($meterID, -6);
            $hexAddress = sprintf("%08X", $addr);

            foreach (self::CMD_CODE as $cmdName => $cmdCode) {
                $cmdPart = $hexAddress . $cmdCode;
                if ($cmdName == self::GET_POWER_VALUES_BY_MONTH) {
                    foreach (self::CMD_MONTH_SUBCODE as $month => $subCode) {
                        $cmd = $cmdPart . $subCode . Utils::crc16_modbus($cmdPart . $subCode);
                        $result[$cmdName][$month] = $cmd;
                    }
                } else {
                    $cmd = $cmdPart . Utils::crc16_modbus($cmdPart);
                    $result[$cmdName] = $cmd;
                }
            }

            $this->cfg->set(self::COMMANDS, $result);
            $this->cfg->save();
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Meter commands successfully generated');
        } else {
            Utils::unifiedExitPoint(
                Utils::STATUS_FAIL,
                'MeterID is empty<br>Setup MetersID and save it to config'
            );
        }
    }

    public function actionESPWhoAmI()
    {
        $host = Vars::get(self::HOST, null);
        $port = Vars::get(self::PORT, null);

        if (!$host || !$port) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::STATUS_FAIL);
        }
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, ['ESPCloseConnection' => false, 'ESPSendNewLine' => false], true);

        $this->cfg->set(self::HOST, $host);
        $this->cfg->set(self::PORT, $port);
        $this->cfg->save();
    }
}
