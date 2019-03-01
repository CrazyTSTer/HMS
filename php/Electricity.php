<?php

class Electricity
{
    const GET_SERIAL_NUMBER          = 'getSerialNumber';
    const GET_MANUFACTURED_DATE      = 'getManufacturedDate';
    const GET_FIRMWARE_VERSION       = 'getFirmWareVersion';
    const GET_BATTERY_VOLTAGE        = 'getBatteryVoltage';
    const GET_LAST_SWITCH_ON         = 'getLastSwitchOn';
    const GET_LAST_SWITCH_OFF        = 'getLastSwitchOff';
    const GET_CURRENT_CIRCUIT_VALUES = 'getCurrentCircuitValues';
    const GET_CURRENT_POWER_VALUES   = 'getCurrentPowerValues';
    const GET_CURRENT_POWER          = 'getCurrentPower';
    const GET_POWER_VALUES_BY_MONTH  = 'getPowerValuesByMonth';

    const CMD_CODE = [
        self::GET_SERIAL_NUMBER          => '2F',
        self::GET_MANUFACTURED_DATE      => '66',
        self::GET_FIRMWARE_VERSION       => '28',
        self::GET_BATTERY_VOLTAGE        => '29',
        self::GET_LAST_SWITCH_ON         => '2C',
        self::GET_LAST_SWITCH_OFF        => '2B',
        self::GET_CURRENT_CIRCUIT_VALUES => '63',
        self::GET_CURRENT_POWER          => '27',
        self::GET_CURRENT_POWER          => '26',
        self::GET_POWER_VALUES_BY_MONTH  => [
            'jan' => '3201',
            'feb' => '3202',
            'mar' => '3203',
            'apr' => '3204',
            'may' => '3205',
            'jun' => '3206',
            'jul' => '3207',
            'aug' => '3208',
            'sep' => '3209',
            'oct' => '320A',
            'nov' => '320B',
            'dec' => '320C',
        ],
    ];
}
