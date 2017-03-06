<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

include_once "php/Utils.php";
define('GET_METER_VALUE', 'SELECT #col# FROM WaterMeter ORDER BY Ts DESC LIMIT 1');
define('SET_METERS_VALUES', 'INSERT INTO WaterMeter (#col1#, #col2#) VALUES (#val1#, #val2#)');

class WaterStat
{
    const MYSQL_HOST        = 'localhost';
    const MYSQL_PORT        = 3306;
    const MYSQL_LOGIN       = 'water_meter';
    const MYSQL_PASS        = 'calcwater';
    const MYSQL_BASE        = 'HomeMetersStats';
    const MYSQL_BASE_LOCALE = 'utf8';

    const ACTION_SET      = 'set';
    const ACTION_GET_LAST = 'getlast';

    /** @var  DB */
    private $db;

    private $set;
    private $debug;

    public function init($debug = false)
    {
        $this->debug = $debug;

        $this->db = DB::getInstance();
        $this->db->init(self::MYSQL_HOST, self::MYSQL_PORT, self::MYSQL_LOGIN, self::MYSQL_PASS, $this->debug);
        $this->db->connect();
        $this->db->selectDB(self::MYSQL_BASE);
        $this->db->setLocale(self::MYSQL_BASE_LOCALE);
    }

    public function run()
    {
        if (Vars::check('set')) {
            $this->set = Vars::get('set', null);
            $this->checkValues('set', $this->set, $this->debug);
            $this->actionSet();
        } elseif (Vars::check('get')) {
            //$this->actionGet();
        } else {
            Utils::unifiedExitPoint(Utils::RESPONSE_FAIL, 'Unknown action');
        }

    }

    private function actionSet()
    {
        if (!is_array($this->set)) {
            Utils::reportError(__CLASS__, 'Set array should be passed', $this->debug);
        }

        $i = 0;
        $data = array();
        foreach ($this->set as $key => $value) {
            $i++;
            $data['col' . strval($i)] = strtolower($key);
            $data['val' . strval($i)] = $value;
        }

        $result = $this->db->executeQuery(SET_METERS_VALUES, $data, false);
        Utils::unifiedExitPoint(Utils::RESPONSE_SUCCESS, $result);
    }

    private function checkValues($param, $value, $debug)
    {
        if (!$value) {
            Utils::reportError(__CLASS__, "Got NULL in parameter '{$param}'", $debug);
        }
    }
}

$ws = new WaterStat();
$ws->init(true);
$ws->run();
