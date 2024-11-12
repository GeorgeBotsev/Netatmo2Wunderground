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
    <h2>Wind Speed & Gust</h2>
    <canvas id="windSpeedChart"></canvas>
</div>

<div class="chart-container">
    <h2>Wind Direction</h2>
    <canvas id="windDirectionChart"></canvas>
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
        const windGusts = data.map(entry => entry.wind_gust);
        const windDirections = data.map(entry => entry.wind_direction);
        const precipitations = data.map(entry => entry.precipitation);
        const accumulatedPrecipitations = data.map(entry => entry.accumulated_precipitation);
        const humidities = data.map(entry => entry.humidity);
        const pressures = data.map(entry => entry.pressure);

        // Function to create a combined chart with custom styling
        function createCombinedChart(ctx, datasets, yLabels) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timestamps,
                    datasets: datasets
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
                            title: { display: true, text: yLabels[0] }
                        },
                        y2: {
                            type: 'linear',
                            position: 'right',
                            title: { display: true, text: yLabels[1] },
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
            [
                {
                    label: 'Temperature (°C)',
                    data: temperatures,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1'
                },
                {
                    label: 'Dew Point (°C)',
                    data: dewPoints,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y2'
                }
            ],
            ['Temperature (°C)', 'Dew Point (°C)']
        );

        // Wind Speed and Gust Chart
        createCombinedChart(
            document.getElementById('windSpeedChart').getContext('2d'),
            [
                {
                    label: 'Wind Speed (km/h)',
                    data: windSpeeds,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1'
                },
                {
                    label: 'Wind Gust (km/h)',
                    data: windGusts,
                    borderColor: 'rgba(255, 159, 64, 1)',
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ],
            ['Wind Speed (km/h)', 'Wind Gust (km/h)']
        );

        // Define the custom y-axis labels for degrees with corresponding cardinal directions
        const directionLabels = [
            { label: 'N', degree: 0 },
            { label: 'NE', degree: 45 },
            { label: 'E', degree: 90 },
            { label: 'SE', degree: 135 },
            { label: 'S', degree: 180 },
            { label: 'SW', degree: 225 },
            { label: 'W', degree: 270 },
            { label: 'NW', degree: 315 },
            { label: 'N', degree: 360 }
        ];

        // Map degrees to cardinal direction labels
        function getDirectionLabel(degree) {
            if (degree >= 337.5 || degree < 22.5) return 'N';
            if (degree >= 22.5 && degree < 67.5) return 'NE';
            if (degree >= 67.5 && degree < 112.5) return 'E';
            if (degree >= 112.5 && degree < 157.5) return 'SE';
            if (degree >= 157.5 && degree < 202.5) return 'S';
            if (degree >= 202.5 && degree < 247.5) return 'SW';
            if (degree >= 247.5 && degree < 292.5) return 'W';
            if (degree >= 292.5 && degree < 337.5) return 'NW';
        }

        // Wind Direction Chart with Properly Aligned Dual Y-Axis
        new Chart(document.getElementById('windDirectionChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Wind Direction',
                    data: windDirections,
                    borderColor: 'rgba(153, 102, 255, 1)',
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y2'  // Link this dataset to the degree y-axis
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
                    y1: {
                        type: 'category',
                        position: 'left',
                        labels: directionLabels.map(d => d.label),  // Cardinal directions as labels
                        title: { display: true, text: 'Cardinal Direction' }
                    },
                    y2: {
                        type: 'linear',
                        position: 'right',
                        min: 0,
                        max: 360,
                        title: { display: true, text: 'Degrees (°)' },
                        ticks: {
                            callback: function(value) {
                                return `${value}°`; // Show degree symbols on right y-axis
                            },
                            stepSize: 45 // Align major directions with cardinal labels on the left
                        },
                        grid: { drawOnChartArea: false }  // Remove grid lines on the degree y-axis
                    }
                },
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const degree = context.raw;
                                const direction = getDirectionLabel(degree);
                                return `${direction} (${degree}°)`;
                            }
                        }
                    }
                }
            }
        });

        // Precipitation and Accumulated Precipitation Chart
        createCombinedChart(
            document.getElementById('precipitationChart').getContext('2d'),
            [
                {
                    label: 'Precipitation (mm)',
                    data: precipitations,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1'
                },
                {
                    label: 'Accumulated Precipitation (mm)',
                    data: accumulatedPrecipitations,
                    borderColor: 'rgba(255, 206, 86, 1)',
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y2'
                }
            ],
            ['Precipitation (mm)', 'Accumulated Precipitation (mm)']
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
