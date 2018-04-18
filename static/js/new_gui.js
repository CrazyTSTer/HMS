/**
 * Created by crazytster on 03.08.17.
 */

jQuery(document).ready(function() {
    show_main_stats();
    $('#sidebar a').on('click', function (e) {
        if ($(this).attr('data-toggle') != 'collapse') {
            $('#sidebar').find('.active').removeClass('active');
            $(this).addClass('active');
            if ($(this)[0].hasAttribute("data-target")) {
                $('#content').find('.active').removeClass('active');
                $($(this).attr('data-target')).addClass('active');
            }
            if ($('#sidebar').hasClass('show')) {
                $('.navbar-toggler').button().click()
                $('body,html').animate({scrollTop: 0}, 400);
            }
        } else {
            if (!$(this).hasClass('a_sub-active')) {
                $(this).addClass('a_sub-active');
            } else {
                $(this).removeClass('a_sub-active');
            }
            if ($(this).siblings('ul').hasClass('sub-active')) {
                //$(this).removeClass('a_sub-active');
                var el = $(this).siblings('ul').removeClass('sub-active').find('.sub-active').siblings('a').get().reverse();
                $(el).click();
            } else {
                $(this).siblings('ul').addClass('sub-active');
                //$(this).addClass('a_sub-active');
            }
        }
    });

    $('.navbar-toggler').button().on('click', function() {
        var el = $("#sidebar a[data-toggle*='collapse'][aria-expanded*='true']").get().reverse();
        $(el).click();
    });
});

function show_main_stats()
{
    executeAjaxRequest({action: 'get', param: 'current_val'}, function (result) {
        if (result['status'] == 'success') {
            $(".js_water_last_update").text(result['data']['ts']);

            $(".js_cold_curr_value").text(result['data']['coldwater']['cube'] + ',' + result['data']['coldwater']['liter']);
            $(".js_cold_curr_day_rate").text(result['data']['coldwater']['day_rate']);
            $(".js_cold_curr_month_rate").text(result['data']['coldwater']['month_rate']);
            $(".js_cold_prev_month_rate").text(result['data']['coldwater']['prev_month_rate']);

            $(".js_hot_curr_value").text(result['data']['hotwater']['cube'] + ',' + result['data']['hotwater']['liter']);
            $(".js_hot_curr_day_rate").text(result['data']['hotwater']['day_rate']);
            $(".js_hot_curr_month_rate").text(result['data']['hotwater']['month_rate']);
            $(".js_hot_prev_month_rate").text(result['data']['hotwater']['prev_month_rate']);
        } else {
            alert(result['status'] + ": " + result['data']);
        }
    });
}

function show_graph_rate()
{
    var options = {
        grid: {
            borderColor: "#3e4e56",
            borderWidth: 1,
            backgroundColor: { colors: ["#263238", "#3e4e56"] },
            hoverable: true,
            clickable: true,
        },
        yaxis: {
            tickColor: "#3e4e56", // or same color as background
        },
        xaxis: {
            mode: "time",
            /*tickFormatter: function (val, axis) {
                return moment(val).format("HH:mm");
            },*/
            timezone: "browser",
            timeformat: "%H:%M",
            tickSize: [2, "hour"],

            tickColor: "#3e4e56", // or same color as background
        },
        legend: {
            position: "nw",
            noColumns: 0,
            backgroundColor: "transparent",
            labelBoxBorderColor: null,
            labelFormatter: function(label, series) {
                // just add some space to labes
                return '&nbsp;&nbsp;' + label + ' &nbsp;&nbsp;';
            },
            width: 30,
            height: 2
        },
        series: {
            lines: {
                show: true,
                lineWidth: 1
            },
            points: {
                show:true,
                radius: 2,
                fill: true,
                borderColor: "#fff"
            }
        },
        tooltip: {
            show: true,
            content: "<h6>%s</h6><ul><li>Time: %x</li><li>Value: %y</li></ul>",
            cssClass: "tooltip1",
            relative: true
        },
        colors: ["#26c6da", "#dc3545"],
    };

    executeAjaxRequest({action: 'get', param: 'current'}, function (result) {
        if (result['status'] == 'success') {
            if (result["data"]["current_day"]["status"] == "success") {
                var data = result["data"]["current_day"]["data"]["flot"];
                console.log(data);
                $.plot("#day_rate", [{
                    label: "ColdWater",
                    data: data["coldwater"],
                    points: {fillColor: "#26c6da"}
                },
                    {
                        label: "HotWater",
                        data: data["hotwater"],
                        points: {fillColor: "#dc3545"}
                    }], options);
            } else {
                alert(result["data"]["current_day"]["status"] + ": " + result["data"]["current_day"]["data"]);
            }
        } else {
            alert(result['status'] + ": " + result['data']);
        }
    });

    var d1 = [];
    for (var i = 0; i < 14; i += 0.5) {
        d1.push([i, Math.sin(i)]);
    }

    var d2 = [[0, 3], [4, 8], [8, 5], [9, 13]];
    var d3 = [[0, 12], [7, 12], null, [7, 2.5], [12, 2.5]];


    //$.plot("#month_rate", [ d1, d2, d3 ], options);
    //$.plot("#12_month_rate", [ d1, d2, d3 ], options);
}