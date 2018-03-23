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
                var sibl = $(this).siblings('ul').removeClass('sub-active');
                var fnd = $(sibl).find('.sub-active').get().reverse();
                $(fnd).removeClass('sub-active').siblings('a').click();
            } else {
                $(this).siblings('ul').addClass('sub-active');
            }
        }
    });
});