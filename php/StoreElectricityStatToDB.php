<?php
include_once "Utils.php";

define('QUERY', 'INSERT INTO #table# (TZ1, TZ2, TZ3, TZ4, total) VALUES (#TZ1#, #TZ2#, #TZ3#, #TZ4#, #total#)');

const DEBUG = true;

$data['table'] = ElectricityStat::MYSQL_TABLE_WATER;
$electricityStat = new ElectricityStat(DEBUG);

if (!$electricityStat->db->isConnected()) {
    $electricityStat->db->disconnect();
    die('Failed connect to DB');
}

$result = $electricityStat->executeCommands([ElectricityMetersSettings::GET_CURRENT_POWER_VALUES]);
if ($result[ElectricityMetersSettings::GET_CURRENT_POWER_VALUES] == NULL) {
    $electricityStat->db->disconnect();
    die('Failed to get data from Electricity Meter');
}

$parsedResult = ElectricityParser::parseData($result);
$total = 0;
foreach ($parsedResult[ElectricityMetersSettings::GET_CURRENT_POWER_VALUES] as $key => $value) {
    $data[$key] = $value;
    $total += $value;
}

$data[ElectricityStat::TOTAL] = $total;

$result = $electricityStat->db->executeQuery(QUERY, $data, false);
$electricityStat->db->disconnect();

if ($result !== true) {
    die('Failed to insert data into DB');
}

