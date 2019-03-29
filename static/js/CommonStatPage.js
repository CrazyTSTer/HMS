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

    executeAjaxGetRequest({location: 'ElectricityStat', action: 'actionGet', param: ['getCurrentPowerValues']}, function (result) {
        if (result['status'] == 'success') {
            $(".js_tz1").text(result['data']['getCurrentPowerValues']['TZ1']);
            $(".js_tz2").text(result['data']['getCurrentPowerValues']['TZ2']);
            $(".js_tz3").text(result['data']['getCurrentPowerValues']['TZ3']);
            $(".js_tz4").text(result['data']['getCurrentPowerValues']['TZ4']);
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });

    var x_voltage = ["x"];
    var x_amperage = ["x"];
    var y_vlotage = ["Voltage"];

    var x_amperage = ["x"];
    var y_amperage = ["Amperage"];
    var ts = new Date();
    var ts_time = ts.getTime()
    for (var i=-10; i<0; i++) {
        x_voltage.push(ts_time + 5* i * 1000);
        y_vlotage.push(Math.random() * (225 - 215) + 215);

        x_amperage.push(ts_time + 5* i * 1000);
        y_amperage.push(Math.random());
    }
    var voltage = bb.generate({
        bindto: "#Voltage",
        data: {
            x: "x",
            columns: [
                x_voltage,
                y_vlotage
            ],
            type: "spline",
            colors: {
                Voltage: "#0000FF",
            },
        },

        axis: {
            x: {
                type: "timeseries",
                tick: {
                    format: "%H:%M:%S",
                }
            },
            y: {
                min: 210,
                max: 230,
                tick: {
                    count: 5,
                },
                padding: {bottom:0, top: 0},
            }
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
        legend: {
            position: "inset"
        },
        padding: {
            top: 10,
            bottom: 0,
            right: 10,
        },
    });

    var amperage = bb.generate({
        bindto: "#Amperage",
        data: {
            x: "x",
            columns: [
                x_amperage,
                y_amperage
            ],
            type: "spline",
            colors: {
                Amperage: "#FF0000",
            },
        },

        axis: {
            x: {
                type: "timeseries",
                tick: {
                    format: "%H:%M:%S",
                }
            },
            y: {
                min: 0,
                max: 10,
                tick: {
                    count: 5,
                },
                padding: {bottom:0, top: 0},
            }
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
        legend: {
            position: "inset"
        },
        padding: {
            top: 10,
            bottom: 0,
            right: 10,
        },
    });

    setInterval(function () {
        var ts = new Date();
        executeAjaxGetRequest({location: 'ElectricityStat', action: 'actionGet', param: ['getCurrentCircuitValues']}, function (result) {
            if (result['status'] == 'success') {
                var x_voltage = ["x", ts.getTime()];
                var y_vlotage = ["Voltage", result['data']['getCurrentCircuitValues']['Voltage']];
                voltage.flow({
                    columns: [
                        x_voltage, y_vlotage
                    ],
                    duration: 500,
                });

                var x_amperage = ["x", ts.getTime()];
                var y_amperage = ["Amperage", result['data']['getCurrentCircuitValues']['Amperage']];
                amperage.flow({
                    columns: [
                        x_amperage, y_amperage
                    ],
                    duration: 500,
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