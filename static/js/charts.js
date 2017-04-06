/**
 * Created by crazytster on 04.04.17.
 */
var yAxis = {
    title: {
        text: 'Литры (л)'
    },
    min: 0
};
var seriesData = [
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
        }
    });
    cd_chart.addAxis(yAxis, false);
    cd_chart.addSeries(seriesData);
}

function currentMonthChart()
{
    var options = {
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
                for(var i=this.yData.length; i--;) { total += this.yData[i]; };
                return this.name + ' - Всего: ' + total;
            }
        },
        xAxis: {
            title: {
                text: 'Число'
            },
            categories: [],
            crosshair: {
                enabled: true,
                events: {
                    click: function() {
                        cm_chart.series[0].data.forEach(function(e){
                            e.update({ color: '#7cb5ec' }, true, false);
                        });
                        cm_chart.series[1].data.forEach(function(e){
                            e.update({ color: '#f45b5b' }, true, false);
                        });
                        cm_chart.series[0].data[cm_chart.columnIndex].update({ color: 'blue' }, true, false);
                        cm_chart.series[1].data[cm_chart.columnIndex].update({ color: 'red' }, true, false);
                        cm_chart.redraw();
                    }
                }
            }
        },
        tooltip: {
            //headerFormat: '<span style="font-size:14px"><b>{point.key}</b></span><table>',
            //pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td><td style="padding:0"><b>{point.y:1f} л</b></td></tr>',
            //footerFormat: '</table>',
            shared: true,
            //useHTML: true,
            formatter: function(tooltip) {
                var items = this.points || splat(this), s;
                cm_chart.columnIndex = cm_chart.options.xAxis[0].categories.indexOf(this.x);
                // Build the header
                s = [tooltip.tooltipFooterHeaderFormatter(items[0])];
                // build the values
                s = s.concat(tooltip.bodyFormatter(items));
                // footer
                s.push(tooltip.tooltipFooterHeaderFormatter(items[0], true));
                return s;
            }
        },
        plotOptions: {
            series: {
                events: {
                    click: function() {
                        cm_chart.series[0].data.forEach(function(e){
                            e.update({ color: '#7cb5ec' }, true, false);
                        });
                        cm_chart.series[1].data.forEach(function(e){
                            e.update({ color: '#f45b5b' }, true, false);
                        });
                        cm_chart.series[0].data[cm_chart.columnIndex].update({ color: 'blue' }, true, false);
                        cm_chart.series[1].data[cm_chart.columnIndex].update({ color: 'red' }, true, false);
                        cm_chart.redraw();
                    }
                }
            }
        }
    };
    cm_chart = Highcharts.chart('current_month', options);
    cm_chart.addAxis(yAxis, false);
    cm_chart.addSeries(seriesData);
}