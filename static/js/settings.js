function sendPayCode()
{
    var param = {
        location: 'Settings',
        action: 'actionGetPayCodeInfo',
        paycode: $('#payCodeInput').val(),
        flat: $('#flatInput').val(),
    };

    executeAjaxPostRequest(param, function(result) {
        console.log(result);
    });
}