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

    private $action;
    private $debug;
    private $params;

    public function init($debug = false)
    {
        $this->debug = $debug;

        if (Vars::check('action')) {
            $this->action = Vars::get('action', null);
            $this->params = Vars::get('params', null);
        } elseif (Vars::check('getlast')) {
            $this->action = self::ACTION_GET_LAST;
            $this->params = strtolower(Vars::get('getlast', null));
        }

        $this->checkValues('action', $this->action, $this->debug);
        $this->checkValues('params', $this->params, $this->debug);

        $this->db = DB::getInstance();
        $this->db->init(self::MYSQL_HOST, self::MYSQL_PORT, self::MYSQL_LOGIN, self::MYSQL_PASS, $this->debug);
        $this->db->connect();
        $this->db->selectDB(self::MYSQL_BASE);
        $this->db->setLocale(self::MYSQL_BASE_LOCALE);
    }

    public function run()
    {
        switch ($this->action) {
            case self::ACTION_SET:
                $result = $this->actionSet();
                break;

            case self::ACTION_GET_LAST:
                $result = $this->getLastValue($this->params);
                break;

            default:
                Utils::reportError(__CLASS__, "Invalid action {$this->action}", $this->debug);
                break;
        }
        echo $result;
        exit(0);
    }

    private function getLastValue($params)
    {
        return $this->db->fetchOnlyOneValue(GET_METER_VALUE, array('col' => $params), false);
    }

    private function actionSet()
    {
        if (!is_array($this->params)) {
            Utils::reportError(__CLASS__, 'Params array should be passed', $this->debug);
        }

        $i = 0;
        $data = array();
        foreach ($this->params as $key => $value) {
            $i++;
            $data['col' . strval($i)] = strtolower($key);
            $data['val' . strval($i)] = $value;
        }

        return $this->db->executeQuery(SET_METERS_VALUES, $data, false);
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
