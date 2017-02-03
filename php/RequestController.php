<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

include_once "Utils.php";

class RequestController
{
    const MYSQL_HOST        = 'localhost';
    const MYSQL_PORT        = 3306;
    const MYSQL_LOGIN       = 'water_meter';
    const MYSQL_PASS        = 'calcwater';
    const MYSQL_BASE        = 'HomeMetersStats';
    const MYSQL_BASE_LOCALE = 'utf8';

    const RC_ACTION_GET      = 'get';
    const RC_ACTION_SET      = 'set';
    const RC_ACTION_GET_LAST = 'getlast';

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
            $this->action = self::RC_ACTION_GET_LAST;
            $this->params = strtolower(Vars::get('getlast', null));
        }

        $this->db = DB::getInstance();
        $this->db->init(self::MYSQL_HOST, self::MYSQL_PORT, self::MYSQL_LOGIN, self::MYSQL_PASS, $this->debug);
        $this->db->connect();
        $this->db->selectDB(self::MYSQL_BASE);
        $this->db->setLocale(self::MYSQL_BASE_LOCALE);
    }

    public function run()
    {
        $this->checkValues('action', $this->action);
        $this->checkValues('params', $this->params);

        switch ($this->action) {
            case self::RC_ACTION_GET:
                $this->getLastValue('ColdWater');
                break;

            case self::RC_ACTION_SET:
                $this->actionSet();
                break;

            case self::RC_ACTION_GET_LAST:
                $this->getLastValue($this->params);
                break;

            default:
                Utils::reportError(__CLASS__, "Invalid action {$this->action}", $this->debug);
                break;
        }
    }

    private function getLastValue($params)
    {
        $row = $this->db->fetchOnlyOneValue(
            'SELECT #col# FROM WaterMeter order by Ts DESC limit 1',
            array('col' => $params),
            false
        );
        var_export($row);
    }

    private function actionGet()
    {

    }

    private function actionSet()
    {

    }

    private function checkValues($param, $value)
    {
        if (!$value) {
            Utils::reportError(__CLASS__, "Got NULL in parameter '{$param}'", $this->debug);
        }
    }
}

$rq = new RequestController();
$rq->init(true);
$rq->run();
