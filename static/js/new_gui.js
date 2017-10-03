/**
 * Created by crazytster on 03.08.17.
 */

/*$(document).on('click','.navbar-collapse.in',function(e) {
 if( $(e.target).is('a') && $(e.target).attr('class') != 'dropdown-toggle' ) {
 $(this).collapse('hide');
 }
 });*/

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

var chart, chart1;

$(window).resize(function() {
    if ($(window).width() < 768) {
        chart.resize({
            height:125,
            width:125
        });
        chart1.resize({
            height:125,
            width:125
        });
    } else {
        chart.resize({
            height:150,
            width:150
        });
        chart1.resize({
            height:150,
            width:150
        });
    }
});


jQuery(document).ready(function() {
    $(".js_set_focus").focus();

    show_main_stats();
});

function show_main_stats()
{
    $('.js_water_graphs').hide();
    $('.js_main_stats').show();
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

         chart = bb.generate({
            data: {
                columns: [
                    ["data", cw_liter]
                ],
                type: "gauge",
            },
            color: {
                pattern: ["blue"],
            },
            gauge: {
                min: 0,
                max: 1000,
                label: {
                    format: function(value) {return cw_cube + ',' + value;},
                    show: false, //show min max labels
                },
                fullCircle: true,
            },
            size: {
                height: ($(window).width() < 768) ? 125 : 150,
                width: ($(window).width() < 768) ? 125 : 150
            },
            tooltip: {
                show: false
            },
            bindto: "#coldwater"
        });

         chart1 = bb.generate({
            data: {
                columns: [
                    ["data", hw_liter]
                ],
                type: "gauge",
            },
            color: {
                pattern: ["red"],
            },
            gauge: {
                min: 0,
                max: 1000,
                label: {
                    format: function(value) {return hw_cube + ',' + value;},
                    show: false, //show min max labels
                },
                fullCircle: true,
            },
            size: {
                height: ($(window).width() < 768) ? 125 : 150,
                width: ($(window).width() < 768) ? 125 : 150
            },
            tooltip: {
                show: false
            },
            bindto: "#hotwater"
        });

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

function show_water_stats()
{
    $('.js_main_stats').hide();
    $('.js_water_graphs').show();
    executeAjaxRequest({action: 'get', param: 'current'}, function (result) {
        if (result['status'] == 'success') {
            $.each(result['data'], function (key, value) {
                generateChart(key, value);
            });
        } else {
            alert('SMTH GOES WRONG!');
        }
    });
}