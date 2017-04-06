var coldwater, hotwater;
var cd_chart, cm_chart;

jQuery(document).ready(function() {
    setChartGlobalParams();
    currentDayChart();
    currentMonthChart();


    executeAjaxRequest({action: 'get', param: 'last'}, function (result) {
        if (result['status'] == 'success') {
            if (result['data']['current_values']['status'] = 'success') {
                $('.timestamp').html(result['data']['current_values']['data']['ts']);
                $('.coldwater').html(result['data']['current_values']['data']['coldwater']);
                $('.hotwater').html(result['data']['current_values']['data']['hotwater']);
            } else {
                $('.current_values').html(result['data']['current_values']['status'] + '<br>' + result['data']['current_values']['data']);
            }

            if (result['data']['current_day']['status'] = 'success') {
                cd_chart.setTitle(null, {text: result['data']['current_date']});
                cd_chart.series[0].setData(result['data']['current_day']['data']['coldwater']);
                cd_chart.series[1].setData(result['data']['current_day']['data']['hotwater']);
                cd_chart.redraw();
            } else {
                $('.current_day').html(result['data']['current_day']['status'] + '<br>' + result['data']['current_day']['data']);
            }

            if (result['data']['current_month']['status'] = 'success') {
                cm_chart.series[0].setData(result['data']['current_month']['data']['coldwater']);
                cm_chart.series[1].setData(result['data']['current_month']['data']['hotwater']);
                cm_chart.xAxis[0].setCategories(result['data']['current_month']['data']['ts']);
                var col_count = result['data']['current_month']['data']['ts'].length - 1;
                cm_chart.series[0].data[col_count].color = "blue";
                cm_chart.series[1].data[col_count].color = "red";
                cm_chart.legend.update();
                cm_chart.redraw();
            } else {
                $('.current_month').html(result['data']['current_month']['status'] + '<br>' + result['data']['current_month']['data']);
            }
        } else {
            alert('SMTH GOES WRONG!');
        }
    });
});


//-----------------AJAX REQUEST--------------------------------//
function executeAjaxRequest(params, success_callback, error_callback) {
    error_callback = error_callback ? error_callback : function (jqXHR, status, message) {
        /*$('.alert_js').addClass('alert-danger');
        $('.modal-text_js').html("<strong>Can't complete request!</strong><br> Status: " + status + "<br> Message: " + message);
        $("#modalAlert").modal();*/
        alert("AJAX REQUEST FAILED");
    };

    $.ajax({
        url: 'WaterStat.php',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: success_callback,
        error: error_callback
    });
}
