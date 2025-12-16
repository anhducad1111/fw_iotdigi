<?php
// Set timezone to Vietnam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Check if this is an API request
$is_api = isset($_GET['type']);

if ($is_api) {
    require_once 'auth_check.php';
    // API Mode - Return JSON
    header('Content-Type: application/json');
    
    // Database connection
    require_once 'db/database_config.php';
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        die(json_encode([
            'status' => 'error',
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    
    $targetDeviceId = getTargetDeviceId();

    if (!$targetDeviceId) {
        echo json_encode(['status' => 'success', 'data' => []]);
        $conn->close();
        exit;
    }
    
    $deviceId = $conn->real_escape_string($targetDeviceId);

    $response = [
        'status' => 'success',
        'data' => []
    ];

    $type = $_GET['type'] ?? 'day'; // day, month, year
    $date = $_GET['date'] ?? date('Y-m-d'); // For day view
    $month = $_GET['month'] ?? date('m'); // For month view
    $year = $_GET['year'] ?? date('Y'); // For year view

    if ($type === 'day') {
        // Get all readings for a specific day
        $result = $conn->query("
            SELECT 
                id,
                timestamp,
                value,
                error_code,
                error_message
            FROM readings
            WHERE device_id = '$deviceId' 
              AND DATE(CONVERT_TZ(FROM_UNIXTIME(timestamp), '+00:00', '+07:00')) = '$date'
            ORDER BY timestamp DESC
        ");
        
        $readings = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $timestamp = intval($row['timestamp']);
                $datetime = date('h:i A, d M', $timestamp);
                
                $readings[] = [
                    'id' => $row['id'],
                    'value' => floatval($row['value']),
                    'timestamp' => $timestamp,
                    'datetime' => $datetime,
                    'error_code' => intval($row['error_code']),
                    'error_message' => $row['error_message']
                ];
            }
        }
        $response['data'] = $readings;
        
    } elseif ($type === 'month') {
        // Get daily summaries for a specific month
        $result = $conn->query("
            SELECT 
                date,
                consumption,
                start_value,
                end_value
            FROM daily_usage
            WHERE device_id = '$deviceId' 
              AND YEAR(date) = $year AND MONTH(date) = $month
            ORDER BY date DESC
        ");
        
        $daily = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $datetime = date('h:i A, d M', strtotime($row['date']));
                
                $daily[] = [
                    'date' => $row['date'],
                    'consumption' => floatval($row['consumption']),
                    'datetime' => $datetime,
                    'start_value' => floatval($row['start_value']),
                    'end_value' => floatval($row['end_value'])
                ];
            }
        }
        $response['data'] = $daily;
        
    } elseif ($type === 'year') {
        // Get monthly summaries for a specific year
        $result = $conn->query("
            SELECT 
                year,
                month,
                consumption,
                cost
            FROM monthly_usage
            WHERE device_id = '$deviceId' 
              AND year = $year
            ORDER BY month DESC
        ");
        
        $monthly = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $month_name = date('F', mktime(0, 0, 0, intval($row['month']), 1));
                
                $monthly[] = [
                    'year' => intval($row['year']),
                    'month' => intval($row['month']),
                    'month_name' => $month_name,
                    'consumption' => floatval($row['consumption']),
                    'cost' => floatval($row['cost'])
                ];
            }
        }
        $response['data'] = $monthly;
    }

    $conn->close();
    echo json_encode($response);
    exit;
}

// HTML Mode - Return page
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>History</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .material-symbols-outlined {
            font-variation-settings:
                'FILL' 0,
                'wght' 400,
                'GRAD' 0,
                'opsz' 24
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
$page_title = "History";
include 'top.php';
?>

        <main class="flex flex-col gap-4 p-4 max-w-6xl w-full mx-auto flex-1">
            <!-- Controls Container -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 rounded-xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <!-- Filters -->
                <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
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

                    <!-- Date Pickers -->
                    <div id="date-picker-container" class="flex items-center gap-2 hidden">
                        <input type="date" id="date-input" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white text-sm">
                    </div>

                    <div id="month-picker-container" class="flex items-center gap-2">
                        <select id="month-select" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white text-sm min-w-[120px] cursor-pointer">
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
                        <select id="year-select-month" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white text-sm min-w-[100px] cursor-pointer">
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>

                    <div id="year-picker-container" class="flex items-center gap-2 hidden">
                        <select id="year-select" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white text-sm min-w-[100px] cursor-pointer">
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>
                </div>

                <!-- View Mode Toggle -->
                <div class="flex h-10 items-center rounded-lg bg-neutral-100 dark:bg-neutral-800 p-1 gap-1 self-end md:self-auto">
                    <button class="view-btn px-4 py-2 rounded text-sm font-medium transition bg-primary text-white flex items-center gap-2" data-mode="list">
                        <span class="material-symbols-outlined text-lg">list</span> <span>List</span>
                    </button>
                    <button class="view-btn px-4 py-2 rounded text-sm font-medium transition text-neutral-700 dark:text-neutral-300 flex items-center gap-2" data-mode="chart">
                        <span class="material-symbols-outlined text-lg">bar_chart</span> <span>Chart</span>
                    </button>
                </div>
            </div>

            <!-- List View -->
            <div id="history-list" class="rounded-xl border border-neutral-200/80 bg-white dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                    <p>Loading...</p>
                </div>
            </div>

            <!-- Chart View -->
            <div id="history-chart" class="rounded-xl border border-neutral-200/80 bg-white dark:border-neutral-800/80 dark:bg-neutral-900/50 p-6 hidden">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white mb-4">Consumption Chart</h2>
                <div class="relative h-[300px] w-full">
                    <canvas id="chart"></canvas>
                </div>
                
                <!-- Chart Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    <div class="rounded-lg bg-neutral-50 dark:bg-neutral-800 p-4">
                        <p class="text-xs text-neutral-500">Total Consumption</p>
                        <p class="text-xl font-bold text-primary dark:text-white mt-1" id="chart-total">-- m³</p>
                    </div>
                    <div class="rounded-lg bg-neutral-50 dark:bg-neutral-800 p-4">
                        <p class="text-xs text-neutral-500">Average</p>
                        <p class="text-xl font-bold text-primary dark:text-white mt-1" id="chart-avg">-- m³</p>
                    </div>
                    <div class="rounded-lg bg-neutral-50 dark:bg-neutral-800 p-4">
                        <p class="text-xs text-neutral-500">Peak Usage</p>
                        <p class="text-xl font-bold text-primary dark:text-white mt-1" id="chart-peak">-- m³</p>
                    </div>
                </div>
            </div>

        </main>
</div>

<script>
let currentType = 'day';
let currentMode = 'list';
let chartInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    // Set default values
    const today = new Date();
    document.getElementById('date-input').valueAsDate = today;
    document.getElementById('month-select').value = (today.getMonth() + 1).toString();
    document.getElementById('year-select-month').value = today.getFullYear();
    document.getElementById('year-select').value = today.getFullYear();
    
    // Set initial Picker Visibility based on default 'month'
    currentType = 'month'; // Force month default logic to match buttons
    updatePickerVisibility();

    // Load initial data
    loadHistory();

    // Filter Buttons logic
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // UI Update
            document.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('bg-primary', 'text-white');
                b.classList.add('text-neutral-700', 'dark:text-neutral-300');
            });
            const target = e.currentTarget; // Use currentTarget to ensure we get button
            target.classList.add('bg-primary', 'text-white');
            target.classList.remove('text-neutral-700', 'dark:text-neutral-300');

            // Logic
            currentType = target.dataset.type;
            updatePickerVisibility();
            loadHistory();
        });
    });

    // View Mode Toggle logic
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.view-btn').forEach(b => {
                b.classList.remove('bg-primary', 'text-white');
                b.classList.add('text-neutral-700', 'dark:text-neutral-300');
            });
            const target = e.currentTarget;
            target.classList.add('bg-primary', 'text-white');
            target.classList.remove('text-neutral-700', 'dark:text-neutral-300');

            currentMode = target.dataset.mode;
            updateViewMode();
        });
    });

    // Inputs change logic
    document.getElementById('date-input').addEventListener('change', loadHistory);
    document.getElementById('month-select').addEventListener('change', loadHistory);
    document.getElementById('year-select-month').addEventListener('change', loadHistory);
    document.getElementById('year-select').addEventListener('change', loadHistory);
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

function updateViewMode() {
    const listDiv = document.getElementById('history-list');
    const chartDiv = document.getElementById('history-chart');

    if (currentMode === 'list') {
        listDiv.classList.remove('hidden');
        chartDiv.classList.add('hidden');
    } else {
        listDiv.classList.add('hidden');
        chartDiv.classList.remove('hidden');
    }
    // Reload to ensure chart renders if switching for first time
    loadHistory(); 
}

async function loadHistory() {
    let url = 'fetch_data.php?type=' + currentType;
    if (currentType === 'day') {
        url += '&date=' + document.getElementById('date-input').value;
    } else if (currentType === 'month') {
        url += '&month=' + document.getElementById('month-select').value + '&year=' + document.getElementById('year-select-month').value;
    } else if (currentType === 'year') {
        url += '&year=' + document.getElementById('year-select').value;
    }

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.status === 'success') {
            if (currentMode === 'list') {
                renderList(data.data);
            } else {
                renderChart(data.data);
            }
        }
    } catch (error) {
        console.error('Error loading history:', error);
    }
}

function renderList(items) {
    const historyList = document.getElementById('history-list');
    
    if (items.length === 0) {
        historyList.innerHTML = '<div class="text-center py-8 text-neutral-500 dark:text-neutral-400"><p>No data available</p></div>';
        return;
    }

    let html = '';
    
    if (currentType === 'day') {
        items.forEach(item => {
            // ... (Same Day logic)
            const isError = item.error_code !== 0;
            const bgClass = isError ? 'bg-red-50 dark:bg-red-900/20' : 'bg-white dark:bg-neutral-900/50';
            const iconBgClass = isError ? 'bg-red-100 dark:bg-red-900/30' : 'bg-neutral-100 dark:bg-neutral-800';
            const iconColorClass = isError ? 'text-red-600 dark:text-red-400' : 'text-neutral-700 dark:text-neutral-300';
            const textColorClass = isError ? 'text-red-800 dark:text-red-300' : 'text-neutral-900 dark:text-neutral-50';
            
            const icon = isError ? 'error' : 'water_drop';
            const label = isError ? (item.error_message || 'Error') : item.value + ' m³';
            
            html += `
            <div class="flex min-h-[3.5rem] items-center gap-4 ${bgClass} px-4 border-b border-neutral-200/80 dark:border-neutral-800/80 last:border-b-0">
                <div class="flex items-center gap-4 flex-1">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${iconBgClass} ${iconColorClass}">
                        <span class="material-symbols-outlined text-xl">${icon}</span>
                    </div>
                    <p class="flex-1 truncate text-base font-normal ${textColorClass}">${label}</p>
                </div>
                <div class="ml-auto shrink-0">
                    <p class="text-sm font-normal text-neutral-500 dark:text-neutral-400">${item.datetime}</p>
                </div>
            </div>`;
        });
    } else if (currentType === 'month') {
        items.forEach(item => {
            html += `
            <div class="flex min-h-[3.5rem] items-center gap-4 bg-white dark:bg-neutral-900/50 px-4 border-b border-neutral-200/80 dark:border-neutral-800/80 last:border-b-0">
                <div class="flex items-center gap-4 flex-1">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                        <span class="material-symbols-outlined text-xl">water_drop</span>
                    </div>
                    <p class="flex-1 truncate text-base font-normal text-neutral-900 dark:text-neutral-50">${item.consumption.toFixed(2)} m³</p>
                </div>
                <div class="ml-auto shrink-0">
                    <p class="text-sm font-normal text-neutral-500 dark:text-neutral-400">${item.date}</p>
                </div>
            </div>`;
        });
    } else if (currentType === 'year') {
        items.forEach(item => {
            html += `
            <div class="flex min-h-[3.5rem] items-center gap-4 bg-white dark:bg-neutral-900/50 px-4 border-b border-neutral-200/80 dark:border-neutral-800/80 last:border-b-0">
                <div class="flex items-center gap-4 flex-1">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                        <span class="material-symbols-outlined text-xl">water_drop</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-base font-normal text-neutral-900 dark:text-neutral-50">${item.month_name}</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">${item.consumption.toFixed(2)} m³</p>
                    </div>
                </div>
                <div class="ml-auto shrink-0">
                    <p class="text-sm font-semibold text-primary">${item.cost.toLocaleString('vi-VN', {style: 'currency', currency: 'VND', minimumFractionDigits: 0})}</p>
                </div>
            </div>`;
        });
    }
    
    historyList.innerHTML = html;
}

function renderChart(items) {
    const ctx = document.getElementById('chart').getContext('2d');
    let labels = [];
    let values = [];

    if (currentType === 'day') {
        // Reverse for chart: Oldest FIRST
        const dataAsc = [...items].reverse();
        
        // Calculate hourly consumption
        let prevVal = null;
        dataAsc.forEach((d, idx) => {
            if (idx > 0 && prevVal !== null) {
                const diff = d.value > prevVal ? d.value - prevVal : 0;
                // Only take Hour
                const hr = new Date(d.timestamp * 1000).getHours() + ":00";
                labels.push(hr);
                values.push(diff);
            }
            prevVal = d.value;
        });

    } else if (currentType === 'month') {
        // Note: fetch_data returns ASC by date for 'month' type. Wait, fetch_data returns DESC?
        // Let's check fetch_data.php... 
        // -> month query: ORDER BY date ASC (Wait.. history.php query was DESC, fetch_data WAS ASC)
        // I should check my own update to fetch_data.php in step 83.
        // It says: "ORDER BY date ASC" in fetch_data.php.
        // But in history.php provided snippet (Step 78), query was "ORDER BY date DESC".
        // My recent history.php update (Step 89) used "ORDER BY date DESC".
        // OK, list view expects DESC (Recent first). Chart needs ASC (Time flow).
        
        // Let's assume we sort ASC for chart.
        const sorted = [...items].sort((a,b) => new Date(a.date) - new Date(b.date));
        labels = sorted.map(d => d.date.split('-')[2]); // Just Day
        values = sorted.map(d => parseFloat(d.consumption));

    } else if (currentType === 'year') {
        // year query in fetch_data.php was ASC. 
        // in history.php step 89 was DESC.
        const sorted = [...items].sort((a,b) => a.month - b.month);
        labels = sorted.map(d => d.month_name.substring(0,3));
        values = sorted.map(d => parseFloat(d.consumption));
    }

    // Stats
    const total = values.reduce((a,b) => a+b, 0);
    const avg = values.length ? total / values.length : 0;
    const peak = values.length ? Math.max(...values) : 0;

    document.getElementById('chart-total').textContent = total.toFixed(2) + " m³";
    document.getElementById('chart-avg').textContent = avg.toFixed(2) + " m³";
    document.getElementById('chart-peak').textContent = peak.toFixed(2) + " m³";

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
        type: 'line', // Usage usually represented as bar for period, but line is sleek
        data: {
            labels: labels,
            datasets: [{
                label: 'Consumption (m³)',
                data: values,
                borderColor: '#1d398d',
                backgroundColor: 'rgba(29, 57, 141, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#1d398d'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>
</body>
</html>
