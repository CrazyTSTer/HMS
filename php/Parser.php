<?php

class Parser
{
    public static function parserCurrentValues($data)
    {
        if ($data == false || $data == DB::MYSQL_EMPTY_SELECTION) {
            $ret = [
                "status" => Utils::STATUS_FAIL,
                "data" => 'Can\'t get currnet values from DB'
            ];
        } else {
            $ret = [
                "status" => Utils::STATUS_SUCCESS,
                "data" => $data
            ];
        }
        return $ret;
    }

    public static function parseCurrentDay($data, $isCurrentDay = true)
    {
        if ($data == false || $data == DB::MYSQL_EMPTY_SELECTION) {
            $ret = [
                "status" => Utils::STATUS_FAIL,
                "data" => 'Can\'t get current day data from DB'
            ];
        } else {
            $coldWaterFirstValue = $data[0][WaterStat::COLDWATER];
            $hotWaterFirstValue = $data[0][WaterStat::HOTWATER];
            $data[0][WaterStat::TIMESTAMP] = isset($data[1][WaterStat::TIMESTAMP]) ? date('Y-m-d 00:00:00', strtotime($data[1][WaterStat::TIMESTAMP])) : date('Y-m-d 00:00:00');

            //Добавляем дату, которую будем показывать
            $ret['data']['date'] = isset($data[1][WaterStat::TIMESTAMP]) ? date('d-m-Y', strtotime($data[1][WaterStat::TIMESTAMP])) : date('d-m-Y');

            //Добавляем первую точку (начало дня)
            $ret['data'][WaterStat::TIMESTAMP . 'cw'][] = 'tscw';
            $ret['data'][WaterStat::TIMESTAMP . 'cw'][] = $data[0][WaterStat::TIMESTAMP];
            $ret['data'][WaterStat::COLDWATER][] = 'coldwater';
            $ret['data'][WaterStat::COLDWATER][] = 0;

            $ret['data'][WaterStat::TIMESTAMP . 'hw'][] = 'tshw';
            $ret['data'][WaterStat::TIMESTAMP . 'hw'][] = $data[0][WaterStat::TIMESTAMP];
            $ret['data'][WaterStat::HOTWATER][] = 'hotwater';
            $ret['data'][WaterStat::HOTWATER][] = 0;

            for ($i = 1; $i < $data[DB::MYSQL_ROWS_COUNT]; $i++) {
                //Смотрим интервал между двумя точками
                $current_ts = strtotime($data[$i][WaterStat::TIMESTAMP]);
                $prev_ts = strtotime($data[$i - 1][WaterStat::TIMESTAMP]);
                $interval = round(abs($current_ts - $prev_ts) / 60);

                //Если интервал больше 5 минут, рисуем точку, на минуту раньше текущей
                if ($interval > 5) {
                    $point_ts = $current_ts - 60; //Сдвигаемся на минуту назад
                    if ($data[$i][WaterStat::COLDWATER] - $data[$i - 1][WaterStat::COLDWATER] != 0) {
                        $ret['data'][WaterStat::TIMESTAMP . 'cw'][] = date('Y-m-d H:i:s', $point_ts);
                        $ret['data'][WaterStat::COLDWATER][] = $data[$i - 1][WaterStat::COLDWATER] - $coldWaterFirstValue;
                    }
                    if ($data[$i][WaterStat::HOTWATER] - $data[$i - 1][WaterStat::HOTWATER] != 0) {
                        $ret['data'][WaterStat::TIMESTAMP . 'hw'][] = date('Y-m-d H:i:s', $point_ts);
                        $ret['data'][WaterStat::HOTWATER][] = $data[$i - 1][WaterStat::HOTWATER] - $hotWaterFirstValue;
                    }
                }

                //Рисуем текущую точку
                $ret['data'][WaterStat::TIMESTAMP . 'cw'][] = $data[$i][WaterStat::TIMESTAMP];
                $ret['data'][WaterStat::COLDWATER][] = $data[$i][WaterStat::COLDWATER] - $coldWaterFirstValue;

                $ret['data'][WaterStat::TIMESTAMP . 'hw'][] = $data[$i][WaterStat::TIMESTAMP];
                $ret['data'][WaterStat::HOTWATER][] = $data[$i][WaterStat::HOTWATER] - $hotWaterFirstValue;
            }

            //Добавляем последнюю точку на текущее время
            $ret['data'][WaterStat::TIMESTAMP . 'cw'][] = $isCurrentDay ? date("Y-m-d H:i:s") : date('Y-m-d 23:59:59', strtotime($data[1][WaterStat::TIMESTAMP]));
            $ret['data'][WaterStat::COLDWATER][] = $data[$data[DB::MYSQL_ROWS_COUNT] - 1][WaterStat::COLDWATER] - $coldWaterFirstValue;

            $ret['data'][WaterStat::TIMESTAMP . 'hw'][] = $isCurrentDay ? date("Y-m-d H:i:s") : date('Y-m-d 23:59:59', strtotime($data[1][WaterStat::TIMESTAMP]));
            $ret['data'][WaterStat::HOTWATER][] = $data[$data[DB::MYSQL_ROWS_COUNT] - 1][WaterStat::HOTWATER] - $hotWaterFirstValue;

            $ret['status'] = Utils::STATUS_SUCCESS;
        }
        return $ret;
    }

    public static function parseMonth($data, $isCurrentMonth = true, $isLast12Month = false)
    {
        if ($data == false || $data == DB::MYSQL_EMPTY_SELECTION) {
            $ret = [
                "status" => Utils::STATUS_FAIL,
                "data" => 'Can\'t get current ' . !$isLast12Month ? 'day' : 'month' . ' data from DB'
            ];
        } else {
            $ret['data'][WaterStat::TIMESTAMP][] = 'ts';
            $ret['data'][WaterStat::COLDWATER][] = 'coldwater';
            $ret['data'][WaterStat::HOTWATER][] = 'hotwater';

            for ($i = 1; $i < $data[DB::MYSQL_ROWS_COUNT]; $i++) {
                $ret['data'][WaterStat::TIMESTAMP][] = $data[$i][WaterStat::TIMESTAMP];
                $ret['data'][WaterStat::COLDWATER][] = $data[$i][WaterStat::COLDWATER] - $data[$i - 1][WaterStat::COLDWATER];
                $ret['data'][WaterStat::HOTWATER][] = $data[$i][WaterStat::HOTWATER] - $data[$i - 1][WaterStat::HOTWATER];
            }

            if (!$isLast12Month) {
                $ret['data']["date"] = isset($data[1][WaterStat::TIMESTAMP]) ? date('Y-m', strtotime($data[1][WaterStat::TIMESTAMP])) : date('Y-m');
            }

            $ts = strtotime($data[$data[DB::MYSQL_ROWS_COUNT] - 1][WaterStat::TIMESTAMP]);

            //Если для текущего дня/месяца еще нет данных, добавляем нулевую точку
            if (!$isLast12Month ? (date('Y-m-d', $ts) < date('Y-m-d') && $isCurrentMonth) : date('Y-m', $ts) < date('Y-m')) {
                $ret['data'][WaterStat::TIMESTAMP][] = !$isLast12Month ? date('Y-m-d') : date('Y-m');
                $ret['data'][WaterStat::COLDWATER][] = 0;
                $ret['data'][WaterStat::HOTWATER][] = 0;
            }
            $ret['status'] = Utils::STATUS_SUCCESS;
        }
        return $ret;
    }
}

class ElectricityParser
{
    public static function parseData($data) {
        $cmdName = key($data);
        $cmdData = $data[$cmdName];

        switch ($cmdName) {
            case ElectricityStat::GET_SERIAL_NUMBER:
                $result['SN'] = hexdec($cmdData);
                break;
            case ElectricityStat::GET_MANUFACTURED_DATE:
                $tmp = str_split($cmdData, 2);
                $tmp[2] = '20' . $tmp[2];
                $result['Manufactured'] = implode('-', $tmp);
                break;
            case ElectricityStat::GET_FIRMWARE_VERSION:
                $tmp = explode('00', $cmdData);

                $version = implode('.', str_split($tmp[0], 2));

                $releaseDate = str_split($tmp[1], 2);
                $releaseDate[2] = '20' . $releaseDate[2];
                $releaseDate = implode('-', $releaseDate);

                $result = [
                    'Firmware_version'     => $version,
                    'Version_Release_date' => $releaseDate,
                ];
                break;
            case ElectricityStat::GET_BATTERY_VOLTAGE:
                $result['BatteryVoltage'] = implode('.', str_split($cmdData, 2));
                break;
            case ElectricityStat::GET_LAST_SWITCH_ON:
            case ElectricityStat::GET_LAST_SWITCH_OFF:
                //TODO: Parse date time
                break;
            case ElectricityStat::GET_CURRENT_CIRCUIT_VALUES:
                $result = [
                    'Voltage'  => substr($cmdData,0, 4) / 10,
                    'Amperage' => substr($cmdData,4, 4) / 100,
                    'Power'    => substr($cmdData,8, 6) / 1000,
                ];
                break;
            case ElectricityStat::GET_CURRENT_POWER_VALUES:
                $result = self::parsePowerValuse($cmdData);
                break;
            case ElectricityStat::GET_CURRENT_POWER:
                $result['Power'] = $cmdData / 100;
                break;
            case ElectricityStat::GET_POWER_VALUES_BY_MONTH:
                foreach ($cmdData as $month => $monthData) {
                    $result[$month] = self::parsePowerValuse($monthData);
                }
                break;
        }

        return $result;
    }

    private static function parsePowerValuse($data)
    {
        $i = 1;
        foreach (str_split($data, 8) as $chunk) {
            $result['TZ' . $i] = $chunk / 100;
            $i++;
        }

        return $result;
    }
}
