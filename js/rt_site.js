/**
 * Created by CrazyTSTer on 20.10.14.
 */
//Global Varibles

const GET                = 'get';
const GET_WELCOME        = 'welcome';
const GET_MENU_ITEM_DESC = 'menu_item_desc';
const GET_MENU_TREE      = 'menu_tree';

const MYSQL_EMPTY_SELECTION = 'Selection is empty';

const AJAX_RESPONSE_SUCCESS = 'success';
const AJAX_RESPONSE_FAIL = 'fail';

$(function() {
    $(".js_content").mCustomScrollbar({
        alwaysShowScrollbar: 2,
        //advanced:{
         //   updateOnContentResize: true    // <- the solution
        //}
    });

    //This is multi request to get Menu and Welcome page at one time
    var params = {
        action: GET,
        from:   {first:GET_MENU_TREE, second:GET_WELCOME},
        params: {first:'not_null', entry: 'main'}
    };
    executeAjaxQuery(params, parseAjaxRequest);
    return false;
});

function showMenuTree()
{
    var params = {
        action: GET,
        from:   GET_MENU_TREE,
        params: 'not_null'
    };
    executeAjaxQuery(params, parseAjaxRequest);
    return false;
}


function showItemDesc(entry, element_id)
{
    var params = {
        action: GET,
        from:   GET_MENU_ITEM_DESC,
        params: {entry: entry, element_id: element_id}
    };
    executeAjaxQuery(params, parseAjaxRequest);
    return false;
}

function showWelcomeForEntry(entry)
{
    var params = {
        action : GET,
        from: GET_WELCOME,
        params : {entry : entry}
    };
    executeAjaxQuery(params, parseAjaxRequest);
    return false;
}

function executeAjaxQuery(params, callback)
{
    var _entry = params['params']['entry'];

    $.ajax({
        url: 'php/RequestController.php',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function (return_data) {
            if (return_data.status == AJAX_RESPONSE_FAIL) {
                alert(return_data.data);
            } else if (return_data.status == AJAX_RESPONSE_SUCCESS) {
                callback(return_data.data, _entry);
            } else {
                alert('Unexpected response status!');
            }
        },
        error: function () {
            alert('Ajax request failed!');
        }
    });
}

function parseAjaxRequest(data)
{
    for (var key in data) {
        /*if (data[key] == MYSQL_EMPTY_SELECTION) {
         clearContentAreas();
         continue;
         }*/
        switch(key) {
            case GET_WELCOME:
                showWelcomeContent(data[key]);
                break;
            case GET_MENU_TREE:
                createAccordionMenu(data[key]);
                break;
            case GET_MENU_ITEM_DESC:
                showMenuItemDesc(data[key]);
                break;
            default:
                alert('Unexpected data in Ajax response');
                break;
        }
    }
}

function showWelcomeContent(content)
{
    $(".js_content_data").html('');

    if (content && content != MYSQL_EMPTY_SELECTION ) {
        $(".js_content_data").html(content);
    }
}

function showMenuItemDesc(content)
{
    $(".js_content_data").html('');

    if (content && content != MYSQL_EMPTY_SELECTION ) {
        $(".js_content_data").html(content.long_desc);
    }
}

function createAccordionMenu(content)
{
    $(".js_main_menu").html('');

    if (content && content != MYSQL_EMPTY_SELECTION ) {
        $(".js_main_menu").html(content);
    }

    $(".js_main_menu").mCustomScrollbar({
        alwaysShowScrollbar: 2
    });


    $('.item').each(function() {
        $(this).click(function() {
            var parent = $(this).parent();
            collapseItems(this);

            if ($(parent).hasClass('js_item_container')) {
                var sub_menu_element = $(parent).find('.sub_menu');
                collapseItems(parent);

                if ($(sub_menu_element[0]).css('display') == 'none') {
                    $(sub_menu_element[0]).slideDown(200, function() {
                        $(this).addClass('expanded');
                    });
                }
            }
            $(this).addClass('selected');
        });
    });
}

function collapseItems(element) {
    $(element).siblings().andSelf().each(function() {
        if ($(this).hasClass('js_item_container')) {
            var sub_menu_element = $(this).find('.sub_menu');
            var selected = $(this).find('.selected');

            $(selected).each(function() {
                $(this).removeClass('selected');
            });
            $(sub_menu_element).each(function () {
                $(this).slideUp(200);
                $(this).removeClass('expanded');
            });
        }
        $(this).removeClass('selected');
    });
}

function showPage(page)
{
    if (page) {
        $('.js_content_data').load('html/' + page + '.html');
    }
}
