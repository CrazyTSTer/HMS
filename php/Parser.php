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

    public static function parseCurrentDay($data, $currentDate = null)
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

            for ($i = 0; $i < $data[DB::MYSQL_ROWS_COUNT]; $i++) {
                $ts = strtotime($data[$i][self::TIMESTAMP]) * 1000;

                $ret['data'][self::COLDWATER][] = [
                    $ts,
                    $data[$i][self::COLDWATER] - $coldWaterFirstValue,
                ];
                $ret['data'][self::HOTWATER][] = [
                    $ts,
                    $data[$i][self::HOTWATER] - $hotWaterFirstValue,
                ];

                if (!array_key_exists($i + 1, $data)) continue;

                //Get time interval between two points
                $ts1 = strtotime($data[$i + 1][self::TIMESTAMP]);
                $ts2 = strtotime($data[$i][self::TIMESTAMP]);
                $interval = round(abs($ts1 - $ts2) / 60);

                if ($interval > 5) {
                    $ts = ($ts1 - 60) * 1000;//Сдвигаемся на минуту назад
                    if ($data[$i][self::COLDWATER] - $data[$i + 1][self::COLDWATER] != 0) {
                        $ret['data'][self::COLDWATER][] = [
                            $ts,
                            $data[$i][self::COLDWATER] - $coldWaterFirstValue,
                        ];
                    }
                    if ($data[$i][self::HOTWATER] - $data[$i + 1][self::HOTWATER] != 0) {
                        $ret['data'][self::HOTWATER][] = [
                            $ts,
                            $data[$i][self::HOTWATER] - $hotWaterFirstValue,
                        ];
                    }
                }
            }
            if (!is_null($currentDate)) {
                $ts = $currentDate * 1000;
                $ret['data'][self::COLDWATER][] = [
                    $ts,
                    $data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::COLDWATER] - $coldWaterFirstValue,
                ];
                $ret['data'][self::HOTWATER][] = [
                    $ts,
                    $data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::HOTWATER] - $hotWaterFirstValue,
                ];
            }
            $ret['status'] = Utils::STATUS_SUCCESS;
        }
        return $ret;
    }

    public static function parseMonth($data, $currentDate = null, $isLast12Month = false)
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
                $ret['data'][self::TIMESTAMP][0][] = $isLast12Month ? date('M Y', $ts) : date('jS M \(D\)', $ts);
                $ret['data'][self::TIMESTAMP][1][] = date('Y-m-d', $ts);
                $ret['data'][self::COLDWATER][] = $data[$i][self::COLDWATER] - $data[$i - 1][self::COLDWATER];
                $ret['data'][self::HOTWATER][] = $data[$i][self::HOTWATER] - $data[$i - 1][self::HOTWATER];
            }

            $ts = strtotime($data[$data[DB::MYSQL_ROWS_COUNT] - 1][self::TIMESTAMP]);
            if (!is_null($currentDate)
                && ($isLast12Month
                    ? date('Y-m', $ts) < date('Y-m', strtotime($currentDate))
                    : date('Y-m-d', $ts) < date('Y-m-d', strtotime($currentDate))
                )
            ) {
                $ret['data'][self::TIMESTAMP][0][] = $isLast12Month ? date('M Y', strtotime($currentDate)) : date('jS M \(D\)', strtotime($currentDate));
                $ret['data'][self::TIMESTAMP][1][] = date('Y-m-d', strtotime($currentDate));
                $ret['data'][self::COLDWATER][] = 0;
                $ret['data'][self::HOTWATER][] = 0;
            }
            $ret['status'] = Utils::STATUS_SUCCESS;
        }
        return $ret;
    }
}
