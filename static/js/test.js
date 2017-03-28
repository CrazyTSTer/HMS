jQuery(document).ready(function () {
    Highcharts.setOptions({
        global: {
            useUTC: false
        }
    });
    Highcharts.chart('current_day', {

        chart: {
            type: 'spline'
        },
        title: {
            text: 'Cold and Hot water stat'
        },
        xAxis: {
            type: 'datetime'

        },
        yAxis: {
            title: {
                text: 'Liters'
            }
        },
        plotOptions: {
            line: {
                dataLabels: {
                    enabled: true
                },
                enableMouseTracking: false
            }
        },
        series: [{
            name: 'ColdWater',
            color: '#0000ff',
            data: [[1490669721000, 0],
                [1490682682000, 10],
                [1490684542000, 17],
                [1490684602000, 20],
                [1490684662000, 20],
                [1490684722000, 27],
                [1490684782000, 30],
                [1490685382000, 37],
                [1490685442000, 37],
                [1490685502000, 40],
                [1490685563000, 40]]
        }, {
            name: 'HotWater',
            color: '#ff0000',
            data: [[1490669721000, 0],
                [1490682682000, 0],
                [1490684542000, 0],
                [1490684602000, 10],
                [1490684662000, 17],
                [1490684722000, 20],
                [1490684782000, 30],
                [1490685382000, 30],
                [1490685442000, 37],
                [1490685502000, 37],
                [1490685563000, 40]]
        }]
    });
});