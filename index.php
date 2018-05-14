<?php
/**
 * Created by PhpStorm.
 * User: crazytster
 * Date: 14.05.18
 * Time: 17:49
 */
ini_set('display_errors', "1");
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

include_once "php/Utils.php";

class ASMS
{
    const ACTION_SET = 'set';
    const ACTION_GET = 'get';

    private $debug;
    private $action;

    public function init($debug = false)
    {
        setlocale(LC_TIME, 'ru_RU.UTF-8');
        date_default_timezone_set('Europe/Moscow');
        $this->debug = $debug;
        $this->action = Vars::get('action', null);
    }

    public function run()
    {
        switch ($this->action) {
            case self::ACTION_SET:
                WaterStat::actionSet($this->debug);
                break;

            case self::ACTION_GET:
                WaterStat::actionGet($this->debug);
                break;

            default:
                echo file_get_contents("index.html");
        }
    }
}

$asms = new ASMS();
$asms->init(true);
$asms->run();