/**
 * Created by crazytster on 03.08.17.
 */
jQuery(document).ready(function() {
   $(".js_set_focus").focus();
    d3.select("#fillgauge1").call(d3.liquidfillgauge, 50.8, {
        textVertPosition: 0.8,
        waveAnimateTime: 5000,
        waveHeight: 0.15,
        waveAnimate: true,
        waveOffset: 0.25,
        valueCountUp: false,
        displayPercent: false,
    }, 'Custom');

    d3.select("#fillgauge2").call(d3.liquidfillgauge, 50.8, {
        circleColor: "#d73232",
        waveColor: "#d73232",
        textColor: "#9e1f1f", // The color of the value text when the wave does not overlap it.
        waveTextColor: "#e37272",
        textVertPosition: 0.8,
        waveAnimateTime: 5000,
        waveHeight: 0.15,
        waveAnimate: true,
        waveOffset: 0.25,
        valueCountUp: false,
        displayPercent: false,
    }, 'Custom');

    setInterval(function() {
        d3.select("#fillgauge1").on("valueChanged")(Math.floor(Math.random() * 100), Math.floor(Math.random() * 1000));
        d3.select("#fillgauge2").on("valueChanged")(Math.floor(Math.random() * 100), Math.floor(Math.random() * 1000));
    }, 2000);
});

/*$(document).on('click','.navbar-collapse.in',function(e) {
    if( $(e.target).is('a') && $(e.target).attr('class') != 'dropdown-toggle' ) {
        $(this).collapse('hide');
    }
});*/