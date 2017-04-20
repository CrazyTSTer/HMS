var days, months;
jQuery(document).ready(function() {
    setChartGlobalParams();
    currentDayChart();
    currentMonthChart();
    last12MonthChart();

    executeAjaxRequest({action: 'get', param: 'current'}, function (result) {
        if (result['status'] == 'success') {
            if (result['data']['current_values']['status'] = 'success') {
                $('.timestamp').html(result['data']['current_values']['data']['ts']);
                $('.coldwater').html(result['data']['current_values']['data']['coldwater']);
                $('.hotwater').html(result['data']['current_values']['data']['hotwater']);
            } else {
                $('.current_values').html(result['data']['current_values']['status'] + '<br>' + result['data']['current_values']['data']);
            }

            updateChart(cd_chart, result['data']['current_day']);
            updateChart(cm_chart, result['data']['current_month'], true);
            updateChart(last12Month_chart, result['data']['last_12month'], true);
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
