jQuery(document).ready(function () {
    /*Highcharts.setOptions({
        global: {
            useUTC: false
        }
    });*/
    /*Highcharts.chart('current_day', {

        chart: {
            type: 'spline'
        },
        title: {
            text: 'Cold and Hot water stat'
        },
        xAxis: {
            type: 'datetime'

        },
        yAxis: {
            title: {
                text: 'Liters'
            }
        },
        plotOptions: {
            line: {
                dataLabels: {
                    enabled: true
                },
                enableMouseTracking: false
            }
        },
        series: [{
            name: 'ColdWater',
            color: '#0000ff',
            data: [[1490669721000, 0],
                [1490682682000, 10],
                [1490684542000, 17],
                [1490684602000, 20],
                [1490684662000, 20],
                [1490684722000, 27],
                [1490684782000, 30],
                [1490685382000, 37],
                [1490685442000, 37],
                [1490685502000, 40],
                [1490685563000, 40]]
        }, {
            name: 'HotWater',
            color: '#ff0000',
            data: [[1490669721000, 0],
                [1490682682000, 0],
                [1490684542000, 0],
                [1490684602000, 10],
                [1490684662000, 17],
                [1490684722000, 20],
                [1490684782000, 30],
                [1490685382000, 30],
                [1490685442000, 37],
                [1490685502000, 37],
                [1490685563000, 40]]
        }]
    });*/
    var params = {
        action: get,
        param: 'last',
    };
    
    executeAjaxRequest(params, function (result) {
        if (result['status'] == 'success') {

        } else {

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
