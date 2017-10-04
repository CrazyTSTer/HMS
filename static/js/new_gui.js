/**
 * Created by crazytster on 03.08.17.
 */

/*$(document).on('click','.navbar-collapse.in',function(e) {
 if( $(e.target).is('a') && $(e.target).attr('class') != 'dropdown-toggle' ) {
 $(this).collapse('hide');
 }
 });*/
jQuery(document).ready(function() {
    $(".js_set_focus").focus();
    show_main_stats();
});

function show_main_stats()
{
    $('.js_water_graphs').hide();
    $('.js_main_stats').show();
    executeAjaxRequest({action: 'get', param: 'current_val'}, function (result) {
        if (result['status'] == 'success') {
            $(".js_last_insert").text(result['data']['ts']);

            $(".js_cold_current_value").text(result['data']['coldwater']['cube'] + ',' + result['data']['coldwater']['liter']);
            $(".js_cold_today_rate").text(result['data']['coldwater']['day_rate']);
            $(".js_cold_month_rate").text(result['data']['coldwater']['month_rate']);
            $(".js_cold_prev_month_rate").text(result['data']['coldwater']['prev_month_rate']);

            $(".js_hot_current_value").text(result['data']['hotwater']['cube'] + ',' + result['data']['hotwater']['liter']);
            $(".js_hot_today_rate").text(result['data']['hotwater']['day_rate']);
            $(".js_hot_month_rate").text(result['data']['hotwater']['month_rate']);
            $(".js_hot_prev_month_rate").text(result['data']['hotwater']['prev_month_rate']);

            $.each(result['data'], function (key, value) {
                if (key == 'ts') return;
                generateGauge(key, value);
            });
        } else {
            alert(result['status'] + ": " + result['data']);
        }
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
            alert(result['status'] + ": " + result['data']);
        }
    });
}