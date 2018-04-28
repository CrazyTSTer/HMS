/**
 * Created by crazytster on 04.04.17.
 */
var DayChart, MonthChart, Last12MonthChart;

var chartOptions = {
    data: {
        type: "",
        xFormat: "",
        x: "",
        xs: "",
        colors: {
            coldwater: "#26c6da",
            hotwater: "#dc3545",
        },
        columns: [],
    },
    bar: {
        width: {
            ratio: 0.5
        }
    },
    axis: {
        x: {
            type: "timeseries",
            height: 80,
            tick: {
                count:"",
                rotate: -45,
                multiline: false,
                format: "",
                outer: true
            },
        },
        y: {
            tick: {
                /*count: 10,*/
                min: 0,
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
    legend: {
        position: "inset"
    },
    padding: {
        top: 0,
        bottom: 0,
        right: 10,
    },
    bindto: "",
};

function generateDayChart(data)
{
    var _chartOptions = chartOptions;
    _chartOptions.data.type = "line";
    _chartOptions.data.xFormat = "%Y-%m-%d %H:%M:%S";
    _chartOptions.data.xs = {
        coldwater: "tscw",
        hotwater: "tshw",
    };
    _chartOptions.data.columns = [
        data['tscw'],
        data['tshw'],
        data['coldwater'],
        data['hotwater'],
    ];
    _chartOptions.axis.x.tick.count = 24;
    _chartOptions.axis.x.tick.format = "%H:%M";
    _chartOptions.grid.x.show = true;
    _chartOptions.bindto = "#day_rate";
    DayChart = bb.generate(_chartOptions);
}

function generateMonthChart(data)
{
    var _chartOptions = chartOptions;
    _chartOptions.data.type = "bar";
    _chartOptions.data.xFormat = "%Y-%m-%d";
    _chartOptions.data.x = "ts";
    _chartOptions.data.columns = [
        data['ts'],
        data['coldwater'],
        data['hotwater']
    ];
    _chartOptions.axis.x.tick.format = "%_d %b. (%a)";
    _chartOptions.grid.x.show = true;
    _chartOptions.bindto = "#month_rate";
    MonthChart = bb.generate(_chartOptions);
}

function generateLast12MonthChart(data)
{
    var _chartOptions = chartOptions;
    _chartOptions.data.type = "bar";
    _chartOptions.data.xFormat = "%Y-%m";
    _chartOptions.data.x = "ts";
    _chartOptions.data.columns = [
        data['ts'],
        data['coldwater'],
        data['hotwater']
    ];
    _chartOptions.axis.x.tick.count = "";
    _chartOptions.axis.x.tick.format = "%b. %Y";
    _chartOptions.grid.x.show = false;
    _chartOptions.bindto = "#last_12_month_rate";
    Last12MonthChart = bb.generate(_chartOptions);
}