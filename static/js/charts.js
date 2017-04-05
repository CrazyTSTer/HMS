/**
 * Created by crazytster on 04.04.17.
 */
var yAxis = {
    title: {
        text: 'Литры (л)'
    },
    min: 0
};
var series = [
    {
        name: 'Холодная вода',
        color: '#7cb5ec',
        data: []
    }, {
        name: 'Горячая вода',
        color: '#f45b5b',
        data: []
    }
];

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
            text: ''
        },
        xAxis: {
            type: 'datetime',
            dateTimeLabelFormats: {
                hour: '%H:%M'
            },
            title: {
                text: 'Время (ЧЧ:ММ)'
            }
        },
        yAxis: yAxis,
        tooltip: {
            headerFormat: '<b>{series.name}</b><br>',
            pointFormat: '{point.x:%H:%M:%S}: {point.y:2f} л'
        },
        plotOptions: {
            spline: {
                marker: {
                    enabled: true
                }
            }
        },
        series: series
    });
}

function currentMonthChart()
{
    cm_chart = Highcharts.chart('current_month', {
        chart: {
            type: 'column',
            events: {
                drilldown: function(e) {
                    alert(e.seriesOptions.name);
                }
            }
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
                for(var i=this.yData.length; i--;) { total += this.yData[i]; };
                return this.name + ' - Всего: ' + total;
            }
        },
        xAxis: {
            title: {
                text: 'Число'
            },
            categories: [],
            crosshair: true
        },
        yAxis: yAxis,
        tooltip: {
            headerFormat: '<span style="font-size:14px"><b>{point.key}</b></span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td><td style="padding:0"><b>{point.y:1f} л</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function () {
                            alert('Category: ' + this.category + ', value: ' + this.y);
                        }
                    }
                }
            }
        },
        series: series
    });
}