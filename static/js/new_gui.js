/**
 * Created by crazytster on 03.08.17.
 */

jQuery(document).ready(function() {
    $('#sidebar a').on('click', function (e) {
        $('#sidebar').find('.active').removeClass('active');
        if ($(this).attr('data-toggle') != 'collapse') {
            $(this).addClass('active');
        } else {
            if (!$(this).hasClass('a_sub-active')) {
                $(this).addClass('a_sub-active');
            } else {
                $(this).removeClass('a_sub-active');
            }
            if ($(this).siblings('ul').hasClass('sub-active')) {
                var el = $(this).siblings('ul').removeClass('sub-active').find('.sub-active').siblings('a').get().reverse();
                $(el).click();
            } else {
                $(this).siblings('ul').addClass('sub-active');
            }
        }
    });

    $('.navbar-toggler').button().on('click', function() {
        var el = $("#sidebar a[data-toggle*='collapse'][aria-expanded*='true']").get().reverse();
        $(el).click();
    });
});