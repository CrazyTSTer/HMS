var waterMetersInfo;

function getWaterMetersInfo() {
    var param = {
        location: 'Settings',
        action: 'actionGetWaterMetersInfo',
        paycode: $('#payCodeInput').val(),
        flat: $('#flatInput').val(),
    };

    executeAjaxPostRequest(param, function(result) {
        if (result['status'] == 'success') {
            waterMetersInfo = result['data'];
            parseWaterMetersInfo(waterMetersInfo);
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });
}

function parseWaterMetersInfo(result) {
    $('.js_district').text(result['address']['district']);
    $('.js_street').text(result['address']['street']);
    $('.js_house').text(result['address']['house']);
    $('.js_building').text(result['address']['building']);
    $('.js_flat').text(result['address']['flat']);

    var i = 0;
    $("#tableMetersInfo tbody").html("");
    result['meters'].forEach(function(element) {
        i++;
        var table_row = "<tr>" +
            "<td class=\"meter_header\">Meter " + i + "</td>" +
            "<td data-title=\"ID:\" class=\"align-middle\">" + element['id'] + "</td>" +
            "<td data-title=\"Номер:\" class=\"align-middle\">" + element['number'] + "</td>" +
            "<td data-title=\"Тип:\" class=\"align-middle\">" +
            "<select class=\"form-control form-control-sm\" id=\"Meter" + i +"\">" +
            "<option value=1>ХВС</option>" +
            "<option value=2>ГВС</option>" +
            "</select>" +
            "</td>" +
            "<td data-title=\"Поверка:\" class=\"align-middle\">" + element['checkup'] + "</td>" +
            "</tr>";
        $('#tableMetersInfo').append(table_row);

        $("#Meter" + i + " option[value=" + element['type'] + "]").attr('selected','selected');
    });
}

function resetWaterMetersInfo() {
    $('.js_district').text('');
    $('.js_street').text('');
    $('.js_house').text('');
    $('.js_building').text('');
    $('.js_flat').text('');

    $("#tableMetersInfo tbody").html("");

    waterMetersInfo = '';

    /*var param = {
        location: 'Settings',
        action: 'actionResetWaterSettings',
    };
    executeAjaxPostRequest(param, '');*/
}

function saveWaterMetersInfo() {
    var dataToSave = {
        'paycode': waterMetersInfo['paycode'] ? waterMetersInfo['paycode'] : '',
        'flat'   : waterMetersInfo['flat'] ? waterMetersInfo['flat'] : '',
        'address': waterMetersInfo['address'] ? waterMetersInfo['address'] : {},
        'meters' : waterMetersInfo['meters'] ? waterMetersInfo['meters'] : {},
    };
    var param = {
        location:   'Settings',
        action:     'actionSaveWaterSettings',
        dataToSave: dataToSave,
    };
    executeAjaxPostRequest(param, function(result) {
        showModalAlert(result['status'], result['data']);
    });
}

function getSettingsFromConfig() {
    var param = {
        location: 'Settings',
        action:   'actionGetSettingsFromConfig',
    };
    executeAjaxGetRequest(param, function(result) {
        if (result['status'] == 'success') {
            $("#payCodeInput").val(result['data']['paycode']);
            $("#flatInput").val(result['data']['flat']);
            waterMetersInfo = result['data'];
            parseWaterMetersInfo(waterMetersInfo);
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });
}