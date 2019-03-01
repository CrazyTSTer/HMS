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

    private static $debug;

    public function __construct($debug)
    {
        self::$debug = $debug;
        parent::__construct(self::CFG_NAME);
    }

    public static function actionGetElectricityMeterInfoFromPgu()
    {
        if (!Vars::check('electricityPayCode')) {
            Utils::reportError(__CLASS__, 'PayCode should be passed', self::$debug);
        }

        $electricityPayCode = Vars::getPostVar('electricityPayCode', null);
        if (!$electricityPayCode) {
            Utils::reportError(__CLASS__, 'Passed empty PayCode', self::$debug);
        }

        if (!Vars::check('meterID')) {
            Utils::reportError(__CLASS__, 'meterID should be passed', self::$debug);
        }

        $meterID = Vars::getPostVar('meterID', null);
        if (!$meterID) {
            Utils::reportError(__CLASS__, 'Passed empty meterID', self::$debug);
        }

        PguApi::checkElectricityPayCode($electricityPayCode);
        $result = PguApi::checkElectricityMeterID($electricityPayCode, $meterID);
        $result = PguApi::getElectricityMeterInfo($electricityPayCode, $result['schema'], $result['id_kng']);

        $result['paycode'] = $electricityPayCode;
        $result['meterID'] = $meterID;

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result);
    }

    public static function actionGenerateElectricityMeterCommands()
    {
        $meterID = ElectricityMetersSettings::$cfg->get('meterID');
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

            self::$cfg->set('commands', $result);
            self::$cfg->save();
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Команды счетчика успешно сгенерированы.');
        } else {
            Utils::unifiedExitPoint(
                Utils::STATUS_FAIL,
                'В конфиг-файле не указан номер счетчика.<br>Укажите номер счетчика и сохраните конфиг.'
            );
        }
    }

    public static function actionESPWhoAmI()
    {
        $host = Vars::get('host', null);
        $port = Vars::get('port', null);

        if (!$host || !$port) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::STATUS_FAIL);
        }
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, Utils::STATUS_SUCCESS, true);

        self::$cfg->set('host', $host);
        self::$cfg->set('port', $port);
        self::$cfg->save();
    }
}
