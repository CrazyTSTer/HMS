<?php
/**
 * Created by PhpStorm.
 * User: crazytster
 * Date: 14.05.18
 * Time: 17:49
 */
ini_set('display_errors', "1");
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
define('SETTINGS_CLASS', 'Settings');

include_once "php/Utils.php";

class ASMS
{
    const HREF_PATH           = 'index.php?target=';
    const LOCAL_PAGE_PATH     = 'static/html/';
    const PAGE_EXT            = '.html';
    const MAIN_PAGE           = 'MainPage';
    const COMMONSTAT_PAGE     = 'CommonStatPage';
    const SETTINGS_PAGE       = 'SettingsPage';
    const WATERSTAT_PAGE      = 'WaterStatPage';

    private $debug;
    private $location;
    private $action;

    public function init($debug = false)
    {
        setlocale(LC_TIME, 'ru_RU.UTF-8');
        date_default_timezone_set('Europe/Moscow');
        $this->debug = $debug;
        $this->location = Vars::get('location', null);
        $this->action = Vars::get('action', null);
    }

    public function run()
    {
        if ($this->location) {
            if (class_exists($this->location)) {
                if ($this->location == SETTINGS_CLASS) {
                    $cfgName = Vars::get('config', null);
                    if (!$cfgName) {
                        Utils::reportError(__CLASS__, "Config name should be passed to Settings class constructor", $this->debug);
                    }
                }
                $obj = new $this->location($this->debug, $cfgName ?? null);
                if (method_exists($obj, $this->action)) {
                    $method = $this->action;
                    $obj->$method();
                } else {
                    Utils::reportError(__CLASS__, "Unknown action '$this->action' for location '$this->location'", $this->debug);
                }
            } else {
                Utils::reportError(__CLASS__, "Unknown location '$this->location'", $this->debug);
            }
        } else {
            $target = Vars::get('target', '');
            $headers = getallheaders();

            switch ($target) {
                case self::COMMONSTAT_PAGE:
                default:
                    $content = file_get_contents(self::LOCAL_PAGE_PATH . self::COMMONSTAT_PAGE . self::PAGE_EXT);
                    break;
                case self::WATERSTAT_PAGE:
                    $content = file_get_contents(self::LOCAL_PAGE_PATH . self::WATERSTAT_PAGE . self::PAGE_EXT);
                    break;

                case self::SETTINGS_PAGE:
                    $content = file_get_contents(self::LOCAL_PAGE_PATH . self::SETTINGS_PAGE . self::PAGE_EXT);
                    break;
            }

            if ($target && array_key_exists('X-Requested-With', $headers) && $headers['X-Requested-With'] == 'XMLHttpRequest') {
                echo $content;
            } else {
                require self::LOCAL_PAGE_PATH . self::MAIN_PAGE . self::PAGE_EXT;
            }
        }
    }
}

if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$asms = new ASMS();
$asms->init(true);
$asms->run();