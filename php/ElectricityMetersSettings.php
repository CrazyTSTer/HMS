<?php
/**
 * Created by PhpStorm.
 * User: igribkov
 * Date: 01.03.19
 * Time: 16:27
 */
class ElectricityMetersSettings extends CommonSettings
{
    const CFG_NAME = 'ElectricityMetersConfig';
    const HOST     = 'host';
    const PORT     = 'port';
    const COMMANDS = 'commands';
    const PAY_CODE = 'paycode';
    const METER_ID = 'meterID';

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

            foreach (ElectricityStat::CMD_CODE as $cmdName => $cmdCode) {
                $cmdPart = $hexAddress . $cmdCode;
                if ($cmdName == ElectricityStat::GET_POWER_VALUES_BY_MONTH) {
                    foreach (ElectricityStat::CMD_MONTH_SUBCODE as $month => $subCode) {
                        $cmd = $cmdPart . $subCode . Utils::crc16_modbus($cmdPart . $subCode);
                        $result[$cmdName][$month] = $cmd ;
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
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, Utils::STATUS_SUCCESS, true);

        $this->cfg->set(self::HOST, $host);
        $this->cfg->set(self::PORT, $port);
        $this->cfg->save();
    }
}
