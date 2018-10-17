var waterMetersInfo;
//Common
function getMetersInfoFromConfig() {
    var param = {
        location: 'Settings',
        action:   'actionGetWaterMetersInfoFromConfig',
        config:   'Water'
    };

    executeAjaxGetRequest(param, parseWaterMetersInfo);
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

function saveWaterMetersInfoToConfig() {
    var dataToSave = {
        'paycode': waterMetersInfo['paycode'] ? waterMetersInfo['paycode'] : '',
        'flat'   : waterMetersInfo['flat'] ? waterMetersInfo['flat'] : '',
        'address': waterMetersInfo['address'] ? waterMetersInfo['address'] : [],
        'meters' : waterMetersInfo['meters'] ? waterMetersInfo['meters'] : []
    };

    var param = {
        location:   'Settings',
        action:     'actionSaveWaterSettings',
        config:     'Water',
        dataToSave: JSON.stringify(dataToSave),
    };

    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}

function resetWaterMetersInfo() {
    $('.js_water_district').text('');
    $('.js_water_street').text('');
    $('.js_water_house').text('');
    $('.js_water_building').text('');
    $('.js_water_flat').text('');

    $('#waterPayCodeInput').val('');
    $('#waterFlatInput').val('');

    $("#waterTableMetersInfo tbody").html("");

    waterMetersInfo = [];
    saveWaterMetersInfoToConfig();
}

function waterTypeChage(el) {
    waterMetersInfo['meters'][el.id.split('_').pop() - 1]['type'] = el.value;
}

function parseWaterMetersInfo(result) {
    if (result['status'] == 'success') {
        waterMetersInfo = result['data'];
        //if (jQuery.isEmptyObject(waterMetersInfo)) return;
        $('.js_water_district').text(waterMetersInfo['address']['district'] ? waterMetersInfo['address']['district'] : '');
        $('.js_water_street').text(waterMetersInfo['address']['street'] ? waterMetersInfo['address']['street'] : '');
        $('.js_water_house').text(waterMetersInfo['address']['house'] ? waterMetersInfo['address']['house'] : '');
        $('.js_water_building').text(waterMetersInfo['address']['building'] ? waterMetersInfo['address']['building'] : '');
        $('.js_water_flat').text(waterMetersInfo['address']['flat'] ? waterMetersInfo['address']['flat'] : '');

        $('#waterPayCodeInput').val(waterMetersInfo['paycode']);
        $('#waterFlatInput').val(waterMetersInfo['flat']);

        var i = 0;
        $("#waterTableMetersInfo tbody").html("");
        waterMetersInfo['meters'].forEach(function(element) {
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
            $('#waterTableMetersInfo').append(table_row);

            $("#Meter_" + i + " option[value=" + element['type'] + "]").attr('selected','selected');
        })
    } else {
        showModalAlert(result['status'], result['data']);
    }
}

//Electricity
function getElectricityMeterInfoFromPgu() {
    var param = {
        location:            'Settings',
        action:              'actionGetElectricityMeterInfoFromPgu',
        config:              'Electricity',
        electricityPayCode:  $('#electricityPayCodeInput').val(),
        meterID:             $('#meterID').val(),
    };

    executeAjaxPostRequest(param, parseElectricityMeterInfo);
}

function parseElectricityMeterInfo(result) {
    if (result['status'] == 'success') {

    } else {
        showModalAlert(result['status'], result['data']);
    }
}