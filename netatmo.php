<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Netatmo Data Visualization</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Basic styling for layout */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h1 {
            margin: 20px;
        }
        .chart-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 90%;
            max-width: 800px;
        }
        canvas {
            max-width: 100%;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<h1>Netatmo Weather Station Data</h1>

<div class="chart-container">
    <h2>Temperature & Dew Point</h2>
    <canvas id="tempDewChart"></canvas>
</div>

<div class="chart-container">
    <h2>Wind Speed & Direction</h2>
    <canvas id="windChart"></canvas>
</div>

<div class="chart-container">
    <h2>Precipitation & Accumulated Precipitation</h2>
    <canvas id="precipitationChart"></canvas>
</div>

<div class="chart-container">
    <h2>Humidity</h2>
    <canvas id="humidityChart"></canvas>
</div>

<div class="chart-container">
    <h2>Pressure</h2>
    <canvas id="pressureChart"></canvas>
</div>

<script>
// Load data from JSON file
fetch('netatmo_data.json')
    .then(response => response.json())
    .then(data => {
        // Parse the JSON data to get each parameter and timestamp
        const timestamps = data.map(entry => new Date(entry.timestamp * 1000).toLocaleString());
        const temperatures = data.map(entry => entry.temperature);
        const dewPoints = data.map(entry => entry.dew_point);
        const windSpeeds = data.map(entry => entry.wind_speed);
        const windDirections = data.map(entry => entry.wind_direction);
        const precipitations = data.map(entry => entry.precipitation);
        const accumulatedPrecipitations = data.map(entry => entry.accumulated_precipitation);
        const humidities = data.map(entry => entry.humidity);
        const pressures = data.map(entry => entry.pressure);

        // Function to create a combined chart with custom styling
        function createCombinedChart(ctx, label1, data1, label2, data2, yLabel1, yLabel2) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timestamps,
                    datasets: [
                        {
                            label: label1,
                            data: data1,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y1'
                        },
                        {
                            label: label2,
                            data: data2,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y2'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: 'category',
                            title: { display: true, text: 'Timestamp' },
                            ticks: { maxTicksLimit: 10 }
                        },
                        y1: {
                            type: 'linear',
                            position: 'left',
                            title: { display: true, text: yLabel1 }
                        },
                        y2: {
                            type: 'linear',
                            position: 'right',
                            title: { display: true, text: yLabel2 },
                            grid: { drawOnChartArea: false }
                        }
                    },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: { mode: 'index', intersect: false }
                    }
                }
            });
        }

        // Function to create a single-parameter chart with custom styling
        function createSingleChart(ctx, label, data, yLabel) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timestamps,
                    datasets: [{
                        label: label,
                        data: data,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: 'category',
                            title: { display: true, text: 'Timestamp' },
                            ticks: { maxTicksLimit: 10 }
                        },
                        y: {
                            title: { display: true, text: yLabel }
                        }
                    },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: { mode: 'index', intersect: false }
                    }
                }
            });
        }

        // Temperature and Dew Point Chart
        createCombinedChart(
            document.getElementById('tempDewChart').getContext('2d'),
            'Temperature (°C)', temperatures,
            'Dew Point (°C)', dewPoints,
            'Temperature (°C)', 'Dew Point (°C)'
        );

        // Wind Speed and Wind Direction Chart
        createCombinedChart(
            document.getElementById('windChart').getContext('2d'),
            'Wind Speed (km/h)', windSpeeds,
            'Wind Direction (°)', windDirections,
            'Speed (km/h)', 'Direction (°)'
        );

        // Precipitation and Accumulated Precipitation Chart
        createCombinedChart(
            document.getElementById('precipitationChart').getContext('2d'),
            'Precipitation (mm)', precipitations,
            'Accumulated Precipitation (mm)', accumulatedPrecipitations,
            'Precipitation (mm)', 'Accumulated Precipitation (mm)'
        );

        // Humidity Chart
        createSingleChart(
            document.getElementById('humidityChart').getContext('2d'),
            'Humidity (%)', humidities,
            'Humidity (%)'
        );

        // Pressure Chart
        createSingleChart(
            document.getElementById('pressureChart').getContext('2d'),
            'Pressure (hPa)', pressures,
            'Pressure (hPa)'
        );
    })
    .catch(error => console.error('Error loading data:', error));
</script>

</body>
</html>
