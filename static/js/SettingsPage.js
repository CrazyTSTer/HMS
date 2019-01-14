//Config sections
const WATER_CFG             = 'water';
const ELECTRICITY_CFG       = 'electricity';
const PGU_CFG               = 'pgu';

//Each config section has two parameters
const CONFIG_NAME       = 'configName';
const CONFIG_DATA       = 'data';

//Php class name
const LOCATION_SETTINGS = 'Settings';

//Php method in class
const ACTION_GET_DATA_FROM_CONFIG   = 'actionGetDataFromConfig';
const ACTION_SAVE_DATA_TO_CONFIG    = 'actionSaveDataToConfig';
const ACTION_ERASE_DATA_FROM_CONFIG = 'actionEraseDataFromConfig';


var config = {
    water: {
        configName: 'WaterMeterInfo',
        data: [],
    },
    electricity: {
        configName: 'ElectricityMeterInfo',
        data: [],
    },
    pgu: {
        configName: 'PguInfo',
        data: [],
    },
};

//Common
function getDataFromConfig(cfg)
{
    var param = {
        location: LOCATION_SETTINGS,
        action:   ACTION_GET_DATA_FROM_CONFIG,
        config:   config[cfg][CONFIG_NAME],
    };

    if (cfg == WATER_CFG) {
        executeAjaxGetRequest(param, parseWaterMetersInfo);
    } else if (cfg == ELECTRICITY_CFG) {
        executeAjaxGetRequest(param, parseElectricityMeterInfo);
    }
}

function saveDataToConfig(cfg)
{
    var param = {
        location:   LOCATION_SETTINGS,
        action:     ACTION_SAVE_DATA_TO_CONFIG,
        config:     config[cfg][CONFIG_NAME],
        dataToSave: JSON.stringify(config[cfg][CONFIG_DATA]),
    };

    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}

function eraseDataFromConfig(cfg)
{
    if (cfg == WATER_CFG) {
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
    } else if (cfg == ELECTRICITY_CFG) {
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
    }

    config[cfg][CONFIG_DATA] = [];

    var param = {
        location:   LOCATION_SETTINGS,
        action:     ACTION_ERASE_DATA_FROM_CONFIG,
        config:     config[cfg][CONFIG_NAME],
    };

    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}

//Water
function getWaterMetersInfoFromPgu()
{
    var param = {
        location: LOCATION_SETTINGS,
        action:   'actionGetWaterMetersInfoFromPgu',
        config:   'Water',
        paycode:  $('#waterPayCodeInput').val(),
        flat:     $('#waterFlatInput').val(),
    };

    executeAjaxPostRequest(param, parseWaterMetersInfo);
}

function parseWaterMetersInfo(result)
{
    if (result['status'] == 'success') {
        var res = result['data'];
        config[WATER_CFG][CONFIG_DATA] = res;

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
    config[WATER_CFG][CONFIG_DATA]['meters'][el.id.split('_').pop() - 1]['type'] = el.value;
}

//Electricity
function getElectricityMeterInfoFromPgu()
{
    var param = {
        location:            LOCATION_SETTINGS,
        action:              'actionGetElectricityMeterInfoFromPgu',
        config:              'Electricity',
        electricityPayCode:  $('#electricityPayCodeInput').val(),
        meterID:             $('#electricityMeterID').val(),
    };

    executeAjaxPostRequest(param, parseElectricityMeterInfo);
}

function parseElectricityMeterInfo(result)
{
    if (result['status'] == 'success') {
        var res = result['data'];
        config[ELECTRICITY_CFG][CONFIG_DATA] = res;

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
        location:   LOCATION_SETTINGS,
        action:     'actionGenerateElectricityMeterCommands',
        config:     config[ELECTRICITY_CFG][CONFIG_NAME],
    };

    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}

//PGU
function getPGUInfoFromForm()
{

}