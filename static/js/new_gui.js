/**
 * Created by crazytster on 03.08.17.
 */

jQuery(document).ready(function() {
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

    if(window.target != "") {
        var targets = window.target.split('/');
        for (var i = 0; i < targets.length; i++) {
            var target = targets[0];
            if (i > 0) {
                 for (var i1 = 1; i1 < i + 1 ; i1++) {
                     target = target + '/' + targets[i1];
                 }
            }
            $('a[href="#' + target + '"]').click();
        }
    } else {
        show_main_stats();
    }
});

function show_main_stats()
{
    executeAjaxGetRequest({action: 'get', param: 'main_stat'}, function (result) {
        if (result['status'] == 'success') {
            $(".js_water_last_update").text(result['data']['ts']);

            $(".js_cold_curr_value").text(result['data']['coldwater']['current_value']);
            $(".js_cold_curr_day_rate").text(result['data']['coldwater']['day_rate']);
            $(".js_cold_curr_month_rate").text(result['data']['coldwater']['month_rate']);
            $(".js_cold_prev_month_rate").text(result['data']['coldwater']['prev_month_rate']);

            $(".js_hot_curr_value").text(result['data']['hotwater']['current_value']);
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
    executeAjaxGetRequest({action: 'get', param: 'current'}, function (result) {
        if (result['status'] == 'success') {
            $.each(result['data'], function (key, value) {
                if (value['status'] == 'success') {
                    var data = value['data'];
                    switch (key) {
                        case 'current_day':
                            $('.js_day').text(data['date']);
                            generateDayChart(value['data']);
                            break;

                        case 'current_month':
                            $('.js_month').text(moment(data['date']).format('MMMM'));
                            $('.js_day_list').html('');
                            for (var i = value['data']['ts'].length - 1; i > 0 ; i--) {
                                $('.js_day_list').append('<a class="dropdown-item" href="#" onclick="loadDayData(\'' + data['ts'][i] + '\'); return false;">' + moment(data['ts'][i]).format('DD MMMM') + '</a>');
                            }
                            generateMonthChart(value['data']);
                            break;

                        case 'last_12month':
                            $('.js_month_list').html('');
                            for (var i = data['ts'].length - 1; i > 0 ; i--) {
                                $('.js_month_list').append('<a class="dropdown-item" href="#" onclick="loadMonthData(\'' + data['ts'][i] + '\'); return false;"> ' + moment(data['ts'][i]).format("MMMM") + '</a>');
                            }
                            generateLast12MonthChart(value['data']);
                            break;
                    }
                }
            });
        } else {
            alert(result['status'] + ": " + result['data']);
        }
    });
}