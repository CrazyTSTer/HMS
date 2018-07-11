function sendPayCode()
{
    var param = {
        location: 'Settings',
        action: 'actionGetPayCodeInfo',
        paycode: $('#payCodeInput').val(),
        flat: $('#flatInput').val(),
    };

    executeAjaxPostRequest(param, function(result) {
        /*if (result['status'] == 'success') {
            $('.js_district').text(result['data']['district']);
            $('.js_street').text(result['data']['street']);
            $('.js_house').text(result['data']['house']);
            $('.js_building').text(result['data']['building']);
            $('.js_flat').text(result['data']['flat']);
        } else {
            alert(result['status'] + ": " + result['data']);
        }*/
        console.log(result);
    });
}