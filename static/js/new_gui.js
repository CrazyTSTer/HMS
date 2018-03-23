/**
 * Created by crazytster on 03.08.17.
 */

jQuery(document).ready(function() {
    /*$(".js_set_focus").focus();
    show_main_stats('');*/
    $('#sidebar a').on('click', function (e) {
        $('#sidebar').find('.active').removeClass('active');
        if ($(this).attr('data-toggle') != 'collapse') {
            $(this).addClass('active');
        } else {
            if ($(this).siblings('ul').hasClass('sub-active')) {
                var sibl = $(this).siblings('ul');
                $(sibl).removeClass('sub-active');
                var fnd = $(sibl).find('.sub-active');
                var a_sibl = $(fnd).siblings('a').get().reverse();
                $(a_sibl).click();
            } else {
                $(this).siblings('ul').addClass('sub-active');
            }
        }
    });
});