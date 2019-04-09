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

define('GET_EL_PREVIOUS_DAY_LAST_DATA',   'SELECT TZ1, TZ2, TZ3, TZ4, total FROM #table# WHERE DATE(ts) < CURDATE() ORDER BY ts DESC LIMIT 1');
define('GET_EL_PREVIOUS_MONTH_LAST_DATA', 'SELECT TZ1, TZ2, TZ3, TZ4, total FROM #table# WHERE DATE(ts) < DATE_FORMAT(CURDATE(), \'%Y-%m-01\') ORDER BY ts DESC LIMIT 1');
define('GET_EL_PREV_MONTH_RATE',          'SELECT MAX(TZ1) - MIN(TZ1) as TZ1, MAX(TZ2) - MIN(TZ2) as TZ2, MAX(TZ3) - MIN(TZ3) as TZ3, MAX(TZ4) - MIN(TZ4) as TZ4 FROM (
                                            (SELECT ts, TZ1, TZ2, TZ3, TZ4 FROM #table# WHERE DATE(ts) < DATE_FORMAT(CURDATE(), \'%Y-%m-01\') - INTERVAL 1 MONTH ORDER BY ts DESC LIMIT 1) 
                                            UNION ALL 
                                            (SELECT ts, TZ1, TZ2, TZ3, TZ4 FROM #table# WHERE DATE(ts) <= DATE_FORMAT(CURDATE(), \'%Y-%m-01\') - INTERVAL 1 DAY ORDER BY ts DESC LIMIT 1)
                                        ) as smth;');
class ElectricityStat
{
    const MYSQL_HOST = '192.168.1.2';
    const MYSQL_PORT = 3306;

    /** @var  DB */
    private $db;

    /** @var  Config */
    public $cfg;

    private $debug;

    public function __construct($debug)
    {
        $this->debug = $debug;
        $this->cfg = Config::getConfig(ElectricityMetersSettings::CFG_NAME);

        $this->db = DB::getInstance();
        $this->db->init(self::MYSQL_HOST, self::MYSQL_PORT, DB::MYSQL_LOGIN, DB::MYSQL_PASS, $this->debug);
        $this->db->connect();
        $this->db->selectDB(DB::MYSQL_BASE);
        $this->db->setLocale(DB::MYSQL_BASE_LOCALE);
    }

    public function __destruct()
    {
        $this->db->disconnect();
        unset($this->db);
        unset($this->cfg);
    }

    public function actionGet()
    {
        if (!Vars::check('param')) {
            Utils::reportError(__CLASS__, 'Parameter should be passed', $this->debug);
        }

        $params = Vars::get('param', null);

        if (!$this->db->isDBReady()) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, DB::MYSQL_DB_IS_NOT_READY);
        }

        switch ($params) {
            case 'main_stat':
                $tmp = ElectricityParser::parseData($this->executeCommands([ElectricityMetersSettings::GET_CURRENT_POWER_VALUES, ElectricityMetersSettings::GET_CURRENT_DATE_TIME]));
                $current_values = $tmp[ElectricityMetersSettings::GET_CURRENT_POWER_VALUES];
                $previous_day_last_data = $this->db->fetchSingleRow(GET_EL_PREVIOUS_DAY_LAST_DATA, ['table' => DB::MYSQL_TABLE_ELECTRICITY]);
                $previous_month_last_data = $this->db->fetchSingleRow(GET_EL_PREVIOUS_MONTH_LAST_DATA, ['table' => DB::MYSQL_TABLE_ELECTRICITY]);
                $prev_month_rate = $this->db->fetchSingleRow(GET_EL_PREV_MONTH_RATE, ['table' => DB::MYSQL_TABLE_ELECTRICITY]);

                $result = [
                    'ts' => $tmp[ElectricityMetersSettings::GET_CURRENT_DATE_TIME],
                    'current_value' => $current_values,
                    'day_rate' => [
                        'TZ1' => number_format($current_values['TZ1'] - $previous_day_last_data['TZ1'], 2, ',', ''),
                        'TZ2' => number_format($current_values['TZ2'] - $previous_day_last_data['TZ2'], 2, ',', ''),
                        'TZ3' => number_format($current_values['TZ3'] - $previous_day_last_data['TZ3'], 2, ',', ''),
                        'TZ4' => number_format($current_values['TZ4'] - $previous_day_last_data['TZ4'], 2, ',', ''),
                    ],
                    'month_rate' => [
                        'TZ1' => number_format($current_values['TZ1'] - $previous_month_last_data['TZ1'], 2, ',', ''),
                        'TZ2' => number_format($current_values['TZ2'] - $previous_month_last_data['TZ2'], 2, ',', ''),
                        'TZ3' => number_format($current_values['TZ3'] - $previous_month_last_data['TZ3'], 2, ',', ''),
                        'TZ4' => number_format($current_values['TZ4'] - $previous_month_last_data['TZ4'], 2, ',', ''),
                    ],
                    'prev_month_rate' => $prev_month_rate,
                ];
                break;

            default:
                Utils::reportError(__CLASS__, Utils::UNKNOWN_PARAMETER, $this->debug);
                break;
        }

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result);
    }

    /**
     * @cmdNames commands array
     * @return array
     */
    public function executeCommands($cmdNames)
    {
        $host = $this->cfg->get(ElectricityMetersSettings::HOST);
        $port = $this->cfg->get(ElectricityMetersSettings::PORT);
        $attempts = $this->cfg->get(ElectricityMetersSettings::REQUEST_ATTEMPTS) ?? 10;

        if (!$host || !$port) {
            Utils::reportError(__CLASS__, 'Can\'t connect to Electricity meter<br>Empty Host or Port', $this->debug);
        }

        $fp = fsockopen($host, $port, $errno, $errstr, 30);
        if (!$fp) {
            Utils::reportError(__CLASS__, 'Can\'t connect to Electricity meter.<br>' . $errno . ':' . $errstr, $this->debug);
        }

        foreach ($cmdNames as $cmdName) {
            if ($cmdName == ElectricityMetersSettings::GET_POWER_VALUES_BY_MONTH) {
                foreach (ElectricityMetersSettings::CMD_MONTH_SUBCODE as $month => $subCode) {
                    $cmd = $this->cfg->get(ElectricityMetersSettings::COMMANDS . '/' . $cmdName . '/' . $month);
                    if (!$cmd) {
                        Utils::reportError(__CLASS__, 'Can\'t execute ' . $cmdName . '. Command code is empty', $this->debug);
                    }
                    $result[$cmdName][$month] = $this->sendRequest($fp, $cmd, $attempts);
                }
            } else {
                $cmd = $this->cfg->get(ElectricityMetersSettings::COMMANDS . '/' . $cmdName);
                if (!$cmd) {
                    Utils::reportError(__CLASS__, 'Can\'t execute ' . $cmdName . '. Command code is empty', $this->debug);
                }
                $result[$cmdName] = $this->sendRequest($fp, $cmd, $attempts);
            }
        }

        fclose($fp);
        return $result;
    }

    private function sendRequest($fp, $cmd, $attempts)
    {
        $result = NULL;
        for ($i = 0; $i < $attempts; $i++) {
            stream_set_blocking($fp, true);
            fwrite($fp, hex2bin($cmd));

            $prevMicrotime = microtime(true);

            $response = '';
            stream_set_blocking($fp, false);

            while (1) {
                $tmp = fgetc($fp);
                if ($tmp === false) {
                    if (microtime(true) - $prevMicrotime > 0.05) {
                        break;
                    }
                } else {
                    $response .= $tmp;
                    $prevMicrotime = microtime(true);
                }
            }

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
