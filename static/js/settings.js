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
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });
}