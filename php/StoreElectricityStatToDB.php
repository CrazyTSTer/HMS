<?php
include_once "Utils.php";

define('QUERY', 'INSERT INTO #table# (TZ1, TZ2, TZ3, TZ4, total) VALUES (#TZ1#, #TZ2#, #TZ3#, #TZ4#, #total#)');

const DEBUG = true;

const MYSQL_HOST              = '192.168.1.2';
const MYSQL_PORT              = 3306;
const MYSQL_LOGIN             = 'hms';
const MYSQL_PASS              = 'HMSStats1';
const MYSQL_BASE              = 'HMS';
const MYSQL_BASE_LOCALE       = 'utf8';
const MYSQL_TABLE_ELECTRICITY = 'Electricity';

$data['table'] = MYSQL_TABLE_ELECTRICITY;

/** @var  DB */
$db = DB::getInstance();

$db->init(MYSQL_HOST, MYSQL_PORT, MYSQL_LOGIN, MYSQL_PASS, DEBUG);
$db->connect();
$db->selectDB(MYSQL_BASE);
$db->setLocale(MYSQL_BASE_LOCALE);

if (!$db->isConnected()) {
    $db->disconnect();
    die('Failed connect to DB');
}

$electricityStat = new ElectricityStat(DEBUG);
$result = $electricityStat->executeCommands([ElectricityMetersSettings::GET_CURRENT_POWER_VALUES]);
if ($result[ElectricityMetersSettings::GET_CURRENT_POWER_VALUES] == NULL) {
    $db->disconnect();
    die('Failed to get data from Electricity Meter');
}

$parsedResult = ElectricityParser::parseData($result);
$total = 0;
foreach ($parsedResult[ElectricityMetersSettings::GET_CURRENT_POWER_VALUES] as $key => $value) {
    $data[$key] = $value;
    $total += $value;
}

$data['total'] = $total;

$result = $db->executeQuery(QUERY, $data, false);
$db->disconnect();

if ($result !== true) {
    die('Failed to insert data into DB');
}

