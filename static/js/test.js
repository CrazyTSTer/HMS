var coldwater, hotwater;

jQuery(document).ready(function() {
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
            coldwater = result['data']['coldwater'];
            hotwater = result['data']['hotwater'];
        } else {
            $('.current_day').html(result['status'] + '<br>' + result['data']);
        }
    });

    Highcharts.setOptions({
        global: {
            useUTC: false
        }
    });

    Highcharts.chart('current_day', {
        chart: {
            type: 'spline',
            zoomType: 'x'
        },
        title: {
            text: 'Потребление холодной и горячей воды за текущий день'
        },
        subtitle: {
            text: '%дата%'
        },
        xAxis: {
            type: 'datetime',
            dateTimeLabelFormats: { // don't display the dummy year
                hour: '%H:%M:%S'
            },
            title: {
                text: 'Время (ЧЧ:ММ)'
            }
        },
        yAxis: {
            title: {
                text: 'Литры (л)'
            },
            min: 0,
        },
        tooltip: {
            headerFormat: '<b>{series.name}</b><br>',
            pointFormat: '{point.x:%H:%M:%S}: {point.y:2f} л'
        },

        plotOptions: {
            spline: {
                marker: {
                    enabled: true,
                }
            }
        },

        series: [{
            name: 'Холодная вода',
            color: 'Blue',
            data: coldwater
        }, {
            name: 'Горячая вода',
            color: 'Red',
            data: hotwater
        }]
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
