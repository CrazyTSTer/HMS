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
            text: 'Потребление холодной и горячей воды за месяц'
        },
        subtitle: {
            text: '(разбивка по дням)'
        },
        legend: {
            labelFormatter: function() {
                var total = 0;
                for(var i=this.series.length; i--;) { total += this.series[i]; }
                return this.name + '- Total: ' + total;
            }
        }
        xAxis: {
            title: {
                text: 'Число'
            },
            categories: [],
            crosshair: true
        },
        yAxis: {
            title: {
                text: 'Литры (л)'
            },
            min: 0,
        },
        tooltip: {
            headerFormat: '<span style="font-size:14px"><b>{point.key}</b></span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td><td style="padding:0"><b>{point.y:.1f} mm</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
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

