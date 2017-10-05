/**
 * Created by crazytster on 04.04.17.
 */
var cd_chart, cm_chart, last12Month_chart;
var cw_gauge, hw_gauge;

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

var chartOptions = {
    data: {
        type: "",
        xFormat: "",
        x: "",
        xs: "",
        colors: {
            coldwater: "blue",
            hotwater: "red",
        },
        columns: [],
    },
    bar: {
        width: {
            ratio: 0.3
        }
    },
    axis: {
        x: {
            type: "timeseries",
            height: 80,
            tick: {
                count:"",
                rotate: 45,
                multiline: false,
                format: "",
            },
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
    bindto: "",
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
        chartOptions.data.type = "line";
        chartOptions.data.xFormat = "%Y-%m-%d %H:%M:%S";
        chartOptions.data.x = "";
        chartOptions.data.xs = {
            coldwater: "tscw",
            hotwater: "tshw",
        };
        chartOptions.data.columns = [
            value['data']['tscw'],
            value['data']['tshw'],
            value['data']['coldwater'],
            value['data']['hotwater'],
        ];
        chartOptions.axis.x.tick.count = 24;
        chartOptions.axis.x.tick.format = "%H:%M";
        chartOptions.grid.x.show = true;
        chartOptions.bindto = "#cd_chart";
        cd_chart = bb.generate(chartOptions);
    }
    if (key == 'current_month' && value['status'] == 'success') {
        chartOptions.data.type = "bar";
        chartOptions.data.xFormat = "%Y-%m-%d";
        chartOptions.data.x = "ts";
        chartOptions.data.xs = "";
        chartOptions.data.columns = [
            value['data']['ts'],
            value['data']['coldwater'],
            value['data']['hotwater']
        ];
        chartOptions.axis.x.tick.count = "";
        chartOptions.axis.x.tick.format = "%_d %b. (%a)";
        chartOptions.grid.x.show = false;
        chartOptions.bindto = "#cm_chart";
        cm_chart = bb.generate(chartOptions);
    }
    if (key == 'last_12month' && value['status'] == 'success') {
        chartOptions.data.type = "bar";
        chartOptions.data.xFormat = "%Y-%m";
        chartOptions.data.x = "ts";
        chartOptions.data.xs = "";
        chartOptions.data.columns = [
            value['data']['ts'],
            value['data']['coldwater'],
            value['data']['hotwater']
        ];
        chartOptions.axis.x.tick.count = "";
        chartOptions.axis.x.tick.format = "%b. %Y";
        chartOptions.grid.x.show = false;
        chartOptions.bindto = "#last12Month_chart";
        last12Month_chart = bb.generate(chartOptions);
    }
}