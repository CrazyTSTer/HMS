<?php
/**
 * Created by PhpStorm.
 * User: CrazyTSTer
 * Date: 07.04.17
 * Time: 0:18
 */
class Parser
{
    const COLDWATER = 'coldwater';
    const HOTWATER  = 'hotwater';
    const TIMESTAMP = 'ts';

    const EMPTY_DATA = 'empty';

    public static function parserCurrentValues($data)
    {
        if ($data == false) {
            $ret = [
                "status" => Utils::STATUS_FAIL,
                "data" => 'Can\'t get currnet values from DB'
            ];
        } elseif ($data == DB::MYSQL_EMPTY_SELECTION) {
            $ret = [
                "status" => Utils::STATUS_SUCCESS,
                "data" => self::EMPTY_DATA
            ];
        } else {
            $ret = [
                "status" => Utils::STATUS_SUCCESS,
                "data" => $data
            ];
        }
        return $ret;
    }

    public static function parseCurrentDay($data)
    {
        if ($data == false) {
            $ret = [
                "status" => Utils::STATUS_FAIL,
                "data" => 'Can\'t get current day data from DB'
            ];
        } elseif ((isset($data[DB::MYSQL_ROWS_COUNT]) && $data[DB::MYSQL_ROWS_COUNT] < 2) || $data == DB::MYSQL_EMPTY_SELECTION) {
            $ret = [
                "status" => Utils::STATUS_SUCCESS,
                "data" => self::EMPTY_DATA
            ];
        } else {
            $coldWaterFirstValue = $data[0][self::COLDWATER];
            $hotWaterFirstValue = $data[0][self::HOTWATER];
            $data[0][self::TIMESTAMP] = date('Y-m-d 00:00:00', strtotime($data[1][self::TIMESTAMP]));


            //Добавляем дату, которую будем показывать
            $ret['data']['date'] = date('d-m-Y', strtotime($data[1][self::TIMESTAMP]));

            //Добавляем первую точку (начало дня)
            $ret['data'][self::TIMESTAMP . 'cw'][] = 'tscw';
            $ret['data'][self::TIMESTAMP . 'cw'][] = $data[0][self::TIMESTAMP];
            $ret['data'][self::COLDWATER][] = 'coldwater';
            $ret['data'][self::COLDWATER][] = 0;

            $ret['data'][self::TIMESTAMP . 'hw'][] = 'tshw';
            $ret['data'][self::TIMESTAMP . 'hw'][] = $data[0][self::TIMESTAMP];
            $ret['data'][self::HOTWATER][] = 'hotwater';
            $ret['data'][self::HOTWATER][] = 0;

            $ret['data']['flot'][self::COLDWATER][] = [strtotime($data[0][self::TIMESTAMP]) * 1000, 0];
            $ret['data']['flot'][self::HOTWATER][] = [strtotime($data[0][self::TIMESTAMP]) * 1000 ,0];

            for ($i = 1; $i < $data[DB::MYSQL_ROWS_COUNT]; $i++) {
                //Смотрим интервал между двумя точками
                $current_ts = strtotime($data[$i][self::TIMESTAMP]);
                $prev_ts = strtotime($data[$i - 1][self::TIMESTAMP]);
                $interval = round(abs($current_ts - $prev_ts) / 60);

                //Если интервал больше 5 минут, рисуем точку, на минуту раньше текущей
                if ($interval > 5) {
                    $point_ts = $current_ts - 60; //Сдвигаемся на минуту назад
                    if ($data[$i][self::COLDWATER] - $data[$i - 1][self::COLDWATER] != 0) {
                        $ret['data'][self::TIMESTAMP . 'cw'][] = date('Y-m-d H:i:s', $point_ts);
                        $ret['data'][self::COLDWATER][] = $data[$i - 1][self::COLDWATER] - $coldWaterFirstValue;

                        $ret['data']['flot'][self::COLDWATER][] = [$point_ts * 1000, $data[$i - 1][self::COLDWATER] - $coldWaterFirstValue];
                    }
                    if ($data[$i][self::HOTWATER] - $data[$i - 1][self::HOTWATER] != 0) {
                        $ret['data'][self::TIMESTAMP . 'hw'][] = date('Y-m-d H:i:s', $point_ts);
                        $ret['data'][self::HOTWATER][] = $data[$i - 1][self::HOTWATER] - $hotWaterFirstValue;

                        $ret['data']['flot'][self::HOTWATER][] = [$point_ts * 1000, $data[$i - 1][self::HOTWATER] - $hotWaterFirstValue];
                    }
                }

                //Рисуем текущую точку
                $ret['data'][self::TIMESTAMP . 'cw'][] = $data[$i][self::TIMESTAMP];
                $ret['data'][self::COLDWATER][] = $data[$i][self::COLDWATER] - $coldWaterFirstValue;

                $ret['data'][self::TIMESTAMP . 'hw'][] = $data[$i][self::TIMESTAMP];
                $ret['data'][self::HOTWATER][] = $data[$i][self::HOTWATER] - $hotWaterFirstValue;

                $ret['data']['flot'][self::COLDWATER][] = [strtotime($data[$i][self::TIMESTAMP]) * 1000, $data[$i][self::COLDWATER] - $coldWaterFirstValue];
                $ret['data']['flot'][self::HOTWATER][] = [strtotime($data[$i][self::TIMESTAMP]) * 1000, $data[$i][self::HOTWATER] - $hotWaterFirstValue];

            }

            //Добавляем последнюю точку на вермя текущее время
            $ret['data'][self::TIMESTAMP . 'cw'][] = date("Y-m-d H:i:s");
            $ret['data'][self::COLDWATER][] = $data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::COLDWATER] - $coldWaterFirstValue;

            $ret['data'][self::TIMESTAMP . 'hw'][] = date("Y-m-d H:i:s");
            $ret['data'][self::HOTWATER][] = $data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::HOTWATER] - $hotWaterFirstValue;

            $ret['data']['flot'][self::COLDWATER][] = [strtotime(date("Y-m-d H:i:s")) * 1000, $data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::COLDWATER] - $coldWaterFirstValue];
            $ret['data']['flot'][self::HOTWATER][] = [strtotime(date("Y-m-d H:i:s")) * 1000, $data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::HOTWATER] - $hotWaterFirstValue];

            $ret['status'] = Utils::STATUS_SUCCESS;
        }
        return $ret;
    }

    public static function parseMonth($data, $isLast12Month = false)
    {
        if ($data == false) {
            $ret = [
                "status" => Utils::STATUS_FAIL,
                "data" => 'Can\'t get current month data from DB'
            ];
        } elseif ($data[DB::MYSQL_ROWS_COUNT] < 2 || $data == DB::MYSQL_EMPTY_SELECTION) {
            $ret = [
                "status" => Utils::STATUS_SUCCESS,
                "data" => self::EMPTY_DATA
            ];
        } else {
            $ret['data'][self::TIMESTAMP][] = 'ts';
            $ret['data'][self::COLDWATER][] = 'coldwater';
            $ret['data'][self::HOTWATER][] = 'hotwater';

            if (!$isLast12Month) $ret['data']["date"] = date('F', strtotime($data[1][self::TIMESTAMP]));

            for ($i = 1; $i < $data[DB::MYSQL_ROWS_COUNT]; $i++) {
                $ret['data'][self::TIMESTAMP][] = $data[$i][self::TIMESTAMP];
                $ret['data'][self::COLDWATER][] = $data[$i][self::COLDWATER] - $data[$i - 1][self::COLDWATER];
                $ret['data'][self::HOTWATER][] = $data[$i][self::HOTWATER] - $data[$i - 1][self::HOTWATER];
            }

            $ts = strtotime($data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::TIMESTAMP]);

            //Если для текущего дня/месяца еще нет данных, добавляем нулевую точку
            if (($isLast12Month
                    ? date('Y-m', $ts) < date('Y-m')
                    : date('Y-m-d', $ts) < date('Y-m-d'))
            ) {
                $ret['data'][self::TIMESTAMP][] = date('Y-m-d');
                $ret['data'][self::COLDWATER][] = 0;
                $ret['data'][self::HOTWATER][] = 0;
            }
            $ret['status'] = Utils::STATUS_SUCCESS;
        }
        return $ret;
    }
}
