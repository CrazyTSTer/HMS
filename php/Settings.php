<?php

class Settings
{
    private $debug;
    /** @var  Config */
    private $cfg;

    public function __construct($debug, $cfgName)
    {
        $this->debug = $debug;
        $this->cfg = Config::getConfig($cfgName);
    }

    //Common
    public function actionGetMetersInfoFromConfig()
    {
        $ret = $this->cfg->get();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }

    public function actionSaveMetersInfoToConfig()
    {
        $dataToSave = json_decode(Vars::get('dataToSave', null), true);
        $this->cfg->set(null, $dataToSave);
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Saved');
    }

    public function actionEraseMetersInfoFromConfig()
    {
        $this->cfg->drop();
        $this->cfg->save();
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Data Erased');
    }

    //Water
    public function actionGetWaterMetersInfoFromPgu()
    {
        if (!Vars::check('paycode')) {
            Utils::reportError(__CLASS__, 'PayCode should be passed', $this->debug);
        }

        $paycode = Vars::getPostVar('paycode', null);
        if (!$paycode) {
            Utils::reportError(__CLASS__, 'Passed empty PayCode', $this->debug);
        }

        if (!Vars::check('flat')) {
            Utils::reportError(__CLASS__, 'Flat number should be passed', $this->debug);
        }

        $flat = Vars::getPostVar('flat', null);
        if (!$flat) {
            Utils::reportError(__CLASS__, 'Passed empty Flat number', $this->debug);
        }

        $result = PguApi::getWaterMetersInfo($paycode, $flat);

        $address['district'] = ($result['address']['okrug'] ?? '-') . ' / ' . ($result['address']['district'] ?? '-');
        $address['street'] = $result['address']['street'] ?? '-';
        $address['house'] =  $result['address']['house'] ?? '-';
        $address['building'] = $result['address']['korpus'] ?? '-';
        $address['flat'] = $result['address']['flat'] ?? '-';

        if (isset($result['counter'])) {
            foreach ($result['counter'] as $value) {
                $meters[] = [
                    'counterNum' => $value['counterId'] ?? '-',
                    'num'        => $value['num'] ?? '-',
                    'type'       => $value['type'] ?? '-',
                    'checkup'    => date("d-m-Y", strtotime($value['checkup'])) ?? '-',
                ];
            }
        } else {
            $meters = [];
        }

        $ret = [
            'paycode' => $result['paycode'],
            'flat'    => $result['address']['flat'],
            'address' => $address,
            'meters'  => $meters,
        ];

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }

    //Electricity
    public function actionGetElectricityMeterInfoFromPgu()
    {
        if (!Vars::check('electricityPayCode')) {
            Utils::reportError(__CLASS__, 'PayCode should be passed', $this->debug);
        }

        $electricityPayCode = Vars::getPostVar('electricityPayCode', null);
        if (!$electricityPayCode) {
            Utils::reportError(__CLASS__, 'Passed empty PayCode', $this->debug);
        }

        if (!Vars::check('meterID')) {
            Utils::reportError(__CLASS__, 'meterID should be passed', $this->debug);
        }

        $meterID = Vars::getPostVar('meterID', null);
        if (!$meterID) {
            Utils::reportError(__CLASS__, 'Passed empty meterID', $this->debug);
        }

        PguApi::checkElectricityPayCode($electricityPayCode);
        $result = PguApi::checkElectricityMeterID($electricityPayCode, $meterID);
        $result = PguApi::getElectricityMeterInfo($electricityPayCode, $result['schema'], $result['id_kng']);

        $result['paycode'] = $electricityPayCode;
        $result['meterID'] = $meterID;

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result);
    }

    public function actionGenerateElectricityMeterCommands()
    {
        $meterID = $this->cfg->get('meterID');
        if ($meterID) {
            //00 0E 6F 9F 2F 3E D9

            /*$b = 946079;
            $res = [];
            for ($i = 0; $i < 4; $i++) {
                $res[] = $b & 0xFF;
                $b = $b >> 8;
            }
            $res = array_reverse($res);
            $res[] = 0x2F;

            echo "---------------------\n";
            var_export(crc16_modbus($res));

            die;
            function crc16_modbus($msg)
            {
                $data = $msg;//pack('H*',$msg);
                $crc = 0xFFFF;
                for ($i = 0; $i < count($data); $i++)
                {
                    $crc ^= $data[$i];

                    for ($j = 8; $j !=0; $j--)
                    {
                        if (($crc & 0x0001) !=0)
                        {
                            $crc >>= 1;
                            $crc ^= 0xA001;
                        }
                        else $crc >>= 1;
                    }
                }

                $res = [];
                for ($i = 0; $i < 2; $i++) {
                    $res[] = $crc & 0xFF;
                    $crc = $crc >> 8;
                }
                $res = array_reverse($res);

                return $res;
            }
            die;*/
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Команды усчпешно сгенерированы.');
        } else {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'В конфиг-файле не указан номер счетчика.<br>Укажите номер счетчика и сохраните конфиг.');
        }
    }
}
