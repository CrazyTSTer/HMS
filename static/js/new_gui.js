/**
 * Created by crazytster on 03.08.17.
 */

jQuery(document).ready(function() {
    $('#sidebar a').on('click', function (e) {
        if ($(this).attr('data-toggle') != 'collapse') {
            $('#sidebar').find('.active').removeClass('active');
            $(this).addClass('active');
            if ($(this)[0].hasAttribute("data-target")) {
                $('#content').find('.active').removeClass('active');
                $($(this).attr('data-target')).addClass('active');
            }
            if ($('#sidebar').hasClass('show')) {
                $('.navbar-toggler').button().click()
                $('body,html').animate({scrollTop: 0}, 400);
            }
        } else {
            if (!$(this).hasClass('a_sub-active')) {
                $(this).addClass('a_sub-active');
            } else {
                $(this).removeClass('a_sub-active');
            }
            if ($(this).siblings('ul').hasClass('sub-active')) {
                //$(this).removeClass('a_sub-active');
                var el = $(this).siblings('ul').removeClass('sub-active').find('.sub-active').siblings('a').get().reverse();
                $(el).click();
            } else {
                $(this).siblings('ul').addClass('sub-active');
                //$(this).addClass('a_sub-active');
            }
        }
    });

    $('.navbar-toggler').button().on('click', function() {
        var el = $("#sidebar a[data-toggle*='collapse'][aria-expanded*='true']").get().reverse();
        $(el).click();
    });
});