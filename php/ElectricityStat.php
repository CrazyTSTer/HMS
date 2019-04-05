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

class ElectricityStat
{
    const MYSQL_HOST        = '192.168.1.2';
    const MYSQL_PORT        = 3306;
    const MYSQL_LOGIN       = 'hms';
    const MYSQL_PASS        = 'HMSStats1';
    const MYSQL_BASE        = 'HMS';
    const MYSQL_BASE_LOCALE = 'utf8';
    const MYSQL_TABLE_WATER = 'Electricity';

    const TIMESTAMP = 'ts';
    const TZ1       = 'TZ1';
    const TZ2       = 'TZ2';
    const TZ3       = 'TZ3';
    const TZ4       = 'TZ4';
    const TOTAL     = 'total';

    private $debug;

    /** @var  Config */
    public $cfg;

    public function __construct($debug)
    {
        $this->debug = $debug;
        $this->cfg = Config::getConfig(ElectricityMetersSettings::CFG_NAME);

        $this->db = DB::getInstance();
        $this->db->init(self::MYSQL_HOST, self::MYSQL_PORT, self::MYSQL_LOGIN, self::MYSQL_PASS, $this->debug);
        $this->db->connect();
        $this->db->selectDB(self::MYSQL_BASE);
        $this->db->setLocale(self::MYSQL_BASE_LOCALE);
    }

    public function actionGet()
    {
        if (!Vars::check('param')) {
            Utils::reportError(__CLASS__, 'Parameter should be passed', $this->debug);
        }

        $params = Vars::get('param', null);

        $result = self::executeCommands($params);
        $result = ElectricityParser::parseData($result);
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
            Utils::reportError(__CLASS__, 'Can\'t connect to Electricity meter<br>Empty Host or Port');
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
