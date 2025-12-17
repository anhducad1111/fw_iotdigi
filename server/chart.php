<?php
// Set timezone to Vietnam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Usage Chart</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
        }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#1d398d",
                        "background-light": "#f6f6f8",
                        "background-dark": "#121620"
                    },
                    fontFamily: {
                        display: "Be Vietnam Pro"
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px"
                    }
                }
            }
        };
    </script>
    <style>
        body {
            min-height: max(884px, 100dvh);
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
<?php 
$page_title = "Usage Chart";
include 'top.php';
?>
        <!-- Time Filter -->
        <div class="flex flex-col gap-4 rounded-xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800/80 dark:bg-neutral-900/50">
            <div class="flex items-center gap-4">
                <div class="flex h-10 items-center rounded-lg bg-neutral-100 dark:bg-neutral-800 p-1 gap-1">
                    <button class="filter-btn px-4 py-2 rounded text-sm font-medium transition" data-type="day">
                        Day
                    </button>
                    <button class="filter-btn px-4 py-2 rounded text-sm font-medium transition bg-primary text-white" data-type="month">
                        Month
                    </button>
                    <button class="filter-btn px-4 py-2 rounded text-sm font-medium transition" data-type="year">
                        Year
                    </button>
                </div>
            </div>

            <!-- Date Pickers -->
            <div id="date-picker-container" class="flex items-center gap-3 hidden">
                <label class="text-sm font-medium text-neutral-700 dark:text-neutral-300 whitespace-nowrap">Select Date:</label>
                <input type="date" id="date-input" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white flex-1 min-w-[150px]">
            </div>

            <div id="month-picker-container" class="flex flex-wrap items-center gap-3">
                <label class="text-sm font-medium text-neutral-700 dark:text-neutral-300 whitespace-nowrap">Month:</label>
                <select id="month-select" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white min-w-[150px]">
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
                <label class="text-sm font-medium text-neutral-700 dark:text-neutral-300 whitespace-nowrap">Year:</label>
                <select id="year-select-month" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white min-w-[100px]">
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                </select>
            </div>

            <div id="year-picker-container" class="flex items-center gap-3 hidden">
                <label class="text-sm font-medium text-neutral-700 dark:text-neutral-300 whitespace-nowrap">Year:</label>
                <select id="year-select" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white min-w-[100px]">
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                </select>
            </div>
        </div>

        <!-- Chart Card -->
        <div class="rounded-xl border border-neutral-200/80 bg-white dark:border-neutral-800/80 dark:bg-neutral-900/50 shadow">
            <div class="p-6">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white mb-4">Water Usage Chart</h2>
                <div style="position: relative; height: 300px;">
                    <canvas id="chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <p class="text-sm text-neutral-600 dark:text-neutral-400">Total Consumption</p>
                <p class="text-2xl font-bold text-primary dark:text-neutral-50 mt-2" id="stat-total">0 m³</p>
            </div>
            <div class="rounded-xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <p class="text-sm text-neutral-600 dark:text-neutral-400">Average</p>
                <p class="text-2xl font-bold text-primary dark:text-neutral-50 mt-2" id="stat-average">0 m³</p>
            </div>
            <div class="rounded-xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <p class="text-sm text-neutral-600 dark:text-neutral-400">Peak Usage</p>
                <p class="text-2xl font-bold text-primary dark:text-neutral-50 mt-2" id="stat-peak">0 m³</p>
            </div>
        </div>
    </main>
</div>

<script>
let currentType = 'month';
let chart = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Set default values
    const today = new Date();
    document.getElementById('date-input').valueAsDate = today;
    document.getElementById('month-select').value = (today.getMonth() + 1).toString();
    document.getElementById('year-select-month').value = today.getFullYear();
    document.getElementById('year-select').value = today.getFullYear();

    // Load initial chart
    loadChart();

    // Event listeners for filters
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('bg-primary', 'text-white');
                b.classList.add('text-neutral-700', 'dark:text-neutral-300');
            });
            e.target.classList.add('bg-primary', 'text-white');
            e.target.classList.remove('text-neutral-700', 'dark:text-neutral-300');

            // Update picker visibility
            currentType = e.target.dataset.type;
            updatePickerVisibility();
            loadChart();
        });
    });

    // Event listeners for date/month/year inputs
    document.getElementById('date-input').addEventListener('change', loadChart);
    document.getElementById('month-select').addEventListener('change', loadChart);
    document.getElementById('year-select-month').addEventListener('change', loadChart);
    document.getElementById('year-select').addEventListener('change', loadChart);
});

function updatePickerVisibility() {
    const dateContainer = document.getElementById('date-picker-container');
    const monthContainer = document.getElementById('month-picker-container');
    const yearContainer = document.getElementById('year-picker-container');

    dateContainer.classList.add('hidden');
    monthContainer.classList.add('hidden');
    yearContainer.classList.add('hidden');

    if (currentType === 'day') {
        dateContainer.classList.remove('hidden');
    } else if (currentType === 'month') {
        monthContainer.classList.remove('hidden');
    } else if (currentType === 'year') {
        yearContainer.classList.remove('hidden');
    }
}

async function loadChart() {
    let url = 'fetch_data.php?type=' + currentType;

    if (currentType === 'day') {
        const date = document.getElementById('date-input').value;
        url += '&date=' + date;
    } else if (currentType === 'month') {
        const month = document.getElementById('month-select').value;
        const year = document.getElementById('year-select-month').value;
        url += '&month=' + month + '&year=' + year;
    } else if (currentType === 'year') {
        const year = document.getElementById('year-select').value;
        url += '&year=' + year;
    }

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.status === 'success') {
            // Calculate totals and stats
            let values = [];
            
            if (currentType === 'day') {
                // For day view, calculate consumption from meter readings
                const reversedData = [...data.data].reverse();
                let prevValue = null;
                reversedData.forEach((d, idx) => {
                    if (idx > 0 && prevValue !== null) {
                        const consumption = d.value > prevValue ? d.value - prevValue : 0;
                        values.push(consumption);
                    }
                    prevValue = d.value;
                });
            } else {
                // For month/year view, use consumption field directly
                values = data.data.map(d => parseFloat(d.consumption || 0));
            }
            
            const total = values.length > 0 ? values.reduce((a, b) => a + b, 0) : 0;
            const peak = values.length > 0 ? Math.max(...values) : 0;
            const average = values.length > 0 ? total / values.length : 0;
            
            data.total = total.toFixed(2);
            data.average = average.toFixed(2);
            data.peak = peak.toFixed(2);
            
            updateChart(data);
            updateStats(data);
        }
    } catch (error) {
        console.error('Error loading chart:', error);
    }
}

function updateChart(data) {
    const ctx = document.getElementById('chart').getContext('2d');
    
    let labels = [];
    let values = [];

    if (currentType === 'day') {
        // For day view, reverse data first (comes DESC from API, need ASC for proper hourly flow)
        const reversedData = [...data.data].reverse();
        // Show hourly consumption by comparing consecutive readings
        let prevValue = null;
        reversedData.forEach((d, idx) => {
            if (idx > 0 && prevValue !== null) {
                const consumption = d.value > prevValue ? d.value - prevValue : 0;
                const hour = new Date(d.timestamp * 1000).getHours();
                labels.push(hour.toString().padStart(2, '0') + ':00');
                values.push(parseFloat(consumption.toFixed(2)));
            }
            prevValue = d.value;
        });
    } else if (currentType === 'month') {
        labels = data.data.map(d => 'Day ' + d.day);
        values = data.data.map(d => parseFloat(d.consumption));
    } else if (currentType === 'year') {
        labels = data.data.map(d => d.month_name.substring(0, 3));
        values = data.data.map(d => parseFloat(d.consumption));
    }

    if (chart) {
        chart.destroy();
    }

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Water Usage (m³)',
                data: values,
                borderColor: '#1d398d',
                backgroundColor: 'rgba(29, 57, 141, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#1d398d',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: document.documentElement.classList.contains('dark') ? '#ccc' : '#333'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#aaa' : '#666'
                    },
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#aaa' : '#666'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function updateStats(data) {
    document.getElementById('stat-total').textContent = data.total + ' m³';
    document.getElementById('stat-average').textContent = data.average + ' m³';
    document.getElementById('stat-peak').textContent = data.peak + ' m³';
}

</script>
</body>
</html>
