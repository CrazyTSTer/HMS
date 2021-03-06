function showMainStat()
{
    for (var i = 1; i < (electricityTzCount + 1); i++) {
        console.log(i);
        $(".tz" + i + "_header").removeClass("d-none");
        $(".js_tz" + i + "_curr_value").removeClass("d-none");
        $(".js_tz" + i + "_curr_day_rate").removeClass("d-none");
        $(".js_tz" + i + "_curr_month_rate").removeClass("d-none");
        $(".js_tz" + i + "_prev_month_rate").removeClass("d-none");

    }

    if (electricityShowTotal == 1) {
        $(".total_header").removeClass("d-none");
        $(".js_total_curr_value").removeClass("d-none");
        $(".js_total_curr_day_rate").removeClass("d-none");
        $(".js_total_curr_month_rate").removeClass("d-none");
        $(".js_total_prev_month_rate").removeClass("d-none");
    }

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

    executeAjaxGetRequest({location: 'ElectricityStat', action: 'actionGet', param: 'main_stat'}, function (result) {
        if (result['status'] == 'success') {
            $(".js_electricity_last_update").text(result['data']['ts']);

            $(".js_tz1_curr_value").text(result['data']['current_value']['TZ1']);
            $(".js_tz2_curr_value").text(result['data']['current_value']['TZ2']);
            $(".js_tz3_curr_value").text(result['data']['current_value']['TZ3']);
            $(".js_tz4_curr_value").text(result['data']['current_value']['TZ4']);
            $(".js_total_curr_value").text(result['data']['current_value']['total']);

            $(".js_tz1_curr_day_rate").text(result['data']['day_rate']['TZ1']);
            $(".js_tz2_curr_day_rate").text(result['data']['day_rate']['TZ2']);
            $(".js_tz3_curr_day_rate").text(result['data']['day_rate']['TZ3']);
            $(".js_tz4_curr_day_rate").text(result['data']['day_rate']['TZ4']);
            $(".js_total_curr_day_rate").text(result['data']['day_rate']['total']);

            $(".js_tz1_curr_month_rate").text(result['data']['month_rate']['TZ1']);
            $(".js_tz2_curr_month_rate").text(result['data']['month_rate']['TZ2']);
            $(".js_tz3_curr_month_rate").text(result['data']['month_rate']['TZ3']);
            $(".js_tz4_curr_month_rate").text(result['data']['month_rate']['TZ4']);
            $(".js_total_curr_month_rate").text(result['data']['month_rate']['total']);

            $(".js_tz1_curr_month_rate").text(result['data']['month_rate']['TZ1']);
            $(".js_tz2_curr_month_rate").text(result['data']['month_rate']['TZ2']);
            $(".js_tz3_curr_month_rate").text(result['data']['month_rate']['TZ3']);
            $(".js_tz4_curr_month_rate").text(result['data']['month_rate']['TZ4']);
            $(".js_total_curr_month_rate").text(result['data']['month_rate']['total']);

            $(".js_tz1_prev_month_rate").text(result['data']['prev_month_rate']['TZ1']);
            $(".js_tz2_prev_month_rate").text(result['data']['prev_month_rate']['TZ2']);
            $(".js_tz3_prev_month_rate").text(result['data']['prev_month_rate']['TZ3']);
            $(".js_tz4_prev_month_rate").text(result['data']['prev_month_rate']['TZ4']);
            $(".js_total_prev_month_rate").text(result['data']['prev_month_rate']['total']);
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });
}

function sendWaterMetersDataToPgu()
{
    executeAjaxPostRequest({location: 'WaterStat', action: 'actionSendDataToPGU'}, function (result) {
        showModalAlert(result['status'], result['data']);
    });
}