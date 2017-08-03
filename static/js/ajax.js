/**
 * Created by CrazyTSTer on 03.08.17.
 */
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