<?php
/**
 * Created by PhpStorm.
 * User: crazytster
 * Date: 13.01.15
 * Time: 19:10
 */

include_once "DB.php";
include_once "Vars.php";
include_once "Parser.php";
include_once "WaterStat.php";
include_once "CommonSettings.php";
include_once "Config.php";
include_once "PguApi.php";
include_once "ElectricityStat.php";
include_once "WaterMetersSettings.php";
include_once "ElectricityMetersSettings.php";

class Utils
{
    const STATUS_FAIL    = 'fail';
    const STATUS_ERROR   = 'error';
    const STATUS_SUCCESS = 'success';

    const UNKNOWN_ACTION    = 'Unknown action';
    const UNKNOWN_PARAMETER = 'Unknown parameter';

    public static function reportError($class, $errorMsg, $debug = false)
    {
        if ($class === 'DB') {
            if (DB::getInstance()->isConnected()) {
                $errorMsg = $errorMsg
                    . DB::getInstance()->getMYSQLErr()
                    . DB::getInstance()->getMYSQLErrNo();
            } else {
                $errorMsg = $errorMsg . '. ' . mysqli_connect_error() . '.' . PHP_EOL . 'Error: ' . mysqli_connect_errno();
            }
        }

        if ($debug) {
            $errorMsg = "Error at class: '{$class}'." . PHP_EOL . $errorMsg;
        } else {
            $errorMsg = 'Please contact to Administrator. Something goes wrong';
        }

        self::unifiedExitPoint(self::STATUS_ERROR, $errorMsg);
    }

    public static function addDataToTemplate($template, $data, $addQuotes = false, $debug = false)
    {
        $re = "/#([a-zA-Z][a-zA-Z0-9_]*)#/";
        preg_match_all($re, $template, $matches);
        foreach ($matches[1] as $value) {
            if (array_key_exists($value, $data)) {
                $replaceString = $addQuotes ? '\'' . $data[$value] . '\'' : $data[$value];
                $template = str_replace("#{$value}#", $replaceString, $template);
            } else {
                die(self::reportError(__CLASS__, "'{$value}' key does not exist in replacement data array for template", $debug));
            }
        }
        return $template;
    }

    public static function unifiedExitPoint($status, $result = '', $fastCGIFinishRequest = false)
    {
        print_r(json_encode(array("status" => $status, "data" => $result)));
        if ($fastCGIFinishRequest) {
            fastcgi_finish_request();
            return;
        }
        exit(0);
    }

    public static function crc16_modbus($data, $pack = true)
    {
        if ($pack) {
            $data = pack('H*', $data);
        }
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]);
            for ($j = 8; $j != 0; $j--) {
                if (($crc & 0x0001) != 0) {
                    $crc >>= 1;
                    $crc ^= 0xA001;
                } else {
                    $crc >>= 1;
                }
            }
        }
        return sprintf("%02X%02X", ($crc & 0xFF), ($crc >> 8));
    }
}
