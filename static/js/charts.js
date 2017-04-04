/**
 * Created by crazytster on 04.04.17.
 */
function setChartGlobalParams()
{
    Highcharts.setOptions({
        global: {
            useUTC: false
        }
    });
}

function currentDayChart()
{
    cd_chart = Highcharts.chart('current_day', {
        chart: {
            type: 'spline',
            zoomType: 'x'
        },
        title: {
            text: 'Потребление холодной и горячей воды за день'
        },
        subtitle: {
            text: '%дата%'
        },
        xAxis: {
            type: 'datetime',
            dateTimeLabelFormats: { // don't display the dummy year
                hour: '%H:%M:%S'
            },
            title: {
                text: 'Время (ЧЧ:ММ)'
            }
        },
        yAxis: {
            title: {
                text: 'Литры (л)'
            },
            min: 0,
        },
        tooltip: {
            headerFormat: '<b>{series.name}</b><br>',
            pointFormat: '{point.x:%H:%M:%S}: {point.y:2f} л'
        },

        plotOptions: {
            spline: {
                marker: {
                    enabled: true,
                }
            }
        },

        series: [{
            name: 'Холодная вода',
            color: 'Blue',
            data: []
        }, {
            name: 'Горячая вода',
            color: 'Red',
            data: []
        }]
    });
}

function currentMonthChart()
{
    cm_chart = Highcharts.chart('current_month', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Потребление холодной и горячей воды за месяц(разбивка по дням)'
        },
        subtitle: {
            text: '%дата%'
        },
        xAxis: {
             title: {
                text: 'Число'
            },
            crosshair: true
        },
        yAxis: {
            title: {
                text: 'Литры (л)'
            },
            min: 0,
        },
        tooltip: {
            headerFormat: '<b>{series.name}</b>',
            pointFormat: '{point.y:2f} л'
        },

        series: [{
            name: 'Холодная вода',
            color: 'Blue',
            data: []
        }, {
            name: 'Горячая вода',
            color: 'Red',
            data: []
        }]
    });
}

