$(function () {
    var ticksStyle = {
        fontColor: '#52606d',
        fontStyle: 'bold',
        fontSize: 11
    }

    var mode = 'index';
    var intersect = false;

    var $salesChart = $('#sales-chart');

    if (!$salesChart.length || typeof Chart === 'undefined') {
        return;
    }

    var salesChartCanvas = $salesChart.get(0).getContext('2d');
    var dataArray = function (key) {
        var value = $salesChart.data(key);

        if (typeof value === 'string') {
            try {
                value = JSON.parse(value);
            } catch (error) {
                value = [];
            }
        }

        return Array.isArray(value) ? value.map(function (item) {
            return key === 'labels' ? item : Number(item) || 0;
        }) : [];
    };

    // eslint-disable-next-line no-unused-vars
    new Chart(salesChartCanvas, {
        type: 'bar',
        data: {
            labels: dataArray('labels'),
            datasets: [
                {
                    label: 'RPCPPE / PAR',
                    backgroundColor: '#d99a00',
                    borderColor: '#c78d00',
                    borderWidth: 1,
                    data: dataArray('rpcppe'),
                },
                {
                    label: 'RPCSEP / ICS',
                    backgroundColor: '#137a4b',
                    borderColor: '#0f653e',
                    borderWidth: 1,
                    data: dataArray('rpcsep'),
                },
                {
                    label: 'Unserviceable',
                    backgroundColor: '#b24a3b',
                    borderColor: '#963d31',
                    borderWidth: 1,
                    data: dataArray('unserviceable'),
                },
            ]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            tooltips: {
                mode: mode,
                intersect: intersect,
                backgroundColor: '#1f2d3d',
                titleFontStyle: 'bold',
                callbacks: {
                    title: function (tooltipItem, data) {
                        return data.labels[tooltipItem[0].index];
                    },
                    label: function (tooltipItem, data) {
                        return data.datasets[tooltipItem.datasetIndex].label + ': ' + tooltipItem.value;
                    }
                }
            },
            hover: {
                mode: mode,
                intersect: intersect
            },
            legend: {
                display: false
            },
            scales: {
                yAxes: [{
                    display: true,
                    gridLines: {
                        color: 'rgba(82, 96, 109, .12)',
                        zeroLineColor: 'rgba(82, 96, 109, .18)'
                    },
                    ticks: $.extend({
                        beginAtZero: true,
                        precision: 0
                    }, ticksStyle)
                }],
                xAxes: [{
                    display: true,
                    gridLines: {
                        display: false
                    },
                    ticks: $.extend({
                        autoSkip: false,
                        maxRotation: 0
                    }, ticksStyle)
                }]
            }
        }
    });
});
