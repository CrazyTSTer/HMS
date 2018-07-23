function sendPayCode()
{
    var param = {
        location: 'Settings',
        action: 'actionGetPayCodeInfo',
        paycode: $('#payCodeInput').val(),
        flat: $('#flatInput').val(),
    };

    executeAjaxPostRequest(param, function(result) {
        if (result['status'] == 'success') {
            $('.js_district').text(result['data']['address']['district']);
            $('.js_street').text(result['data']['address']['street']);
            $('.js_house').text(result['data']['address']['house']);
            $('.js_building').text(result['data']['address']['building']);
            $('.js_flat').text(result['data']['address']['flat']);

            var i = 0;
            $("#tableMetersInfo tbody").html("");
            result['data']['meters'].forEach(function(element) {
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
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });
}