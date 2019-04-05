function showElectricityStat()
{
    var x_voltage = ["x"];
    var y_vlotage = ["Voltage"];

    var x_amperage = ["x"];
    var y_amperage = ["Amperage"];

    var x_power = ["x"];
    var y_power = ["Power"];
    var ts = new Date();
    var ts_time = ts.getTime();

    for (var i=-10; i<0; i++) {
        x_voltage.push(ts_time + 2 * i * 1000);
        var voltage_tmp = Math.random() * (225 - 215) + 215;
        y_vlotage.push(voltage_tmp);

        var amperage_tmp = Math.random();
        x_amperage.push(ts_time + 2 * i * 1000);
        y_amperage.push(amperage_tmp);

        x_power.push(ts_time + 2 * i * 1000);
        y_power.push((voltage_tmp * amperage_tmp * Math.sin(0.9))/1000);
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
                height: 50,
                tick: {
                    //count: 3,
                    rotate: -45,
                    multiline: false,
                    format: "%H:%M:%S",
                    outer: true
                },
            },
            y: {
                min: 210,
                max: 240,
                tick: {
                    count: 7,
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
                height: 50,
                tick: {
                    //count: 3,
                    rotate: -45,
                    multiline: false,
                    format: "%H:%M:%S",
                    outer: true
                },
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
        padding: {
            top: 10,
            bottom: 0,
            right: 10,
        },
    });

    var power = bb.generate({
        bindto: "#Power",
        data: {
            x: "x",
            columns: [
                x_power,
                y_power
            ],
            type: "spline",
            colors: {
                Power: "green",
            },
        },

        axis: {
            x: {
                type: "timeseries",
                height: 50,
                tick: {
                    rotate: -45,
                    multiline: false,
                    format: "%H:%M:%S",
                    outer: true
                },
            },
            y: {
                min: 0,
                max: 8,
                tick: {
                    count: 5,
                },
                padding: {bottom:0, top: 0},
            }
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

                var x_power = ["x", ts.getTime()];
                var y_power = ["Power", result['data']['getCurrentCircuitValues']['Power']];

                power.flow({
                    columns: [
                        x_power, y_power
                    ],
                    duration: 500,
                });

            } else {
                showModalAlert(result['status'], result['data']);
            }
        });
    }, 2000);
}