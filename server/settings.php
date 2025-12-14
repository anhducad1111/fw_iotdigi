<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
$page_title = "Settings";
include 'top.php';
?>
        <!-- Threshold Section -->
        <div class="flex flex-col gap-4">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Threshold</h2>
            <div class="rounded-xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800/80 dark:bg-neutral-900/50">
                <label class="flex flex-col w-full gap-2 mb-4">
                    <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Daily Consumption Goal</p>
                    <div class="flex w-full items-stretch rounded-lg bg-neutral-100 dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-700">
                        <input 
                            id="max-rate-input"
                            type="number"
                            class="flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-neutral-900 dark:text-white focus:outline-0 focus:ring-0 border-none bg-transparent focus:border-none h-12 placeholder:text-neutral-500 p-3 rounded-r-none border-r-0 pr-2 text-base font-normal leading-normal"
                            placeholder="Enter your daily goal"
                            value="1"
                            step="0.1"
                            min="0"
                        />
                        <div class="text-neutral-600 dark:text-neutral-400 flex border-none items-center justify-center pr-4 rounded-r-lg border-l-0">
                            <span class="font-semibold">m³</span>
                        </div>
                    </div>
                </label>
                
                <label class="flex flex-col w-full gap-2">
                    <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Auto Timer Interval</p>
                    <div class="flex w-full items-stretch rounded-lg bg-neutral-100 dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-700">
                        <input 
                            id="interval-input"
                            type="number"
                            class="flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-neutral-900 dark:text-white focus:outline-0 focus:ring-0 border-none bg-transparent focus:border-none h-12 placeholder:text-neutral-500 p-3 rounded-r-none border-r-0 pr-2 text-base font-normal leading-normal"
                            placeholder="Enter interval"
                            value="2"
                            step="1"
                            min="1"
                        />
                        <div class="text-neutral-600 dark:text-neutral-400 flex border-none items-center justify-center pr-4 rounded-r-lg border-l-0">
                            <span class="font-semibold">min</span>
                        </div>
                    </div>
                </label>
                
                <button 
                    id="save-settings-btn"
                    class="flex min-w-[84px]  cursor-pointer items-center justify-center overflow-hidden rounded-lg h-12 px-5 w-full mt-4 bg-primary hover:bg-blue-900 text-white text-base font-bold leading-normal tracking-[0.015em] transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span class="truncate" id="save-btn-text">Save Changes</span>
                </button>
                <div id="status-message" class="mt-3 p-3 rounded-lg hidden text-sm font-medium"></div>
            </div>
        </div>

        <!-- Price Table Section -->
        <div class="flex flex-col gap-4">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Price Table</h2>
            <div class="flex flex-col divide-y divide-neutral-200 dark:divide-neutral-700 rounded-lg overflow-hidden border border-neutral-200/80 dark:border-neutral-800/80">
                <div class="flex items-center justify-between p-4 bg-white dark:bg-neutral-900/50">
                    <div class="flex-1">
                        <p class="font-semibold text-base text-neutral-900 dark:text-white">Tier 1</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">For the first 10m³</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-base text-primary">5,973 VND/m³</p>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 bg-white dark:bg-neutral-900/50">
                    <div class="flex-1">
                        <p class="font-semibold text-base text-neutral-900 dark:text-white">Tier 2</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">From 10m³ to 20m³</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-base text-primary">7,052 VND/m³</p>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 bg-white dark:bg-neutral-900/50">
                    <div class="flex-1">
                        <p class="font-semibold text-base text-neutral-900 dark:text-white">Tier 3</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">From 20m³ to 30m³</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-base text-primary">8,669 VND/m³</p>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 bg-white dark:bg-neutral-900/50">
                    <div class="flex-1">
                        <p class="font-semibold text-base text-neutral-900 dark:text-white">Tier 4</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">For 30m³ and above</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-base text-primary">15,929 VND/m³</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="settings.js"></script>
</body>
</html>
