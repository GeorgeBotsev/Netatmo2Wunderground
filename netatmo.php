<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Netatmo Data Visualization</title>
    <script src="/chart.js"></script>
    <script src="/chartjs-adapter-date-fns"></script> <!-- Date adapter for Chart.js -->
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
        .filter-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-container button,
        .filter-container input {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .filter-container button:hover {
            background-color: #0056b3;
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

<div class="filter-container">
    <label>Select Date: <input type="date" id="filterDate"></label>
    <button onclick="filterData('day')">Day</button>
    <button onclick="filterData('week')">Week</button>
    <button onclick="filterData('month')">Month</button>
    <button onclick="filterData('year')">Year</button>
</div>

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
let allData = [];
let tempDewChart, windSpeedChart, windDirectionChart, precipitationChart, humidityChart, pressureChart;

// Load data from JSON file
fetch('netatmo_data.json')
    .then(response => response.json())
    .then(data => {
        allData = data;
        setDefaultDate();  // Set the date picker to today
        updateCharts('day');  // Default to today's data on load
    })
    .catch(error => console.error('Error loading data:', error));

// Set the default date to today and refresh data on date change
function setDefaultDate() {
    const dateInput = document.getElementById('filterDate');
    const today = new Date().toISOString().split('T')[0];  // Format today's date as YYYY-MM-DD
    dateInput.value = today;
    dateInput.addEventListener('change', () => updateCharts('day'));  // Refresh charts when date is changed
}

// Filter data based on selected range
function filterData(range) {
    updateCharts(range);
}

// Function to map degrees to cardinal directions
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

// Function to filter data based on the selected time range (day, week, month, or year)
function getFilteredData(data, range, selectedDate) {
    let startTime, endTime;
    selectedDate.setHours(0, 0, 0, 0); // Set to the beginning of the selected day

    if (range === 'day') {
        startTime = selectedDate.getTime() / 1000;
        endTime = startTime + 86400; // Add 24 hours in seconds
    } else if (range === 'week') {
        const dayOfWeek = selectedDate.getDay();
        startTime = (selectedDate.getTime() - dayOfWeek * 86400000) / 1000; // Start of the week
        endTime = startTime + 7 * 86400; // 7 days in seconds
    } else if (range === 'month') {
        startTime = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1).getTime() / 1000;
        endTime = new Date(selectedDate.getFullYear(), selectedDate.getMonth() + 1, 1).getTime() / 1000;
    } else if (range === 'year') {
        startTime = new Date(selectedDate.getFullYear(), 0, 1).getTime() / 1000;
        endTime = new Date(selectedDate.getFullYear() + 1, 0, 1).getTime() / 1000;
    } else {
        startTime = 0;
        endTime = Infinity;
    }
    
    return data.filter(entry => entry.timestamp >= startTime && entry.timestamp < endTime);
}

function updateCharts(range) {
    const dateInput = document.getElementById('filterDate').value;
    const selectedDate = new Date(dateInput);
    const filteredData = getFilteredData(allData, range, selectedDate);
    const timestamps = filteredData.map(entry => new Date(entry.timestamp * 1000));
    
    const temperatures = filteredData.map(entry => entry.temperature);
    const dewPoints = filteredData.map(entry => entry.dew_point);
    const windSpeeds = filteredData.map(entry => entry.wind_speed);
    const windGusts = filteredData.map(entry => entry.wind_gust);
    const windDirections = filteredData.map(entry => entry.wind_direction);
    const precipitations = filteredData.map(entry => entry.precipitation);
    const accumulatedPrecipitations = filteredData.map(entry => entry.accumulated_precipitation);
    const humidities = filteredData.map(entry => entry.humidity);
    const pressures = filteredData.map(entry => entry.pressure);

    // Determine the start and end time range for the x-axis based on selected range
    const xAxisLimits = getXAxisLimits(range, selectedDate);

    // Destroy existing charts before creating new ones
    if (tempDewChart) tempDewChart.destroy();
    if (windSpeedChart) windSpeedChart.destroy();
    if (windDirectionChart) windDirectionChart.destroy();
    if (precipitationChart) precipitationChart.destroy();
    if (humidityChart) humidityChart.destroy();
    if (pressureChart) pressureChart.destroy();

    // Create updated charts
    tempDewChart = createCombinedChart(
        document.getElementById('tempDewChart').getContext('2d'),
        timestamps,
        [
            { label: 'Temperature (°C)', data: temperatures, borderColor: 'rgba(255, 99, 132, 1)', backgroundColor: 'rgba(255, 99, 132, 0.2)', yAxisID: 'y1' },
            { label: 'Dew Point (°C)', data: dewPoints, borderColor: 'rgba(54, 162, 235, 1)', backgroundColor: 'rgba(54, 162, 235, 0.2)', yAxisID: 'y2' }
        ],
        ['Temperature (°C)', 'Dew Point (°C)'],
        xAxisLimits
    );
    windSpeedChart = createCombinedChart(
        document.getElementById('windSpeedChart').getContext('2d'),
        timestamps,
        [
            { label: 'Wind Speed (km/h)', data: windSpeeds, borderColor: 'rgba(75, 192, 192, 1)', backgroundColor: 'rgba(75, 192, 192, 0.2)', yAxisID: 'y1' },
            { label: 'Wind Gust (km/h)', data: windGusts, borderColor: 'rgba(255, 159, 64, 1)', backgroundColor: 'rgba(255, 159, 64, 0.2)', yAxisID: 'y1' }
        ],
        ['Wind Speed (km/h)', 'Wind Gust (km/h)'],
        xAxisLimits
    );
    windDirectionChart = createWindDirectionChart(
        document.getElementById('windDirectionChart').getContext('2d'),
        timestamps, 'Wind Direction (°)', windDirections, 'Degrees (°)', xAxisLimits
    );
    precipitationChart = createCombinedChart(
        document.getElementById('precipitationChart').getContext('2d'),
        timestamps,
        [
            { label: 'Precipitation (mm)', data: precipitations, borderColor: 'rgba(75, 192, 192, 1)', backgroundColor: 'rgba(75, 192, 192, 0.2)', yAxisID: 'y1' },
            { label: 'Accumulated Precipitation (mm)', data: accumulatedPrecipitations, borderColor: 'rgba(255, 206, 86, 1)', backgroundColor: 'rgba(255, 206, 86, 0.2)', yAxisID: 'y2' }
        ],
        ['Precipitation (mm)', 'Accumulated Precipitation (mm)'],
        xAxisLimits
    );
    humidityChart = createSingleChart(
        document.getElementById('humidityChart').getContext('2d'),
        timestamps, 'Humidity (%)', humidities, 'Humidity (%)', xAxisLimits, true, true
    );
    pressureChart = createSingleChart(
        document.getElementById('pressureChart').getContext('2d'),
        timestamps, 'Pressure (hPa)', pressures, 'Pressure (hPa)', xAxisLimits, true, true
    );
}

function getXAxisLimits(range, selectedDate) {
    const oneDay = 86400000; // Milliseconds in a day
    let startDate, endDate;

    if (range === 'day') {
        startDate = selectedDate.getTime();
        endDate = startDate + oneDay;
    } else if (range === 'week') {
        const dayOfWeek = selectedDate.getDay();
        startDate = selectedDate.getTime() - dayOfWeek * oneDay;
        endDate = startDate + 7 * oneDay;
    } else if (range === 'month') {
        startDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1).getTime();
        endDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth() + 1, 1).getTime();
    } else if (range === 'year') {
        startDate = new Date(selectedDate.getFullYear(), 0, 1).getTime();
        endDate = new Date(selectedDate.getFullYear() + 1, 0, 1).getTime();
    }
    
    return {
        min: startDate,
        max: endDate
    };
}

// Function to create combined line charts
function createCombinedChart(ctx, labels, datasets, yLabels, xAxisLimits) {
    return new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: { unit: 'day' },
                    min: xAxisLimits.min,
                    max: xAxisLimits.max,
                    title: { display: true, text: 'Timestamp' },
                    ticks: { maxTicksLimit: 10 }
                },
                y1: { type: 'linear', position: 'left', title: { display: true, text: yLabels[0] }},
                y2: { type: 'linear', position: 'right', title: { display: true, text: yLabels[1] }, grid: { drawOnChartArea: false }}
            },
            plugins: { legend: { display: true, position: 'top' }, tooltip: { mode: 'index', intersect: false }}
        }
    });
}

// Function to create single line charts with optional y-axis limits
function createSingleChart(ctx, labels, label, data, yLabel, xAxisLimits, yAxisLimits = {}, pointOnly = false) {
    return new Chart(ctx, {
        type: 'line',
        data: { 
            labels, 
            datasets: [{
                label, 
                data, 
                borderColor: 'rgba(75, 192, 192, 1)', 
                backgroundColor: 'rgba(75, 192, 192, 0.2)', 
                borderWidth: 2,
                fill: !pointOnly,       
                tension: 0.3,
                showLine: !pointOnly,
                pointStyle: 'circle',
                radius: pointOnly ? 5 : 3
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: { unit: 'day' },
                    min: xAxisLimits.min,
                    max: xAxisLimits.max,
                    title: { display: true, text: 'Timestamp' },
                    ticks: { maxTicksLimit: 10 }
                },
                y: {
                    title: { display: true, text: yLabel },
                    min: yAxisLimits.min,
                    max: yAxisLimits.max
                }
            },
            plugins: { legend: { display: true, position: 'top' }, tooltip: { mode: 'index', intersect: false }}
        }
    });
}

// Function to create the wind direction chart with an additional y-axis for cardinal directions
function createWindDirectionChart(ctx, labels, label, data, yLabel, xAxisLimits) {
    return new Chart(ctx, {
        type: 'line',
        data: { 
            labels, 
            datasets: [{
                label, 
                data, 
                borderColor: 'rgba(153, 102, 255, 1)',
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                borderWidth: 2,
                fill: false,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: { unit: 'day' },
                    min: xAxisLimits.min,
                    max: xAxisLimits.max,
                    title: { display: true, text: 'Timestamp' },
                    ticks: { maxTicksLimit: 10 }
                },
                y: {
                    title: { display: true, text: yLabel },
                    min: 0,
                    max: 360
                },
                y1: {  // Secondary y-axis with cardinal directions
                    position: 'right',
                    title: { display: true, text: 'Direction' },
                    min: 0,
                    max: 360,
                    ticks: {
                        stepSize: 45,
                        callback: function(value) {
                            const directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW', 'N'];
                            return directions[value / 45] || '';  // Show direction at each 45-degree mark
                        }
                    },
                    grid: { drawOnChartArea: false }
                }
            },
            plugins: { 
                legend: { display: true, position: 'top' }, 
                tooltip: { 
                    mode: 'index', 
                    intersect: false,
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
}


</script>

</body>
</html>
