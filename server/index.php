<!DOCTYPE html>

<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>IoT Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700;800&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
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
    $page_title = "IoT Dashboard";
    include 'top.php';
    ?>
            <div class="flex flex-col items-center justify-start rounded-xl border border-neutral-200/80 bg-white p-6 text-center dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <div class="flex flex-col items-center justify-center gap-2">
                    <p class="text-neutral-500 text-base font-medium leading-normal dark:text-neutral-400">This Month Cost</p>
                    <p class="text-primary dark:text-neutral-50 text-5xl font-extrabold leading-tight tracking-[-0.015em]" id="monthly-cost-display">-- ₫</p>
                    <p class="text-neutral-500 text-sm font-normal leading-normal dark:text-neutral-400">Updated • Just now</p>
                </div>
            </div>
            <div class="flex flex-col items-center justify-start rounded-xl border border-neutral-200/80 bg-white p-6 text-center dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <div class="flex flex-col items-center justify-center gap-2">
                    <p class="text-neutral-500 text-base font-medium leading-normal dark:text-neutral-400">Current Value</p>
                    <p class="text-primary dark:text-neutral-50 text-4xl font-bold leading-tight tracking-[-0.015em]" id="current-value-display">-- m³/h</p>
                    <p class="text-neutral-500 text-sm font-normal leading-normal dark:text-neutral-400">Real-time consumption rate</p>
                </div>
            </div>
            <div class="flex flex-col items-center justify-start rounded-xl border border-neutral-200/80 bg-white p-6 text-center dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <div class="flex flex-col items-center justify-center gap-2">
                    <p class="text-neutral-500 text-base font-medium leading-normal dark:text-neutral-400">Total Volume</p>
                    <p class="text-primary dark:text-neutral-50 text-4xl font-bold leading-tight tracking-[-0.015em]" id="total-volume-display">-- m³</p>
                    <p class="text-neutral-500 text-sm font-normal leading-normal dark:text-neutral-400">accumulated reading</p>
                </div>
            </div>
            <div class="mt-2 rounded-xl border border-neutral-200/80 bg-white dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <div class="flex flex-col">
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Rate (m³/h)</span>
                        <span class="font-medium text-primary dark:text-neutral-100" id="webhook-rate">--</span>
                    </div>
                    <div class="h-px w-full bg-neutral-200/80 dark:bg-neutral-800/80"></div>
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Last Update</span>
                        <span class="font-medium text-primary dark:text-neutral-100" id="webhook-timestamp">--</span>
                    </div>
                    <div class="h-px w-full bg-neutral-200/80 dark:bg-neutral-800/80"></div>
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Change</span>
                        <span class="font-medium text-primary dark:text-neutral-100" id="webhook-changeAbsolute">--</span>
                    </div>
                    <div class="h-px w-full bg-neutral-200/80 dark:bg-neutral-800/80"></div>
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Status</span>
                        <div class="flex items-center gap-2" id="device-status">
                            <div class="h-2 w-2 rounded-full bg-green-500"></div>
                            <span class="font-medium text-primary dark:text-neutral-100">Normal</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-neutral-200/80 bg-white dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <div class="flex items-center gap-3 p-4 border-b border-neutral-200/80 dark:border-neutral-800/80">
                    <span class="material-symbols-outlined text-primary dark:text-neutral-100">info</span>
                    <h3 class="font-bold text-primary dark:text-neutral-100">Additional Data</h3>
                </div>
                <div class="webhook-info divide-y divide-neutral-200/80 dark:divide-neutral-800/80">
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Device Name</span>
                        <span id="webhook-name" class="font-medium text-primary dark:text-neutral-100">--</span>
                    </div>
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Raw Value</span>
                        <span id="webhook-rawValue" class="font-medium text-primary dark:text-neutral-100">--</span>
                    </div>
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Value</span>
                        <span id="webhook-value" class="font-medium text-primary dark:text-neutral-100">--</span>
                    </div>
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Pre Value</span>
                        <span id="webhook-preValue" class="font-medium text-primary dark:text-neutral-100">--</span>
                    </div>
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Error Code</span>
                        <span id="webhook-errorCode" class="font-medium text-primary dark:text-neutral-100">--</span>
                    </div>
                    <div class="flex items-center justify-between p-4">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Error</span>
                        <span id="webhook-error" class="font-medium text-primary dark:text-neutral-100">--</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>

</html>