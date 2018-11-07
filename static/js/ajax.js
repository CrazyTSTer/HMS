/**
 * Created by CrazyTSTer on 03.08.17.
 */
//-----------------AJAX REQUEST--------------------------------//
function executeAjaxGetRequest(params, success_callback, error_callback)
{
    error_callback = error_callback ? error_callback : function(jqXHR, status, message) {
        ajax_error_callback(jqXHR, status, message);
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

function executeAjaxPostRequest(params, success_callback, error_callback)
{
    error_callback = error_callback ? error_callback : function(jqXHR, status, message) {
        ajax_error_callback(jqXHR, status, message);
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

function ajax_error_callback(jqXHR, status, message)
{
    $('.js_modal-title').html("<strong>Can't complete request!</strong>");
    $('.js_modal-body').html("<strong>Status:</strong> " + status + "<br><strong>Message: </strong>" + message);
    $("#modalAlert").modal();
}