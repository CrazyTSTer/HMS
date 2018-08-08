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
            min: 0,
            padding: {bottom:0},
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
    var _chartOptions = jQuery.extend(true, {}, chartOptions);
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
    var _chartOptions = jQuery.extend(true, {}, chartOptions);
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
    var _chartOptions = jQuery.extend(true, {}, chartOptions);
    _chartOptions.data.type = "bar";
    _chartOptions.data.xFormat = "%Y-%m";
    _chartOptions.data.x = "ts";
    _chartOptions.data.columns = [
        data['ts'],
        data['coldwater'],
        data['hotwater']
    ];
    _chartOptions.axis.x.tick.format = "%b. %Y";
    _chartOptions.grid.x.show = false;
    _chartOptions.bindto = "#last_12_month_rate";
    Last12MonthChart = bb.generate(_chartOptions);
}


function loadDayData(date) {
    executeAjaxGetRequest({location: 'WaterStat', action: 'actionGet', param: 'day', date: date}, function (result) {
        if (result['status'] == 'success') {
            if (result['data']['current_day']['status'] == 'success') {
                $('.js_day').text(result['data']['current_day']['data']['date']);
                generateDayChart(result['data']['current_day']['data']);
            }
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });
}

function loadMonthData(date) {
    executeAjaxGetRequest({location: 'WaterStat', action: 'actionGet', param: 'month', date: date}, function (result) {
        if (result['status'] == 'success') {
            if (result['data']['current_month']['status'] == 'success') {
                $('.js_month').text(moment(result['data']['current_month']['data']['date']).format('MMMM'));
                $('.js_day_list').html('');
                for (var i = result['data']['current_month']['data']['ts'].length - 1; i > 0 ; i--) {
                    $('.js_day_list').append('<a class="dropdown-item" href="#" onclick="loadDayData(\'' + result['data']['current_month']['data']['ts'][i] + '\'); return false;">' + moment(result['data']['current_month']['data']['ts'][i]).format('DD MMMM') + '</a>');
                }
                generateMonthChart(result['data']['current_month']['data']);
                loadDayData(result['data']['current_month']['data']['ts'][result['data']['current_month']['data']['ts'].length - 1]);
            }
        } else {
            showModalAlert(result['status'], result['data']);
        }
    });
}