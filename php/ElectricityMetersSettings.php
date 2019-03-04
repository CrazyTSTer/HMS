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

        $result['paycode'] = $electricityPayCode;
        $result['meterID'] = $meterID;

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result);
    }

    public function actionGenerateElectricityMeterCommands()
    {
        $meterID = $this->cfg->get('meterID');
        if ($meterID) {
            $addr = substr($meterID, -6);
            $hexAddress = sprintf("%08X", $addr);

            foreach (Electricity::CMD_CODE as $cmdName => $cmdCode) {
                if ($cmdName == Electricity::GET_POWER_VALUES_BY_MONTH) {
                    foreach ($cmdCode as $month => $code) {
                        $result[$cmdName][$month] = $hexAddress . $code . Utils::crc16_modbus($hexAddress . $code);
                    }
                } else {
                    $result[$cmdName] = $hexAddress . $cmdCode . Utils::crc16_modbus($hexAddress . $cmdCode);
                }
            }

            $this->cfg->set('commands', $result);
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
        $host = Vars::get('host', null);
        $port = Vars::get('port', null);

        if (!$host || !$port) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::STATUS_FAIL);
        }
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, Utils::STATUS_SUCCESS, true);

        $this->cfg->set('host', $host);
        $this->cfg->set('port', $port);
        $this->cfg->save();
    }
}
