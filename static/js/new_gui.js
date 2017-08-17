/**
 * Created by crazytster on 03.08.17.
 */
var chart_common = {
    textVertPosition: 0.8,
    waveAnimateTime: 5000,
    waveHeight: 0.15,
    waveAnimate: true,
    waveOffset: 0.25,
    valueCountUp: true,
    displayPercent: false,
    maxValue: 1000,
    textSize: 0.5,
};

jQuery(document).ready(function() {
    $(".js_set_focus").focus();
    if ($(window).width() < 768) {
        $("#coldwater").attr('viewBox', '0 0 100 100');
        $("#hotwater").attr('viewBox', '0 0 100 100');
    } else {
        $("#coldwater").attr('viewBox', '0 0 150 150');
        $("#hotwater").attr('viewBox', '0 0 150 150');
    }

    get_main_stats(false);


    /*setInterval(function() {
        executeAjaxRequest({action: 'get', param: 'current_val'}, function (result) {
            var cw_cube = result['data']['coldwater']['cube'];
            var cw_liter = result['data']['coldwater']['liter'];
            var hw_cube = result['data']['hotwater']['cube'];
            var hw_liter = result['data']['hotwater']['liter'];
            d3.select("#coldwater").on("valueChanged")(cw_liter, cw_cube + ',' + cw_liter);
            d3.select("#hotwater").on("valueChanged")(hw_liter, hw_cube + ',' + hw_liter);
        });
    }, 2000);*/
});

/*$(document).on('click','.navbar-collapse.in',function(e) {
    if( $(e.target).is('a') && $(e.target).attr('class') != 'dropdown-toggle' ) {
        $(this).collapse('hide');
    }
});*/

function show_water_stats()
{
    $('.js_main_stats').hide();
    $('.js_water_graphs').show();
    chart();
}

function show_main_stats()
{
    $('.js_water_graphs').hide();
    $('.js_main_stats').show();
    get_main_stats(false);
}

function get_main_stats(debug)
{
    if (debug) {
        d3.select("#coldwater").call(d3.liquidfillgauge, 872, chart_common, '138,872');
        d3.select("#hotwater").call(
            d3.liquidfillgauge,
            423,
            $.extend(
                {},
                chart_common,
                {
                    circleColor: "#d73232",
                    waveColor: "#d73232",
                    textColor: "#9e1f1f",
                    waveTextColor: "#FFC8C8"
                }
            ), '121,423'
        );
    } else {
        executeAjaxRequest({action: 'get', param: 'current_val'}, function (result) {
            var last_insert = result['data']['ts'];

            var cw_cube = result['data']['coldwater']['cube'];
            var cw_liter = result['data']['coldwater']['liter'];
            var cw_day_rate = result['data']['coldwater']['day_rate'];
            var cw_month_rate = result['data']['coldwater']['month_rate'];
            var cw_prev_month_rate = result['data']['coldwater']['prev_month_rate'];

            var hw_cube = result['data']['hotwater']['cube'];
            var hw_liter = result['data']['hotwater']['liter'];
            var hw_day_rate = result['data']['hotwater']['day_rate'];
            var hw_month_rate = result['data']['hotwater']['month_rate'];
            var hw_prev_month_rate = result['data']['hotwater']['prev_month_rate'];

            $("#coldwater").html("");
            $("#hotwater").html("");
            d3.select("#coldwater").call(d3.liquidfillgauge, cw_liter, chart_common, cw_cube + ',' + cw_liter);
            d3.select("#hotwater").call(
                d3.liquidfillgauge,
                hw_liter,
                $.extend(
                    {},
                    chart_common,
                    {
                        circleColor: "#d73232",
                        waveColor: "#d73232",
                        textColor: "#9e1f1f",
                        waveTextColor: "#FFC8C8",
                    }
                ),
                hw_cube + ',' + hw_liter
            );
            $(".js_last_insert").text(last_insert);

            $(".js_cold_current_value").text(cw_cube + ',' + cw_liter);
            $(".js_cold_today_rate").text(cw_day_rate);
            $(".js_cold_month_rate").text(cw_month_rate);
            $(".js_cold_prev_month_rate").text(cw_prev_month_rate);

            $(".js_hot_current_value").text(hw_cube + ',' + hw_liter);
            $(".js_hot_today_rate").text(hw_day_rate);
            $(".js_hot_month_rate").text(hw_month_rate);
            $(".js_hot_prev_month_rate").text(hw_prev_month_rate);
        });
    }
}
function chart() {
    setChartGlobalParams();
    currentDayChart();
    currentMonthChart();
    last12MonthChart();

    executeAjaxRequest({action: 'get', param: 'current'}, function (result) {
        if (result['status'] == 'success') {
            updateChart(cd_chart, result['data']['current_day']);
            updateChart(cm_chart, result['data']['current_month'], true);
            updateChart(last12Month_chart, result['data']['last_12month'], true);
        } else {
            alert('SMTH GOES WRONG!');
        }
    });
}
/*function chart() {
    Highcharts.chart('container', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Monthly Average Rainfall'
        },
        subtitle: {
            text: 'Source: WorldClimate.com'
        },
        xAxis: {
            categories: [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            ],
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Rainfall (mm)'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
            '<td style="padding:0"><b>{point.y:.1f} mm</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: [{
            name: 'Tokyo',
            data: [49.9, 71.5, 106.4, 129.2, 144.0, 176.0, 135.6, 148.5, 216.4, 194.1, 95.6, 54.4]

        }, {
            name: 'New York',
            data: [83.6, 78.8, 98.5, 93.4, 106.0, 84.5, 105.0, 104.3, 91.2, 83.5, 106.6, 92.3]

        }, {
            name: 'London',
            data: [48.9, 38.8, 39.3, 41.4, 47.0, 48.3, 59.0, 59.6, 52.4, 65.2, 59.3, 51.2]

        }, {
            name: 'Berlin',
            data: [42.4, 33.2, 34.5, 39.7, 52.6, 75.5, 57.4, 60.4, 47.6, 39.1, 46.8, 51.1]

        }]
    });

    Highcharts.chart('container1', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Monthly Average Rainfall'
        },
        subtitle: {
            text: 'Source: WorldClimate.com'
        },
        xAxis: {
            categories: [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            ],
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Rainfall (mm)'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
            '<td style="padding:0"><b>{point.y:.1f} mm</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: [{
            name: 'Tokyo',
            data: [49.9, 71.5, 106.4, 129.2, 144.0, 176.0, 135.6, 148.5, 216.4, 194.1, 95.6, 54.4]

        }, {
            name: 'New York',
            data: [83.6, 78.8, 98.5, 93.4, 106.0, 84.5, 105.0, 104.3, 91.2, 83.5, 106.6, 92.3]

        }, {
            name: 'London',
            data: [48.9, 38.8, 39.3, 41.4, 47.0, 48.3, 59.0, 59.6, 52.4, 65.2, 59.3, 51.2]

        }, {
            name: 'Berlin',
            data: [42.4, 33.2, 34.5, 39.7, 52.6, 75.5, 57.4, 60.4, 47.6, 39.1, 46.8, 51.1]

        }]
    });
    Highcharts.chart('container2', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Monthly Average Rainfall'
        },
        subtitle: {
            text: 'Source: WorldClimate.com'
        },
        xAxis: {
            categories: [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            ],
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Rainfall (mm)'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
            '<td style="padding:0"><b>{point.y:.1f} mm</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: [{
            name: 'Tokyo',
            data: [49.9, 71.5, 106.4, 129.2, 144.0, 176.0, 135.6, 148.5, 216.4, 194.1, 95.6, 54.4]

        }, {
            name: 'New York',
            data: [83.6, 78.8, 98.5, 93.4, 106.0, 84.5, 105.0, 104.3, 91.2, 83.5, 106.6, 92.3]

        }, {
            name: 'London',
            data: [48.9, 38.8, 39.3, 41.4, 47.0, 48.3, 59.0, 59.6, 52.4, 65.2, 59.3, 51.2]

        }, {
            name: 'Berlin',
            data: [42.4, 33.2, 34.5, 39.7, 52.6, 75.5, 57.4, 60.4, 47.6, 39.1, 46.8, 51.1]

        }]
    });
}*/