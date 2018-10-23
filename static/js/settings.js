const WATER ='water';
const ELECTRICITY = 'electricity';
const CONFIG_NAME = 'configName';
const CONFIG_DATA = 'data';

var config = {
    water: {
        configName: 'WaterMeterInfo',
        data: [],
    },
    electricity: {
        configName: 'ElectricityMeterInfo',
        data: [],
    }
};

//Common
function getMetersInfoFromConfig() {
    var water_param = {
        location: 'Settings',
        action:   'actionGetMetersInfoFromConfig',
        config:   config[WATER][CONFIG_NAME],
    };

    var electricity_param = {
        location: 'Settings',
        action:   'actionGetMetersInfoFromConfig',
        config:   config[ELECTRICITY][CONFIG_NAME],
};

    executeAjaxGetRequest(water_param, parseWaterMetersInfo);
    executeAjaxGetRequest(electricity_param, parseElectricityMeterInfo);
}

function saveMetersInfoToConfig(cfg) {
    var param = {
        location:   'Settings',
        action:     'actionSaveMetersInfoToConfig',
        config:     config[cfg][CONFIG_NAME],
        dataToSave: JSON.stringify(config[cfg][CONFIG_DATA]),
    };

    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}

function eraseMetersInfoFromConfig(cfg) {
    if (cfg == WATER) {
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
    } else if (cfg == ELECTRICITY) {
        $('.js_electricity_address').text('');
        $('.js_electricity_setupDate').text('');
        $('.js_electricity_meterType').text('');
        $('.js_electricity_numberOfDigit').text('');
        $('.js_electricity_MPI').text('');

        $('#electricityPayCodeInput').val('');
        $('#electricityMeterID').val('');

        $('#electricityAddressForm').addClass('d-none');
        $('#electricitySaveForm').addClass('d-none');
    }

    config[cfg][CONFIG_DATA] = [];

    var param = {
        location:   'Settings',
        action:     'actionEraseMetersInfoFromConfig',
        config:     config[cfg][CONFIG_NAME],
    };

    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}

//Water
function getWaterMetersInfoFromPgu() {
    var param = {
        location: 'Settings',
        action:   'actionGetWaterMetersInfoFromPgu',
        config:   'Water',
        paycode:  $('#waterPayCodeInput').val(),
        flat:     $('#waterFlatInput').val(),
    };

    executeAjaxPostRequest(param, parseWaterMetersInfo);
}

function parseWaterMetersInfo(result) {
    if (result['status'] == 'success') {
        config[WATER][CONFIG_DATA] = result['data'];

        $('.js_water_district').text('');
        $('.js_water_street').text('');
        $('.js_water_house').text('');
        $('.js_water_building').text('');
        $('.js_water_flat').text('');

        $('#waterPayCodeInput').val('');
        $('#waterFlatInput').val('');

        if (!jQuery.isEmptyObject(config[WATER][CONFIG_DATA])) {
            $('#waterAddressForm').removeClass('d-none');
            $('#waterMetersInfo').removeClass('d-none');
            $('#waterSaveForm').removeClass('d-none');

            $('.js_water_district').text(config[WATER][CONFIG_DATA]['address']['district']);
            $('.js_water_street').text(config[WATER][CONFIG_DATA]['address']['street']);
            $('.js_water_house').text(config[WATER][CONFIG_DATA]['address']['house']);
            $('.js_water_building').text(config[WATER][CONFIG_DATA]['address']['building']);
            $('.js_water_flat').text(config[WATER][CONFIG_DATA]['address']['flat']);

            $('#waterPayCodeInput').val(config[WATER][CONFIG_DATA]['paycode']);
            $('#waterFlatInput').val(config[WATER][CONFIG_DATA]['flat']);
        } else {
            $('#waterAddressForm').addClass('d-none');
            $('#waterMetersInfo').addClass('d-none');
            $('#waterSaveForm').addClass('d-none');
        }

        var i = 0;
        $("#tableWaterMetersInfo tbody").html("");

        if (config[WATER][CONFIG_DATA]['meters']) {
            config[WATER][CONFIG_DATA]['meters'].forEach(function(element) {
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

function waterTypeChage(el) {
    config[WATER][CONFIG_DATA]['meters'][el.id.split('_').pop() - 1]['type'] = el.value;
}

//Electricity
function getElectricityMeterInfoFromPgu() {
    var param = {
        location:            'Settings',
        action:              'actionGetElectricityMeterInfoFromPgu',
        config:              'Electricity',
        electricityPayCode:  $('#electricityPayCodeInput').val(),
        meterID:             $('#electricityMeterID').val(),
    };

    executeAjaxPostRequest(param, parseElectricityMeterInfo);
}

function parseElectricityMeterInfo(result) {
    if (result['status'] == 'success') {
        config[ELECTRICITY][CONFIG_DATA] = result['data'];

        $('.js_electricity_address').text('');
        $('.js_electricity_setupDate').text('');
        $('.js_electricity_meterType').text('');
        $('.js_electricity_numberOfDigit').text('');
        $('.js_electricity_MPI').text('');

        $('#electricityPayCodeInput').val('');
        $('#electricityMeterID').val('');

        if (!jQuery.isEmptyObject(config[ELECTRICITY][CONFIG_DATA])) {
            $('.js_electricity_address').text(config[ELECTRICITY][CONFIG_DATA]['address']);
            $('.js_electricity_setupDate').text(config[ELECTRICITY][CONFIG_DATA]['setupDate']);
            $('.js_electricity_meterType').text(config[ELECTRICITY][CONFIG_DATA]['meterType']);
            $('.js_electricity_numberOfDigit').text(config[ELECTRICITY][CONFIG_DATA]['numberOfDigit']);
            $('.js_electricity_MPI').text(config[ELECTRICITY][CONFIG_DATA]['MPI']);

            $('#electricityPayCodeInput').val(config[ELECTRICITY][CONFIG_DATA]['paycode']);
            $('#electricityMeterID').val(config[ELECTRICITY][CONFIG_DATA]['meterID']);

            $('#electricityAddressForm').removeClass('d-none');
            $('#electricitySaveForm').removeClass('d-none');
        } else {
            $('#electricityAddressForm').addClass('d-none');
            $('#electricitySaveForm').addClass('d-none');
        }
    } else {
        showModalAlert(result['status'], result['data']);
    }
}