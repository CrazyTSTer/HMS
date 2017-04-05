var coldwater, hotwater;
var cd_chart, cm_chart;
var last_timestamp;

jQuery(document).ready(function() {
    setChartGlobalParams();
    currentDayChart();
    currentMonthChart();


    executeAjaxRequest({action: 'get', param: 'last'}, function (result) {
        if (result['status'] == 'success') {
            $('.timestamp').html(result['data']['ts']);
            $('.coldwater').html(result['data']['coldwater']);
            $('.hotwater').html(result['data']['hotwater']);
        } else {
            $('.current_values').html(result['status'] + '<br>' + result['data']);
        }
    });

    executeAjaxRequest({action: 'get', param: 'current_day'}, function (result) {
        if (result['status'] == 'success') {
            cd_chart.series[0].setData(result['data']['coldwater']);
            cd_chart.series[1].setData(result['data']['hotwater']);
            cd_chart.subtitle.textStr = result['data']['current_date'];
            last_timestamp = result['data']['last_timestamp'];
        } else {
            $('.current_day').html(result['status'] + '<br>' + result['data']);
        }
    });

    executeAjaxRequest({action: 'get', param: 'current_month'}, function (result) {
        if (result['status'] == 'success') {
            cm_chart.series[0].setData(result['data']['coldwater']);
            cm_chart.series[1].setData(result['data']['hotwater']);
            cm_chart.xAxis[0].setCategories(result['data']['ts']);
            var col_count = result['data']['ts'].length - 1;
            cm_chart.series[0].data[col_count].color = "blue";
            cm_chart.series[1].data[col_count].color = "red";
            cm_chart.legend.update();
        } else {
            $('.current_month').html(result['status'] + '<br>' + result['data']);
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
