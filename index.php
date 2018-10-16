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
    private $debug;
    private $location;
    private $action;
    private $target = '';

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
            $target = $this->target;
            require "static/index.html";
        }
    }
}

$asms = new ASMS();
$asms->init(true);
$asms->run();