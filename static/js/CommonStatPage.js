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

    executeAjaxGetRequest({location: 'ElectricityStat', action: 'actionGet', param: ['getCurrentPowerValues', 'getCurrentCircuitValues']}, function (result) {
        if (result['status'] == 'success') {
            $(".js_tz1").text(result['data']['getCurrentPowerValues']['TZ1']);
            $(".js_tz2").text(result['data']['getCurrentPowerValues']['TZ2']);
            $(".js_tz3").text(result['data']['getCurrentPowerValues']['TZ3']);
            $(".js_tz4").text(result['data']['getCurrentPowerValues']['TZ4']);
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });

    var x_data = ["x"];
    var y_data = ["Voltage"];
    var ts = new Date();
    var ts_time = ts.getTime()
    for (var i=-10; i<0; i++) {
        x_data.push(ts_time + 5* i * 1000);
        y_data.push(Math.random() * (225 - 215) + 215);
    }
    var chart = bb.generate({
        bindto: "#voltage",
        data: {
            x: "x",
            columns: [
                x_data,
                y_data
            ],
            type: "spline",
        },
        axis: {
            x: {
                type: "timeseries",
                tick: {
                    format: "%H:%M:%S"
                }
            },
            data1: "y"
        },
        grid: {
            y: {
                lines: [
                    {
                        value: 220
                    }
                ]
            }
        },
    });
    chart.axis.range({max: 230, min: 210});
    setInterval(function () {
        var ts = new Date();
        executeAjaxGetRequest({location: 'ElectricityStat', action: 'actionGet', param: ['getCurrentCircuitValues']}, function (result) {
            if (result['status'] == 'success') {
                var x_data = ["x", ts.getTime()];
                var y_data = ["Voltage", result['data']['getCurrentCircuitValues']['Voltage']];
                chart.flow({
                    columns: [
                        x_data, y_data
                    ],
                    duration: 1500,
                });
            } else {
                showModalAlert(result['status'], result['data']);
            }
        });
        }, 5000);
}

function sendWaterMetersDataToPgu()
{
    executeAjaxPostRequest({location: 'WaterStat', action: 'actionSendDataToPGU'}, function (result) {
        showModalAlert(result['status'], result['data']);
    });
}