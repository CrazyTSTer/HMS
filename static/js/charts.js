/**
 * Created by crazytster on 04.04.17.
 */
var cd_chart, cm_chart, last12Month_chart;
var cw_gauge, hw_gauge;
var dateFormatOptions;

var gaugeOptions = {
    cube: "",
    data: {
        columns: [],
        type: "gauge",
    },
    gauge: {
        min: 0,
        max: 1000,
        label: {
            format: function(value) {return gaugeOptions.cube + ',' + value;},
            show: false, //show min max labels
        },
        fullCircle: true,
    },
    size: {
        height: ($(window).width() < 768) ? 120 : 150,
        width: ($(window).width() < 768) ? 120 : 150
    },
    tooltip: {
        show: false
    },
    color: {
        pattern: "",
    },
    bindto: ""
};

var barOptions = {
    data: {
        type: "bar",
        x: "ts",
        columns: [],
        colors: {
            coldwater: "blue",
            hotwater: "red"
        }
    },
    bar: {
        width: {
            ratio: 0.3
        }
    },
    axis: {
        x: {
            type: "timeseries",
            tick: {
                rotate: 45,
                format: function (x) {
                    return x.toLocaleString("ru", dateFormatOptions);
                }
            },
            height: ""
        }
    },
    grid: {
        y: {
            show: true
        },
    },
    bindto: ""
};

$(window).resize(function() {
    if ($(window).width() < 768) {
        cw_gauge.resize({
            height:120,
            width:120
        });
        hw_gauge.resize({
            height:120,
            width:120
        });
    } else {
        cw_gauge.resize({
            height:150,
            width:150
        });
        hw_gauge.resize({
            height:150,
            width:150
        });
    }
});

function generateGauge(key, value)
{
    gaugeOptions.data.columns = [["data", value['liter']]];
    gaugeOptions.cube = value['cube'];

    if (key == 'coldwater') {
        gaugeOptions.bindto = "#coldwater";
        gaugeOptions.color.pattern = ["blue"];
        $("#coldwater").html("");
        cw_gauge = bb.generate(gaugeOptions);
    } else if (key == 'hotwater') {
        gaugeOptions.bindto = "#hotwater";
        gaugeOptions.color.pattern = ["red"];
        $("#hotwater").html("");
        hw_gauge = bb.generate(gaugeOptions);
    }
}

function generateChart(key, value)
{
    if (key == 'current_day' && value['status'] == 'success') {
        cd_chart = bb.generate({
            bindto: "#cd_chart",
            padding: {
                right: 25
            },
            data: {
                xFormat: '%Y-%m-%d %H:%M:%S',
                type: 'line',
                columns: [
                    value['data']['bb']['tsx1'],
                    value['data']['bb']['tsx2'],
                    value['data']['bb']['coldwater'],
                    value['data']['bb']['hotwater'],
                ],
                xs: {
                    coldwater: "x1",
                    hotwater: "x2",
                },
                colors: {
                    coldwater: "blue",
                    hotwater: "red"
                }
            },
            axis: {
                x: {
                    type: "timeseries",
                    tick: {
                        count: 24,
                        format: "%H:%M",
                        rotate: 75,
                        multiline: false
                    },
                    height: 80
                }
            },
            grid: {
                x: {
                    show: true
                },
                y: {
                    show: true
                }
            },
        });
    }
    if (key == 'current_month' && value['status'] == 'success') {
        dateFormatOptions = {
            month: 'short',
            day: 'numeric',
            weekday: 'short',
        };

        barOptions.data.columns = [
            value['data']['ts'],
            value['data']['coldwater'],
            value['data']['hotwater']
        ];

        barOptions.bindto = "#cm_chart"
        cm_chart = bb.generate(barOptions);
    }
    if (key == 'last_12month' && value['status'] == 'success') {
        dateFormatOptions = {
            year: 'numeric',
            month: 'short',
        };

        barOptions.data.columns = [
            value['data']['ts'],
            value['data']['coldwater'],
            value['data']['hotwater']
        ];

        barOptions.bindto = "#last12Month_chart"
        last12Month_chart = bb.generate(barOptions);
    }
}