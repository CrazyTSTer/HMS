<?php
include_once "Utils.php";

const DEBUG = true;

$electricityStat = new ElectricityStat(DEBUG);

$result = $electricityStat->executeCommands([ElectricityMetersSettings::GET_CURRENT_POWER_VALUES]);

if ($result[ElectricityMetersSettings::GET_CURRENT_POWER_VALUES] == NULL) {
    die('Failed to get data from Electricity Meter');
}

$parsedResult = ElectricityParser::parseData($result);

$result = $electricityStat->storeValuesToDB($parsedResult[ElectricityMetersSettings::GET_CURRENT_POWER_VALUES]);

if ($result !== true) {
    die('Failed to insert data into DB');
}

