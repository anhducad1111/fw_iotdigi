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
            <!-- Filter Buttons -->
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

            <!-- History List -->
            <div id="history-list" class="rounded-xl border border-neutral-200/80 bg-white dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                    <p>Loading...</p>
                </div>
            </div>
        </main>
</div>

<script>
let currentType = 'day';

document.addEventListener('DOMContentLoaded', () => {
    // Set default values
    const today = new Date();
    document.getElementById('date-input').valueAsDate = today;
    document.getElementById('month-select').value = (today.getMonth() + 1).toString();
    document.getElementById('year-select-month').value = today.getFullYear();
    document.getElementById('year-select').value = today.getFullYear();

    // Load initial data
    loadHistory();

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
            loadHistory();
        });
    });

    // Event listeners for date/month/year inputs
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

async function loadHistory() {
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
            renderHistory(data.data);
        }
    } catch (error) {
        console.error('Error loading history:', error);
        document.getElementById('history-list').innerHTML = '<div class="text-center py-8 text-red-500 dark:text-red-400">Error loading data</div>';
    }
}

function renderHistory(items) {
    const historyList = document.getElementById('history-list');
    
    if (items.length === 0) {
        historyList.innerHTML = '<div class="text-center py-8 text-neutral-500 dark:text-neutral-400"><p>No data available</p></div>';
        return;
    }

    let html = '';
    
    if (currentType === 'day') {
        // Day view - show individual readings
        items.forEach(item => {
            const isError = item.error_code !== 0;
            const bgClass = isError ? 'bg-red-50 dark:bg-red-900/20' : 'bg-white dark:bg-neutral-900/50';
            const iconBgClass = isError ? 'bg-red-100 dark:bg-red-900/30' : 'bg-neutral-100 dark:bg-neutral-800';
            const iconColorClass = isError ? 'text-red-600 dark:text-red-400' : 'text-neutral-700 dark:text-neutral-300';
            const textColorClass = isError ? 'text-red-800 dark:text-red-300' : 'text-neutral-900 dark:text-neutral-50';
            const timeColorClass = isError ? 'text-red-600 dark:text-red-400' : 'text-neutral-500 dark:text-neutral-400';
            
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
                    <p class="text-sm font-normal ${timeColorClass}">${item.datetime}</p>
                </div>
            </div>
            `;
        });
    } else if (currentType === 'month') {
        // Month view - show daily summaries
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
            </div>
            `;
        });
    } else if (currentType === 'year') {
        // Year view - show monthly summaries
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
            </div>
            `;
        });
    }
    
    historyList.innerHTML = html;
}
</script>
</body>
</html>
