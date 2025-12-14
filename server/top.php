<?php
// Header component - Include this at the top of page body
// Usage: <?php $page_title = "Page Title"; include 'top.php'; ?>

<script>
    // Toggle dropdown menu
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menu-toggle');
        const dropdownMenu = document.getElementById('dropdown-menu');

        if (menuToggle && dropdownMenu) {
            menuToggle.addEventListener('click', function() {
                dropdownMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!menuToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.add('hidden');
                }
            });
        }
    });
</script>
<div class="relative flex min-h-screen w-full flex-col group/design-root overflow-x-hidden">
    <div class="sticky top-0 z-10 flex h-16 items-center border-b border-neutral-200/80 bg-background-light/80 px-4 backdrop-blur-sm dark:border-neutral-800/80 dark:bg-background-dark/80">
        <div class="max-w-6xl w-full mx-auto flex items-center gap-4">
            <h1 class="text-lg font-bold text-primary dark:text-neutral-100 flex-1"><?php echo htmlspecialchars($page_title); ?></h1>
            <div class="relative">
                <button class="flex h-12 px-4 cursor-pointer items-center justify-center rounded-lg text-primary dark:text-neutral-100 bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 transition" id="menu-toggle">
                    <span class="material-symbols-outlined text-xl mr-2">menu</span>
                    <span class="text-sm font-medium">Menu</span>
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-neutral-800 rounded-lg shadow-lg border border-neutral-200/80 dark:border-neutral-700/80 hidden" id="dropdown-menu">
                    <a href="index.php" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition first:rounded-t-lg">
                        <span class="material-symbols-outlined text-lg text-primary">dashboard</span>
                        <span class="text-neutral-700 dark:text-neutral-100">Dashboard</span>
                    </a>
                    <a href="chart.php" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                        <span class="material-symbols-outlined text-lg text-primary">bar_chart</span>
                        <span class="text-neutral-700 dark:text-neutral-100">Chart</span>
                    </a>
                    <a href="history.php" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                        <span class="material-symbols-outlined text-lg text-primary">history</span>
                        <span class="text-neutral-700 dark:text-neutral-100">History</span>
                    </a>
                    <a href="settings.php" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition last:rounded-b-lg">
                        <span class="material-symbols-outlined text-lg text-primary">settings</span>
                        <span class="text-neutral-700 dark:text-neutral-100">Settings</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <main class="flex flex-col gap-4 p-4 max-w-6xl w-full mx-auto flex-1">
