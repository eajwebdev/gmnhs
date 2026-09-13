$(function () {
    var $pieChart = $('#pieChart');

    if (!$pieChart.length || typeof Chart === 'undefined') {
        return;
    }

    var pieChartCanvas = $pieChart.get(0).getContext('2d');
    var rpcppeCount = Number($pieChart.data('rpcppe')) || 0;
    var rpcsepCount = Number($pieChart.data('rpcsep')) || 0;
    var unserviceableCount = Number($pieChart.data('unserviceable')) || 0;

    var pieData = {
        labels: [
            'RPCPPE / PAR',
            'RPCSEP / ICS',
            'Unserviceable',
        ],
        datasets: [
            {
                data: [rpcppeCount, rpcsepCount, unserviceableCount],
                backgroundColor: ['#d99a00', '#137a4b', '#b24a3b'],
                borderColor: '#ffffff',
                borderWidth: 4,
                hoverBorderColor: '#ffffff'
            }
        ]
    }
    var pieOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: {
            display: false
        },
        cutoutPercentage: 64,
        tooltips: {
            backgroundColor: '#1f2d3d',
            titleFontStyle: 'bold',
            callbacks: {
                label: function (tooltipItem, data) {
                    var dataset = data.datasets[tooltipItem.datasetIndex];
                    var value = dataset.data[tooltipItem.index] || 0;
                    var total = dataset.data.reduce(function (sum, item) {
                        return sum + Number(item || 0);
                    }, 0);
                    var percentage = total ? Math.round((value / total) * 100) : 0;

                    return data.labels[tooltipItem.index] + ': ' + value + ' (' + percentage + '%)';
                }
            }
        }
    }
    new Chart(pieChartCanvas, {
        type: 'doughnut',
        data: pieData,
        options: pieOptions
    });
});
