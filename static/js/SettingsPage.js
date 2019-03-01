//Config sections
const WATER_CFG             = 'water';
const ELECTRICITY_CFG       = 'electricity';
const PGU_CFG               = 'pgu';

//Each config section has two parameters
const CONFIG_DATA       = 'data';

//Php class name
const WATER_SETTINGS_CLASS       = 'WaterMetersSettings';
const ELECTRICITY_SETTINGS_CLASS = 'ElectricityMetersSettings';

//Php method in class
const ACTION_GET_METERS_SETTINGS   = 'actionGetMetersSettings';
const ACTION_SAVE_METERS_SETTINGS    = 'actionSaveMetersSettings';
const ACTION_ERASE_METERS_SETTINGS = 'actionEraseMetersSettings';

var WaterMetersSettings;
var ElectricityMetersSettings;

//Common
function getMetersSettings(settingsClass)
{
    var param = {
        location: settingsClass,
        action:   ACTION_GET_METERS_SETTINGS,
    };

    if (settingsClass == WATER_SETTINGS_CLASS) {
        executeAjaxGetRequest(param, parseWaterMetersInfo);
    } else if (settingsClass == ELECTRICITY_SETTINGS_CLASS) {
        executeAjaxGetRequest(param, parseElectricityMeterInfo);
    }
}

function saveMetersSettings(settingsClass)
{
    var data;

    if (settingsClass == WATER_SETTINGS_CLASS) {
        data = WaterMetersSettings;
    } else if (settingsClass == ELECTRICITY_SETTINGS_CLASS) {
        data = ElectricityMetersSettings;
    }

    var param = {
        location:   settingsClass,
        action:     ACTION_SAVE_METERS_SETTINGS,
        dataToSave: JSON.stringify(data),
    };

    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}

function eraseMetersSettings(settingsClass)
{
    if (settingsClass == WATER_SETTINGS_CLASS) {
        $('.js_water_district').text('');
        $('.js_water_street').text('');
        $('.js_water_house').text('');
        $('.js_water_building').text('');
        $('.js_water_flat').text('');

        $('#waterPayCodeInput').val('');
        $('#waterFlatInput').val('');

        $("#tableWaterMetersInfo tbody").html("");

        $('#waterAddressForm').addClass('d-none');
        $('#waterMetersInfo').addClass('d-none');
        $('#waterSaveForm').addClass('d-none');
        WaterMetersSettings = [];
    } else if (settingsClass == ELECTRICITY_SETTINGS_CLASS) {
        $('.js_electricity_address').text('');
        $('.js_electricity_setupDate').text('');
        $('.js_electricity_meterType').text('');
        $('.js_electricity_numberOfDigit').text('');
        $('.js_electricity_MPI').text('');

        $('#electricityPayCodeInput').val('');
        $('#electricityMeterID').val('');

        $('#electricityAddressForm').addClass('d-none');
        $('#electricitySaveForm').addClass('d-none');
        $('#generateMeterCommandsForm').addClass('d-none');
        ElectricityMetersSettings = [];
    }

    var param = {
        location:   settingsClass,
        action:     ACTION_ERASE_METERS_SETTINGS,
    };

    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}

//Water
function getWaterMetersInfoFromPgu()
{
    var param = {
        location: WATER_SETTINGS_CLASS,
        action:   'actionGetWaterMetersInfoFromPgu',
        paycode:  $('#waterPayCodeInput').val(),
        flat:     $('#waterFlatInput').val(),
    };

    executeAjaxPostRequest(param, parseWaterMetersInfo);
}

function parseWaterMetersInfo(result)
{
    if (result['status'] == 'success') {
        var res = result['data'];
        WaterMetersSettings = res;

        $('.js_water_district').text('');
        $('.js_water_street').text('');
        $('.js_water_house').text('');
        $('.js_water_building').text('');
        $('.js_water_flat').text('');

        $('#waterPayCodeInput').val('');
        $('#waterFlatInput').val('');

        if (!jQuery.isEmptyObject(res)) {
            $('#waterAddressForm').removeClass('d-none');
            $('#waterMetersInfo').removeClass('d-none');
            $('#waterSaveForm').removeClass('d-none');

            $('.js_water_district').text(res['address']['district']);
            $('.js_water_street').text(res['address']['street']);
            $('.js_water_house').text(res['address']['house']);
            $('.js_water_building').text(res['address']['building']);
            $('.js_water_flat').text(res['address']['flat']);

            $('#waterPayCodeInput').val(res['paycode']);
            $('#waterFlatInput').val(res['flat']);
        } else {
            $('#waterAddressForm').addClass('d-none');
            $('#waterMetersInfo').addClass('d-none');
            $('#waterSaveForm').addClass('d-none');
        }

        var i = 0;
        $("#tableWaterMetersInfo tbody").html("");

        if (res['meters']) {
            res['meters'].forEach(function(element) {
                i++;
                var table_row = "<tr>" +
                    "<td class=\"meter_header\">Meter " + i + "</td>" +
                    "<td data-title=\"ID:\" class=\"align-middle\">" + element['counterNum'] + "</td>" +
                    "<td data-title=\"Номер:\" class=\"align-middle\">" + element['num'] + "</td>" +
                    "<td data-title=\"Тип:\" class=\"align-middle\">" +
                    "<select onchange='waterTypeChage(this)' class=\"form-control form-control-sm\" id=\"Meter_" + i +"\">" +
                    "<option value=1>ХВС</option>" +
                    "<option value=2>ГВС</option>" +
                    "</select>" +
                    "</td>" +
                    "<td data-title=\"Поверка:\" class=\"align-middle\">" + element['checkup'] + "</td>" +
                    "</tr>";
                $('#tableWaterMetersInfo').append(table_row);

                $("#Meter_" + i + " option[value=" + element['type'] + "]").attr('selected','selected');
            });
        }
    } else {
        showModalAlert(result['status'], result['data']);
    }
}

function waterTypeChage(el)
{
    WaterMetersSettings['meters'][el.id.split('_').pop() - 1]['type'] = el.value;
}

//Electricity
function getElectricityMeterInfoFromPgu()
{
    var param = {
        location:            ELECTRICITY_SETTINGS_CLASS,
        action:              'actionGetElectricityMeterInfoFromPgu',
        electricityPayCode:  $('#electricityPayCodeInput').val(),
        meterID:             $('#electricityMeterID').val(),
    };

    executeAjaxPostRequest(param, parseElectricityMeterInfo);
}

function parseElectricityMeterInfo(result)
{
    if (result['status'] == 'success') {
        var res = result['data'];
        ElectricityMetersSettings = res;

        $('.js_electricity_address').text('');
        $('.js_electricity_setupDate').text('');
        $('.js_electricity_meterType').text('');
        $('.js_electricity_numberOfDigit').text('');
        $('.js_electricity_MPI').text('');

        $('#electricityPayCodeInput').val('');
        $('#electricityMeterID').val('');

        if (!jQuery.isEmptyObject(res)) {
            $('.js_electricity_address').text(res['address']);
            $('.js_electricity_setupDate').text(res['setupDate']);
            $('.js_electricity_meterType').text(res['meterType']);
            $('.js_electricity_numberOfDigit').text(res['numberOfDigit']);
            $('.js_electricity_MPI').text(res['MPI']);

            $('#electricityPayCodeInput').val(res['paycode']);
            $('#electricityMeterID').val(res['meterID']);

            $('#electricityAddressForm').removeClass('d-none');
            $('#electricitySaveForm').removeClass('d-none');
            $('#generateMeterCommandsForm').removeClass('d-none');
        } else {
            $('#electricityAddressForm').addClass('d-none');
            $('#electricitySaveForm').addClass('d-none');
            $('#generateMeterCommandsForm').addClass('d-none');
        }
    } else {
        showModalAlert(result['status'], result['data']);
    }
}

function generateElectricityMeterCommands()
{
    var param = {
        location:   ELECTRICITY_SETTINGS_CLASS,
        action:     'actionGenerateElectricityMeterCommands',
    };

    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}