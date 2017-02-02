<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

include_once "Utils.php";

define('MYSQL_GET_WELCOME', 'SELECT text FROM welcome WHERE entry = #entry#');
define(
    'MYSQL_GET_MENU_ITEM_DESC',
    'SELECT short_desc, long_desc FROM sub_menu WHERE element_id = #element_id# AND entry = #entry#'
);

class RequestController
{
    const MYSQL_HOST        = 'localhost';
    const MYSQL_PORT        = 3306;
    const MYSQL_LOGIN       = 'water_meter';
    const MYSQL_PASS        = 'calcwater';
    const MYSQL_BASE        = 'HomeMEtersStats';
    const MYSQL_BASE_LOCALE = 'utf8';

    const RC_ACTION_GET = 'get';
    const RC_ACTION_SET = 'set';

    const RC_GET_WELCOME        = 'welcome';
    const RC_GET_MENU_ITEM_DESC = 'menu_item_desc';
    const RC_GET_MENU_TREE      = 'menu_tree';

    /** @var  DB */
    private $db;
    private $action;
    private $from;
    private $params;
    private $debug;

    public function init($debug = false)
    {
        $this->debug = $debug;
        $this->action = Vars::get('action', null);
        $this->from = Vars::get('from', null);
        $this->params = Vars::get('params', null);

        $this->db = DB::getInstance();
        $this->db->init(self::MYSQL_HOST, self::MYSQL_PORT, self::MYSQL_LOGIN, self::MYSQL_PASS, true);
        $this->db->connect();
        $this->db->selectDB(self::MYSQL_BASE);
        $this->db->setLocale(self::MYSQL_BASE_LOCALE);
    }

    public function run()
    {
        $this->checkValues('action', $this->action);

        switch ($this->action) {
            case self::RC_ACTION_GET:
                $this->checkValues('from', $this->from);
                $this->checkValues('params', $this->params);
                $this->actionGet();
                break;

            default:
                Utils::reportError(__CLASS__, "Invalid action {$this->action}", $this->debug);
                break;
        }
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
