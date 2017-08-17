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

    public static function parseCurrentDate() {}

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

    public static function parseCurrentDay($data, $currentDate)
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

            //Добавляем дату, которую будем показывать
            $ret['data']['date'] = date('Y-m-d', strtotime($data[1][self::TIMESTAMP]));

            //Добавляем первую точку (начало дня)
            $ts = strtotime(date('Y-m-d 00:00:00', strtotime($data[1][self::TIMESTAMP]))) * 1000;
            $ret['data'][self::COLDWATER][] = [$ts, 0];
            $ret['data'][self::HOTWATER][] = [$ts, 0];

            $ret['data']['chart_js'][self::TIMESTAMP][] = $ts;
            $ret['data']['chart_js'][self::COLDWATER][] = 0;
            $ret['data']['chart_js'][self::HOTWATER][] = 0;
            for ($i = 1; $i < $data[DB::MYSQL_ROWS_COUNT]; $i++) {
                $ret['data']['chart_js'][self::TIMESTAMP][] = $data[$i][self::TIMESTAMP];
                $ret['data']['chart_js'][self::COLDWATER][] = $data[$i][self::COLDWATER] - $coldWaterFirstValue;
                $ret['data']['chart_js'][self::HOTWATER][] = $data[$i][self::HOTWATER] - $hotWaterFirstValue;
            }

            for ($i = 1; $i < $data[DB::MYSQL_ROWS_COUNT]; $i++) {
                //Смотрим интервал между двумя точками
                $current_ts = strtotime($data[$i][self::TIMESTAMP]);
                $prev_ts = strtotime($data[$i - 1][self::TIMESTAMP]);
                $interval = round(abs($current_ts - $prev_ts) / 60);

                //Если интервал больше 5 минут, рисуем точку, на минуту раньше текущей
                if ($interval > 5) {
                    $point_ts = ($current_ts - 60) * 1000;//Сдвигаемся на минуту назад
                    if ($data[$i][self::COLDWATER] - $data[$i - 1][self::COLDWATER] != 0) {
                        $ret['data'][self::COLDWATER][] = [
                            $point_ts,
                            $data[$i - 1][self::COLDWATER] - $coldWaterFirstValue,
                        ];
                    }
                    if ($data[$i][self::HOTWATER] - $data[$i - 1][self::HOTWATER] != 0) {
                        $ret['data'][self::HOTWATER][] = [
                            $point_ts,
                            $data[$i - 1][self::HOTWATER] - $hotWaterFirstValue,
                        ];
                    }
                }

                //Рисуем текущую точку
                $current_ts = $current_ts * 1000;
                $ret['data'][self::COLDWATER][] = [
                    $current_ts,
                    $data[$i][self::COLDWATER] - $coldWaterFirstValue,
                ];
                $ret['data'][self::HOTWATER][] = [
                    $current_ts,
                    $data[$i][self::HOTWATER] - $hotWaterFirstValue,
                ];
            }

            //Добавляем последнюю точку на вермя $currentDate
            $ts = $currentDate * 1000;
            $ret['data'][self::COLDWATER][] = [
                $ts,
                $data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::COLDWATER] - $coldWaterFirstValue,
            ];
            $ret['data'][self::HOTWATER][] = [
                $ts,
                $data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::HOTWATER] - $hotWaterFirstValue,
            ];

            $ret['status'] = Utils::STATUS_SUCCESS;
        }
        return $ret;
    }

    public static function parseMonth($data, $currentDate, $isLast12Month = false)
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
            for ($i = 1; $i < $data[DB::MYSQL_ROWS_COUNT]; $i++) {
                $ts = strtotime($data[$i][self::TIMESTAMP]);
                $ret['data'][self::TIMESTAMP][0][] = $isLast12Month ? strftime('%h. %Y', $ts) : strftime('%e %h. (%a)', $ts);
                $ret['data'][self::TIMESTAMP][1][] = date('Y-m-d', $ts);
                $ret['data'][self::COLDWATER][] = $data[$i][self::COLDWATER] - $data[$i - 1][self::COLDWATER];
                $ret['data'][self::HOTWATER][] = $data[$i][self::HOTWATER] - $data[$i - 1][self::HOTWATER];
            }

            $ts = strtotime($data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::TIMESTAMP]);

            //Если для текущего дня/месяца еще нет данных, добавляем нулевую точку
            if (($isLast12Month
                    ? date('Y-m', $ts) < date('Y-m', $currentDate)
                    : date('Y-m-d', $ts) < date('Y-m-d', $currentDate))
            ) {
                $ret['data'][self::TIMESTAMP][0][] = $isLast12Month ? strftime('%h. %Y', $currentDate) : strftime('%e %h. (%a)', $currentDate);
                $ret['data'][self::TIMESTAMP][1][] = date('Y-m-d', $currentDate);
                $ret['data'][self::COLDWATER][] = 0;
                $ret['data'][self::HOTWATER][] = 0;
            }
            $ret['status'] = Utils::STATUS_SUCCESS;
        }
        return $ret;
    }
}
