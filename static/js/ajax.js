/**
 * Created by CrazyTSTer on 03.08.17.
 */
//-----------------AJAX REQUEST--------------------------------//
function executeAjaxGetRequest(params, success_callback, error_callback) {
    error_callback = error_callback ? error_callback : function (jqXHR, status, message) {
        /*$('.alert_js').addClass('alert-danger');
         $('.modal-text_js').html("<strong>Can't complete request!</strong><br> Status: " + status + "<br> Message: " + message);
         $("#modalAlert").modal();*/
        alert("AJAX GET REQUEST FAILED");
    };

    $.ajax({
        url: 'index.php',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: success_callback,
        error: error_callback
    });
}

function executeAjaxPostRequest(params, success_callback, error_callback) {
    error_callback = error_callback ? error_callback : function (jqXHR, status, message) {
        /*$('.alert_js').addClass('alert-danger');
         $('.modal-text_js').html("<strong>Can't complete request!</strong><br> Status: " + status + "<br> Message: " + message);
         $("#modalAlert").modal();*/
        alert("AJAX POST REQUEST FAILED");
    };

    $.ajax({
        url: 'index.php',
        type: 'POST',
        data: params,
        dataType: 'json',
        success: success_callback,
        error: error_callback
    });
}