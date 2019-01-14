/**
 * Created by crazytster on 03.08.17.
 */
const COMMON_STAT_PAGE          = 'CommonStatPage';
const WATER_STAT_PAGE           = 'WaterStatPage';
const PGU_SETTINGS_PAGE         = 'PGUSettingsPage';
const WATER_SETTINGS_PAGE       = 'WaterSettingsPage';
const ELECTRICITY_SETTINGS_PAGE = 'ElectricitySettingsPage';

jQuery(document).ready(function() {
    $('#sidebar a').on('click', function (e) {
        if ($(this).attr('data-toggle') != 'collapse') {
            $('#sidebar').find('.active').removeClass('active');
            $(this).addClass('active');
            if ($('#sidebar').hasClass('show')) {
                $('.navbar-toggler').button().click()
                $('body,html').animate({scrollTop: 0}, 400);
            }
        } else {
            if ($(this).hasClass('a_sub-active')) {
                $(this).removeClass('a_sub-active');
            } else {
                $(this).addClass('a_sub-active');
            }
            if ($(this).siblings('ul').hasClass('sub-active')) {
                //$(this).removeClass('a_sub-active');
                var el = $(this).siblings('ul').removeClass('sub-active').find('.sub-active').siblings('a').get().reverse();
                $(el).click();
            } else {
                //$(this).addClass('a_sub-active');
                $(this).siblings('ul').addClass('sub-active');
            }
        }
    });

    $('.navbar-toggler').button().on('click', function() {
        var el = $("#sidebar .a_sub-active").get().reverse();
        $(el).click();
    });

    $('a[href*="' + target + '"]').addClass('active').parents("ul:not(#sidebar)").addClass('show sub-active').siblings('a').addClass('a_sub-active').attr('aria-expanded', true);

    displayTargetContent(target);
});

function loadPage(el)
{
    event.preventDefault();
    var href = $(el).attr('href');
    var url = window.location.href;
    var regexp = /index.php(.*)/;
    var target = href.match(/target=(.*)/)[1];

    if (regexp.test(url)) {
        url = url.replace(regexp, href);
    } else {
        url += href;
    }

    history.replaceState('', '', url);

    $(".js_content").html('').load(href);
    displayTargetContent(target);
}

function displayTargetContent(target)
{
    switch(target) {
        case COMMON_STAT_PAGE:
        default:
            show_main_stats();
            break;
        case WATER_STAT_PAGE:
            show_graph_rate();
            break;
        case WATER_SETTINGS_PAGE:
        case ELECTRICITY_SETTINGS_PAGE:
            getDataFromConfig();
            break;
    }
}

function showModalAlert(status, message)
{
    $('.js_modal-title').html("<strong>Request " + status  + "</strong>");
    $('.js_modal-body').html("<strong>Message: </strong>" + message);
    $("#modalAlert").modal();
}