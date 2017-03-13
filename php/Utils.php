<?php
/**
 * Created by PhpStorm.
 * User: crazytster
 * Date: 13.01.15
 * Time: 19:10
 */

include_once "DB.php";
include_once "Vars.php";

class Utils
{
    const STATUS_FAIL    = 'fail';
    const STATUS_ERROR   = 'error';
    const STATUS_SUCCESS = 'success';

    const UNKNOWN_ACTION   = 'Unknown action';

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
            $errorMsg = "Error at class: {$class}." . PHP_EOL . $errorMsg;
        } else {
            $errorMsg = 'Please contact to Administrator. Something goes wrong';
        }

        self::unifiedExitPoint(self::STATUS_ERROR, $errorMsg);
    }

    public static function addDataToTemplate($template, $data, $add_quotes = false, $debug = false)
    {
        $re = "/#([a-zA-Z][a-zA-Z0-9_]*)#/";
        preg_match_all($re, $template, $matches);
        foreach ($matches[1] as $value) {
            if (array_key_exists($value, $data)) {
                $replace_string = $add_quotes ? '\'' . $data[$value] . '\'' : $data[$value];
                $template = str_replace("#{$value}#", $replace_string, $template);
            } else {
                die(self::reportError(__CLASS__, "'{$value}' key does not exist in replacement data array for template", $debug));
            }
        }
        return $template;
    }

    public static function unifiedExitPoint($status, $result)
    {
        print_r(json_encode(array("status" => $status, "data" => $result)));
        DB::getInstance()->disconnect();
        exit(0);
    }
}
