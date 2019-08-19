<p class="mint__block__title">{$lang->mint_economy_insights}</p>
<div class="mint__block">
    <canvas id="currencyUnitsChart"></canvas>
</div>

<script src="{$mybb->asset_url}/jscripts/Chart.bundle.min.js"></script>
<script data-input="{$inputJson}">
let input = JSON.parse(document.currentScript.getAttribute('data-input'));
let currencyUnitsChart = new Chart(document.querySelector('#currencyUnitsChart'), {
    type: 'bar',
    data: {
        datasets: [
            {
                data: input.data.currencyUnitsCreated,
                label: input.lang.mint_currency_units_added,
                backgroundColor: '#79eeba50',
            },
            {
                data: input.data.currencyUnitsRemoved,
                label: input.lang.mint_currency_units_removed,
            },
            {
                data: input.data.currencyUnitsTotal,
                type: 'line',
                yAxisID: 'total-axis',
                label: input.lang.mint_currency_units_total,
                fill: false,
                borderColor: '#79eeba',
                lineTension: 0.2,
            },
        ]
    },
    options: {
        title: {
            display: true,
            text: input.lang.mint_currency_units_in_circulation,
        },
        scales: {
            xAxes: [
                {
                    type: 'time',
                    stacked: true,
                    gridLines: {
                        display: false,
                    },
                    time: {
                         unit: 'day',
                    },
                    scaleLabel: {
                        display: true,
                        labelString: input.lang.mint_economy_insights_closing_date,
                    },
                },
            ],
            yAxes: [
                {
                    ticks: {
                        beginAtZero: true,
                        maxTicksLimit: 7,
                    },
                },
                {
                    id: 'total-axis',
                    position: 'right',
                    ticks: {
                        fontStyle: 'bold',
                        maxTicksLimit: 5,
                    },
                },
            ],
        },
        legend: {
            position: 'bottom',
        },
    }
});
</script>
