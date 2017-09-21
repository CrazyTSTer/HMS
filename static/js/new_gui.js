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

var debug = false;

jQuery(document).ready(function() {
    $(".js_set_focus").focus();
    if ($(window).width() < 768) {
        $("#coldwater").attr('viewBox', '0 0 100 100');
        $("#hotwater").attr('viewBox', '0 0 100 100');
    } else {
        $("#coldwater").attr('viewBox', '0 0 150 150');
        $("#hotwater").attr('viewBox', '0 0 150 150');
    }
    get_main_stats(debug);
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
    chart(debug);
}

function show_main_stats()
{
    $('.js_water_graphs').hide();
    $('.js_main_stats').show();
    get_main_stats(debug);
}

function get_main_stats(debug)
{
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
function chart(debug) {
    executeAjaxRequest({action: 'get', param: 'current'}, function (result) {
        if (result['status'] == 'success') {
            if (result['data']['current_day']['status'] == 'success') {
                var chart = bb.generate({
                    bindto: "#cd_chart",
                    padding: {
                        right: 25
                    },
                    data: {
                        xFormat: '%Y-%m-%d %H:%M:%S',
                        type: 'line',
                        columns: [
                            result['data']['current_day']['data']['bb'],
                            result['data']['current_day']['data']['bb']['ts'],
                            /*result['data']['current_day']['data']['bb']['coldwater'],
                            result['data']['current_day']['data']['bb']['hotwater']*/
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
                                count: 10,
                                rotate: 45,
                                format: "%H:%M"
                            }
                        }
                    },

                });
            }
            if (result['data']['current_month']['status'] == 'success') {
                var chart1 = bb.generate({
                    data: {
                        type: "bar",
                        x: "x2",
                        columns: [
                            result['data']['current_month']['data']['bb']['ts']['x2'],
                            result['data']['current_month']['data']['bb']['coldwater'],
                            result['data']['current_month']['data']['bb']['hotwater']
                        ],
                        colors: {
                            coldwater: "blue",
                            hotwater: "red"
                        }
                    },
                    bar: {
                        width: {
                            ratio: 0.5
                        }
                    },
                    axis: {
                        x: {
                            type: "timeseries",
                            tick: {
                                rotate: 45,
                                format: function (x) {
                                    var options = {
                                        month: 'short',
                                        day: 'numeric',
                                        weekday: 'short',
                                    };
                                    return x.toLocaleString("ru", options);
                                }
                            }
                        }
                    },
                    bindto: "#cm_chart"
                });
            }
            if (result['data']['last_12month']['status'] == 'success') {
                var chart2 = bb.generate({
                    data: {
                        type: "bar",
                        x: "x2",
                        columns: [
                            result['data']['last_12month']['data']['bb']['ts']['x2'],
                            result['data']['last_12month']['data']['bb']['coldwater'],
                            result['data']['last_12month']['data']['bb']['hotwater']
                        ],
                        colors: {
                            coldwater: "blue",
                            hotwater: "red"
                        }
                    },
                    bar: {
                        width: {
                            ratio: 0.5
                        }
                    },
                    axis: {
                        x: {
                            type: "timeseries",
                            tick: {
                                rotate: 45,
                                format: function (x) {
                                    var options = {
                                        year: 'numeric',
                                        month: 'short',
                                    };
                                    return x.toLocaleString("ru", options);
                                }
                            }
                        }
                    },
                    bindto: "#last12Month_chart"
                });
            }
        } else {
            alert('SMTH GOES WRONG!');
        }
    });
}