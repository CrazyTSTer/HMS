/**
 * Created by crazytster on 03.08.17.
 */
jQuery(document).ready(function() {
    $(".js_set_focus").focus();
    var chart_common = {
       textVertPosition: 0.8,
       waveAnimateTime: 5000,
       waveHeight: 0.15,
       waveAnimate: true,
       waveOffset: 0.25,
       valueCountUp: true,
       displayPercent: false,
       maxValue: 1000,
       textSize: 0.5,
       /*width: $(window).width() < 768 ? "150" : "250",
       height: $(window).width() < 768 ? "150" : "250",*/
    };

    if ($(window).width() < 768) {
        $("#coldwater").attr('viewBox', '0 0 100 100');
        $("#hotwater").attr('viewBox', '0 0 100 100');
    } else {
        $("#coldwater").attr('viewBox', '0 0 150 150');
        $("#hotwater").attr('viewBox', '0 0 150 150');
    }

    /*d3.select("#coldwater").call(d3.liquidfillgauge, 123, chart_common);
    d3.select("#hotwater").call(
        d3.liquidfillgauge,
        567,
        $.extend(
            {},
            chart_common,
            {
                circleColor: "#d73232",
                waveColor: "#d73232",
                textColor: "#9e1f1f",
                waveTextColor: "#e37272"
            }
        )
    );*/

    executeAjaxRequest({action: 'get', param: 'current_val'}, function (result) {
        var cw_cube = result['data']['coldwater']['cube'];
        var cw_liter = result['data']['coldwater']['liter'];
        var hw_cube = result['data']['hotwater']['cube'];
        var hw_liter = result['data']['hotwater']['liter'];

        d3.select("#coldwater").call(d3.liquidfillgauge, cw_liter, chart_common, cw_cube + ',' + cw_liter);
        d3.select("#hotwater").call(
            d3.liquidfillgauge,
            hw_liter,
            $.extend(
                {},
                chart_common,
                {
                    circleColor: "#d73232",
                    waveColor: "#d73232",
                    textColor: "#9e1f1f",
                    waveTextColor: "#e37272",
                }
            ),
            hw_cube + ',' + hw_liter
        );
    });

    /*setInterval(function() {
        executeAjaxRequest({action: 'get', param: 'current_val'}, function (result) {
            var cw_cube = result['data']['coldwater']['cube'];
            var cw_liter = result['data']['coldwater']['liter'];
            var hw_cube = result['data']['hotwater']['cube'];
            var hw_liter = result['data']['hotwater']['liter'];
            d3.select("#coldwater").on("valueChanged")(cw_liter, cw_cube + ',' + cw_liter);
            d3.select("#hotwater").on("valueChanged")(hw_liter, hw_cube + ',' + hw_liter);
        });
    }, 2000);*/
});

/*$(document).on('click','.navbar-collapse.in',function(e) {
    if( $(e.target).is('a') && $(e.target).attr('class') != 'dropdown-toggle' ) {
        $(this).collapse('hide');
    }
});*/