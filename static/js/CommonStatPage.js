function show_main_stats()
{
    executeAjaxGetRequest({location: 'WaterStat', action: 'actionGet', param: 'main_stat'}, function (result) {
        if (result['status'] == 'success') {
            $(".js_water_last_update").text(result['data']['ts']);

            $(".js_cold_curr_value").text(result['data']['coldwater']['current_value']);
            $(".js_cold_curr_day_rate").text(result['data']['coldwater']['day_rate']);
            $(".js_cold_curr_month_rate").text(result['data']['coldwater']['month_rate']);
            $(".js_cold_prev_month_rate").text(result['data']['coldwater']['prev_month_rate']);

            $(".js_hot_curr_value").text(result['data']['hotwater']['current_value']);
            $(".js_hot_curr_day_rate").text(result['data']['hotwater']['day_rate']);
            $(".js_hot_curr_month_rate").text(result['data']['hotwater']['month_rate']);
            $(".js_hot_prev_month_rate").text(result['data']['hotwater']['prev_month_rate']);
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });
}

function sendWaterMetersDataToPgu()
{
    executeAjaxGetRequest({location: 'WaterStat', action: 'actionSendDataToPGU'}, function (result) {
        showModalAlert(result['status'], result['data']);
    });
}