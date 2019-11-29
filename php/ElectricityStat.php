<?php
/*CREATE TABLE `Electricity` (
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `TZ1` decimal(8,2) NOT NULL,
  `TZ2` decimal(8,2) NOT NULL,
  `TZ3` decimal(8,2) NOT NULL,
  `TZ4` decimal(8,2) NOT NULL,
  `total` decimal(9,2) NOT NULL,
  PRIMARY KEY (`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SELECT * FROM HMS.Electricity;*/
define('SET_EL_VALUES',                   'INSERT INTO #table# (TZ1, TZ2, TZ3, TZ4, total) VALUES (#TZ1#, #TZ2#, #TZ3#, #TZ4#, #total#)');

define('GET_EL_PREVIOUS_DAY_LAST_DATA',   'SELECT TZ1, TZ2, TZ3, TZ4, total FROM #table# WHERE DATE(ts) < CURDATE() ORDER BY ts DESC LIMIT 1');
define('GET_EL_PREVIOUS_MONTH_LAST_DATA', 'SELECT TZ1, TZ2, TZ3, TZ4, total FROM #table# WHERE DATE(ts) < DATE_FORMAT(CURDATE(), \'%Y-%m-01\') ORDER BY ts DESC LIMIT 1');
define('GET_EL_PREV_MONTH_RATE',          'SELECT MAX(TZ1) - MIN(TZ1) as TZ1, MAX(TZ2) - MIN(TZ2) as TZ2, MAX(TZ3) - MIN(TZ3) as TZ3, MAX(TZ4) - MIN(TZ4) as TZ4, MAX(total) - MIN(total) as total FROM (
                                                (SELECT ts, TZ1, TZ2, TZ3, TZ4, total FROM #table# WHERE DATE(ts) < DATE_FORMAT(CURDATE(), \'%Y-%m-01\') - INTERVAL 1 MONTH ORDER BY ts DESC LIMIT 1)
                                                UNION ALL
                                                (SELECT ts, TZ1, TZ2, TZ3, TZ4, total FROM #table# WHERE DATE(ts) <= DATE_FORMAT(CURDATE(), \'%Y-%m-01\') - INTERVAL 1 DAY ORDER BY ts DESC LIMIT 1)
                                           ) as smth;');
class ElectricityStat
{
    /** @var  DB */
    private $db;

    /** @var  Config */
    public $cfg;

    private $debug;

    private $host, $port;

    public function __construct($debug)
    {
        $this->debug = $debug;

        $this->cfg = Config::getConfig(ElectricityMetersSettings::CFG_NAME);
        $this->host = $this->cfg->get(ElectricityMetersSettings::HOST);
        $this->port = $this->cfg->get(ElectricityMetersSettings::PORT);

        $this->db = DB::getInstance();
        if (!$this->db->isDBReady()) {
            Utils::reportError(__CLASS__, DB::MYSQL_DB_IS_NOT_READY, $this->debug);
        }
    }

    public function actionGet()
    {
        if (!Vars::check('param')) {
            Utils::reportError(__CLASS__, 'Parameter should be passed', $this->debug);
        }

        $params = Vars::get('param', null);

        switch ($params) {
            case 'execute_command':
                if (!Vars::check('cmds')) {
                    Utils::reportError(__CLASS__, 'Commands should be passed', $this->debug);
                }
                $cmds = Vars::get('cmds', null);

                $result = $this->executeCommands($cmds);
                $result = ElectricityParser::parseData($result);
                break;

            case 'main_stat':
                $tmp = ElectricityParser::parseData($this->executeCommands([ElectricityMetersSettings::GET_CURRENT_POWER_VALUES, ElectricityMetersSettings::GET_CURRENT_DATE_TIME]));
                $current_values = $tmp[ElectricityMetersSettings::GET_CURRENT_POWER_VALUES];
                $previous_day_last_data = $this->db->fetchSingleRow(GET_EL_PREVIOUS_DAY_LAST_DATA, ['table' => DB::MYSQL_TABLE_ELECTRICITY]);
                $previous_month_last_data = $this->db->fetchSingleRow(GET_EL_PREVIOUS_MONTH_LAST_DATA, ['table' => DB::MYSQL_TABLE_ELECTRICITY]);
                $prev_month_rate = $this->db->fetchSingleRow(GET_EL_PREV_MONTH_RATE, ['table' => DB::MYSQL_TABLE_ELECTRICITY]);

                $result = [
                    'ts' => $tmp[ElectricityMetersSettings::GET_CURRENT_DATE_TIME],
                    'current_value' => [
                        'TZ1'   => sprintf('%.2f', $current_values['TZ1']),
                        'TZ2'   => sprintf('%.2f', $current_values['TZ2']),
                        'TZ3'   => sprintf('%.2f', $current_values['TZ3']),
                        'TZ4'   => sprintf('%.2f', $current_values['TZ4']),
                        'total' => sprintf('%.2f', $current_values['total']),
                    ],
                    'day_rate' => [
                        'TZ1'   => sprintf('%.2f', $current_values['TZ1'] - $previous_day_last_data['TZ1']),
                        'TZ2'   => sprintf('%.2f', $current_values['TZ2'] - $previous_day_last_data['TZ2']),
                        'TZ3'   => sprintf('%.2f', $current_values['TZ3'] - $previous_day_last_data['TZ3']),
                        'TZ4'   => sprintf('%.2f', $current_values['TZ4'] - $previous_day_last_data['TZ4']),
                        'total' => sprintf('%.2f', $current_values['total'] - $previous_day_last_data['total']),
                    ],
                    'month_rate' => [
                        'TZ1'   => sprintf('%.2f', $current_values['TZ1'] - $previous_month_last_data['TZ1']),
                        'TZ2'   => sprintf('%.2f', $current_values['TZ2'] - $previous_month_last_data['TZ2']),
                        'TZ3'   => sprintf('%.2f', $current_values['TZ3'] - $previous_month_last_data['TZ3']),
                        'TZ4'   => sprintf('%.2f', $current_values['TZ4'] - $previous_month_last_data['TZ4']),
                        'total' => sprintf('%.2f', $current_values['total'] - $previous_month_last_data['total']),
                    ],
                    'prev_month_rate' => [
                        'TZ1'   => sprintf('%.2f', $prev_month_rate['TZ1']),
                        'TZ2'   => sprintf('%.2f', $prev_month_rate['TZ2']),
                        'TZ3'   => sprintf('%.2f', $prev_month_rate['TZ3']),
                        'TZ4'   => sprintf('%.2f', $prev_month_rate['TZ4']),
                        'total' => sprintf('%.2f', $prev_month_rate['total']),
                    ],
                ];
                break;

            default:
                Utils::reportError(__CLASS__, Utils::UNKNOWN_PARAMETER, $this->debug);
                break;
        }

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result);
    }

    public function storeValuesToDB($data)
    {
        foreach ($data as $key => $value) {
            $res[$key] = $value;
        }

        $res['table'] = DB::MYSQL_TABLE_ELECTRICITY;
        $result = $this->db->executeQuery(SET_EL_VALUES, $res);

        return $result;
    }

    /**
     * @cmdNames commands array
     * @return array
     */
    public function executeCommands($cmdNames)
    {
        $attempts = $this->cfg->get(ElectricityMetersSettings::REQUEST_ATTEMPTS) ?? 10;

        foreach ($cmdNames as $cmdName) {
            if ($cmdName == ElectricityMetersSettings::GET_POWER_VALUES_BY_MONTH) {
                foreach (ElectricityMetersSettings::CMD_MONTH_SUBCODE as $month => $subCode) {
                    $cmd = $this->cfg->get(ElectricityMetersSettings::COMMANDS . '/' . $cmdName . '/' . $month);
                    if (!$cmd) {
                        Utils::reportError(__CLASS__, 'Can\'t execute ' . $cmdName . '. Command code is empty', $this->debug);
                    }
                    $result[$cmdName][$month] = $this->sendRequest($cmd, $attempts);
                }
            } else {
                $cmd = $this->cfg->get(ElectricityMetersSettings::COMMANDS . '/' . $cmdName);
                if (!$cmd) {
                    Utils::reportError(__CLASS__, 'Can\'t execute ' . $cmdName . '. Command code is empty', $this->debug);
                }
                $result[$cmdName] = $this->sendRequest($cmd, $attempts);
            }
        }

        return $result;
    }

    private function sendRequest($cmd, $attempts)
    {
        if (!$this->host || !$this->port) {
            Utils::reportError(__CLASS__, 'Can\'t connect to Electricity meter<br>Empty Host or Port', $this->debug);
        }


        $result = NULL;
        for ($i = 0; $i < $attempts; $i++) {
            $fp = fsockopen($this->host, $this->port, $errno, $errstr, 30);
            if (!$fp) {
                Utils::reportError(__CLASS__, 'Can\'t connect to Electricity meter.<br>' . $errno . ':' . $errstr, $this->debug);
            }

            //stream_set_blocking($fp, true);
            fwrite($fp, hex2bin($cmd));

            //$prevMicrotime = microtime(true);

            $response = '';
            //stream_set_blocking($fp, false);

            while (!feof($fp)) {
                $response .= fgets($fp);
                /*if ($tmp === false) {
                    if (microtime(true) - $prevMicrotime > 0.05) {
                        break;
                    }
                } else {
                    $response .= $tmp;
                    $prevMicrotime = microtime(true);
                }*/
            }
            fclose($fp);

            $responseDecoded = strtoupper(bin2hex($response));

            $responseCRC = substr($responseDecoded, -4);
            $calcResponseCRC = Utils::crc16_modbus(substr($responseDecoded, 0, strlen($responseDecoded) - 4));

            if ($responseCRC == $calcResponseCRC) {
                $result = substr($responseDecoded, 10, -4);
                break;
            }
        }

        return $result;
    }
}
