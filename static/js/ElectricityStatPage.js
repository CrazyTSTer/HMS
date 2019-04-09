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

    voltage = bb.generate({
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
                    show: false,
                    text: {
                        show: false
                    }

                },
            },
            y: {
                min: 210,
                max: 240,
                tick: {
                    count: 4,
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
            bottom: -10,
            right: 10,
        },
        legend: {
            show: false,
        }
    });

    amperage = bb.generate({
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
                    show: false,
                    text: {
                        show: false
                    }

                },
            },
            y: {
                min: 0,
                max: 32,
                tick: {
                    count: 5,
                },
                padding: {bottom:0, top: 0},
            }
        },
        padding: {
            top: 10,
            bottom: -10,
            right: 10,
        },
        legend: {
            show: false,
        }
    });

    power = bb.generate({
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
                tick: {
                    show: false,
                    text: {
                        show: false
                    }

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
            bottom: -10,
            right: 10,
        },
        legend: {
            show: false,
        }
    });

    clearTimeout(timer);
    getData();
}

function getData()
{
    var ts = new Date();

    executeAjaxGetRequest({location: 'ElectricityStat', action: 'actionGet', param: 'execute_command', cmds:['getCurrentCircuitValues']}, function (result) {
        if (result['status'] == 'success') {
            voltage.flow({
                columns: [
                    ["x", ts.getTime()], ["Voltage", result['data']['getCurrentCircuitValues']['Voltage']]
                ],
                duration: 500,
            });

            amperage.flow({
                columns: [
                    ["x", ts.getTime()], ["Amperage", result['data']['getCurrentCircuitValues']['Amperage']]
                ],
                duration: 500,
            });

            power.flow({
                columns: [
                    ["x", ts.getTime()], ["Power", result['data']['getCurrentCircuitValues']['Power']]
                ],
                duration: 500,
            });

            $(".js_voltage_value").text(result['data']['getCurrentCircuitValues']['Voltage']);
            $(".js_amperage_value").text(result['data']['getCurrentCircuitValues']['Amperage']);
            $(".js_power_value").text(result['data']['getCurrentCircuitValues']['Power']);
        } else {
            showModalAlert(result['status'], result['data']);
        }

        clearTimeout(timer);
        timer = setTimeout(function () {
            getData();
        }, 2000);
    });
}