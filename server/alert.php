<?php
// Set timezone to Vietnam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Handle API requests
$is_api = isset($_GET['type']);

if ($is_api) {
    require_once 'auth_check.php';
    header('Content-Type: application/json');
    require_once 'db/database_config.php';
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
    }
    
    $targetDeviceId = getTargetDeviceId();
    if (!$targetDeviceId) {
        echo json_encode(['status' => 'success', 'data' => []]);
        $conn->close();
        exit;
    }
    
    $deviceId = $conn->real_escape_string($targetDeviceId);
    $type = $_GET['type'] ?? 'day';
    $date = $_GET['date'] ?? date('Y-m-d');
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');

    $query = "";
    if ($type === 'day') {
        $query = "SELECT * FROM alerts WHERE device_id = '$deviceId' AND DATE(FROM_UNIXTIME(timestamp)) = '$date' ORDER BY timestamp DESC";
    } elseif ($type === 'month') {
        $query = "SELECT * FROM alerts WHERE device_id = '$deviceId' AND YEAR(FROM_UNIXTIME(timestamp)) = $year AND MONTH(FROM_UNIXTIME(timestamp)) = $month ORDER BY timestamp DESC";
    } elseif ($type === 'year') {
        $query = "SELECT * FROM alerts WHERE device_id = '$deviceId' AND YEAR(FROM_UNIXTIME(timestamp)) = $year ORDER BY timestamp DESC";
    }

    $result = $conn->query($query);
    $alerts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['timestamp'] = intval($row['timestamp']);
            $row['resolved'] = (bool)$row['resolved'];
            $row['datetime'] = date('H:i, d/m/Y', $row['timestamp']);
            
            // Format time ago
            $time_diff = time() - $row['timestamp'];
            if ($time_diff < 60) $row['time_ago'] = 'just now';
            elseif ($time_diff < 3600) $row['time_ago'] = floor($time_diff/60) . 'm ago';
            elseif ($time_diff < 86400) $row['time_ago'] = floor($time_diff/3600) . 'h ago';
            else $row['time_ago'] = floor($time_diff/86400) . 'd ago';
            
            $alerts[] = $row;
        }
    }

    echo json_encode(['status' => 'success', 'data' => $alerts]);
    $conn->close();
    exit;
}

// HTML Mode
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
    <title>Alerts</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#1d398d",
                        "background-light": "#f6f6f8",
                        "background-dark": "#121620"
                    },
                    fontFamily: { display: "Be Vietnam Pro" },
                    borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", full: "9999px" }
                }
            }
        };
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
<?php 
$page_title = "Alerts";
include 'top.php';
?>

    <main class="flex flex-col gap-4 p-4 max-w-6xl w-full mx-auto flex-1">
        <!-- Controls Container -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 rounded-xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800/80 dark:bg-neutral-900/50">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                <div class="flex h-10 items-center rounded-lg bg-neutral-100 dark:bg-neutral-800 p-1 gap-1">
                    <button class="filter-btn px-4 py-2 rounded text-sm font-medium transition" data-type="day">Day</button>
                    <button class="filter-btn px-4 py-2 rounded text-sm font-medium transition bg-primary text-white" data-type="month">Month</button>
                    <button class="filter-btn px-4 py-2 rounded text-sm font-medium transition" data-type="year">Year</button>
                </div>

                <!-- Date Pickers (Sync with history.php logic) -->
                <div id="date-picker-container" class="flex items-center gap-2 hidden">
                    <input type="date" id="date-input" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white text-sm">
                </div>

                <div id="month-picker-container" class="flex items-center gap-2">
                    <select id="month-select" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white text-sm min-w-[120px] cursor-pointer">
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="year-select-month" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white text-sm min-w-[100px] cursor-pointer">
                        <?php for($y=date('Y')-1; $y<=date('Y')+1; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div id="year-picker-container" class="flex items-center gap-2 hidden">
                    <select id="year-select" class="px-3 py-2 border border-neutral-300 rounded-lg dark:bg-neutral-800 dark:border-neutral-700 dark:text-white text-sm min-w-[100px] cursor-pointer">
                         <?php for($y=date('Y')-1; $y<=date('Y')+1; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <!-- Clear Button Placeholder -->
            <div>
                 <button class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                    <span class="material-symbols-outlined text-lg">delete_sweep</span>
                    <span>Clear Visual</span>
                </button>
            </div>
        </div>

        <!-- Alerts List -->
        <div id="alerts-list" class="flex flex-col gap-3">
             <div class="text-center py-12 text-neutral-500">
                <p>Loading alerts...</p>
            </div>
        </div>
    </main>

    <script>
    let currentType = 'month';

    document.addEventListener('DOMContentLoaded', () => {
        // Set default values
        const today = new Date();
        document.getElementById('date-input').valueAsDate = today;

        loadAlerts();

        // Filter Buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn').forEach(b => {
                    b.classList.remove('bg-primary', 'text-white');
                    b.classList.add('text-neutral-700', 'dark:text-neutral-300');
                });
                const target = e.currentTarget;
                target.classList.add('bg-primary', 'text-white');
                target.classList.remove('text-neutral-700', 'dark:text-neutral-300');

                currentType = target.dataset.type;
                updatePickerVisibility();
                loadAlerts();
            });
        });

        // Event listeners for inputs
        ['date-input', 'month-select', 'year-select-month', 'year-select'].forEach(id => {
            document.getElementById(id).addEventListener('change', loadAlerts);
        });
    });

    function updatePickerVisibility() {
        document.getElementById('date-picker-container').classList.toggle('hidden', currentType !== 'day');
        document.getElementById('month-picker-container').classList.toggle('hidden', currentType !== 'month');
        document.getElementById('year-picker-container').classList.toggle('hidden', currentType !== 'year');
    }

    async function loadAlerts() {
        let url = `alert.php?type=${currentType}`;
        if (currentType === 'day') url += `&date=${document.getElementById('date-input').value}`;
        else if (currentType === 'month') url += `&month=${document.getElementById('month-select').value}&year=${document.getElementById('year-select-month').value}`;
        else if (currentType === 'year') url += `&year=${document.getElementById('year-select').value}`;

        try {
            const res = await fetch(url);
            const json = await res.json();
            if (json.status === 'success') {
                renderAlerts(json.data);
            }
        } catch (e) { console.error(e); }
    }

    function renderAlerts(alerts) {
        const list = document.getElementById('alerts-list');
        if (alerts.length === 0) {
            list.innerHTML = `
                <div class="flex flex-col items-center justify-center text-center py-16 px-4">
                    <div class="flex items-center justify-center size-16 rounded-full bg-neutral-100 dark:bg-neutral-800">
                         <span class="material-symbols-outlined text-3xl text-neutral-400">notifications_off</span>
                    </div>
                    <p class="mt-4 text-base font-medium text-neutral-900 dark:text-white">No Alerts Found</p>
                    <p class="mt-1 text-sm text-neutral-500">Everything looks normal for this period.</p>
                </div>`;
            return;
        }

        list.innerHTML = alerts.map(alert => {
            const isResolved = alert.resolved;
            const icon = alert.type === 'Neg. Rate' ? 'error' : (alert.type === 'Rate too high' ? 'warning' : 'info');
            const iconBg = alert.type === 'Neg. Rate' ? 'bg-red-100 dark:bg-red-900/50' : 'bg-amber-100 dark:bg-amber-900/50';
            const iconColor = alert.type === 'Neg. Rate' ? 'text-red-500' : 'text-amber-500';
            
            return `
            <div class="flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm border border-neutral-100 dark:bg-neutral-900/50 dark:border-neutral-800">
                <div class="flex items-start gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full ${iconBg}">
                        <span class="material-symbols-outlined ${iconColor}">${icon}</span>
                    </div>
                    <div class="flex flex-1 flex-col">
                        <p class="text-base font-medium text-neutral-900 dark:text-white">${alert.type}</p>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">${alert.message}</p>
                        ${alert.value_at_event ? `<p class="mt-1 text-xs font-bold text-primary italic">Value at event: ${alert.value_at_event} m³</p>` : ''}
                    </div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-neutral-50 dark:border-neutral-800/50">
                    <p class="text-xs text-neutral-400 font-medium flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">schedule</span> ${alert.datetime} (${alert.time_ago})
                    </p>
                    <div class="rounded-full ${isResolved ? 'bg-neutral-100 text-neutral-500' : 'bg-red-100 text-red-600'} px-3 py-1 text-xs font-bold uppercase tracking-wider">
                        ${isResolved ? 'Resolved' : 'Active'}
                    </div>
                </div>
            </div>`;
        }).join('');
    }
    </script>
</body>
</html>
