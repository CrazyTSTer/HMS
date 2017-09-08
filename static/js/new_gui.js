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
    /*if ($(window).width() < 768) {
        $("#coldwater").attr('viewBox', '0 0 100 100');
        $("#hotwater").attr('viewBox', '0 0 100 100');
    } else {
        $("#coldwater").attr('viewBox', '0 0 200 200');
        $("#hotwater").attr('viewBox', '0 0 200 200');
    }*/
    get_main_stats(true);
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
    get_main_stats(true);
}

function get_main_stats(debug)
{
    if (debug) {
        var chart = bb.generate({
            "data": {
                "columns": [
                    ["data", 91.4]
                ],
                "type": "gauge",
            },
            "gauge": {},
            "color": {
                "pattern": [
                    "#FF0000",
                    "#F97600",
                    "#F6C600",
                    "#60B044"
                ],
                "threshold": {
                    "values": [
                        30,
                        60,
                        90,
                        100
                    ]
                }
            },
            "size": {
                "height": 100
            },
            "bindto": "#coldwater"
        });
        var chart1 = bb.generate({
            "data": {
                "columns": [
                    ["data", 91.4]
                ],
                "type": "gauge",
            },
            "gauge": {},
            "color": {
                "pattern": [
                    "#FF0000",
                    "#F97600",
                    "#F6C600",
                    "#60B044"
                ],
                "threshold": {
                    "values": [
                        30,
                        60,
                        90,
                        100
                    ]
                }
            },
            "size": {
                "height": 100
            },
            "bindto": "#hotwater"
        });
        /*d3.select("#coldwater").call(d3.liquidfillgauge, 872, chart_common, '138,872');
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
        );*/
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
    /*setChartGlobalParams();
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
    });*/
    var chart = bb.generate({
        "data": {
            "xs": {
                "data1": "x1",
                "data2": "x2"
            },
            "columns": [
                ["x1", 10, 30, 45, 50, 70, 100],
                ["x2", 30, 50, 75, 100, 120],
                ["data1", 30, 200, 100, 400, 150, 250],
                ["data2", 20, 180, 240, 100, 190]
            ]
        },
        "bindto": "#cd_chart"
    });
    var chart1 = bb.generate({
        "data": {
            "xs": {
                "data1": "x1",
                "data2": "x2"
            },
            "columns": [
                ["x1", 10, 30, 45, 50, 70, 100],
                ["x2", 30, 50, 75, 100, 120],
                ["data1", 30, 200, 100, 400, 150, 250],
                ["data2", 20, 180, 240, 100, 190]
            ]
        },
        "bindto": "#cm_chart"
    });
    var chart2 = bb.generate({
        "data": {
            "xs": {
                "data1": "x1",
                "data2": "x2"
            },
            "columns": [
                ["x1", 10, 30, 45, 50, 70, 100],
                ["x2", 30, 50, 75, 100, 120],
                ["data1", 30, 200, 100, 400, 150, 250],
                ["data2", 20, 180, 240, 100, 190]
            ]
        },
        "bindto": "#last12Month_chart"
    });
}