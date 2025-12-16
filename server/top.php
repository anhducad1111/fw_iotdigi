<?php
// Header component - Include this at the top of page body
// Usage: <?php $page_title = "Page Title"; include 'top.php'; ?>

// Handle Admin Device Selection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_set_device'])) {
        $devId = trim($_POST['admin_device_id']);
        if (!empty($devId)) {
            $_SESSION['admin_view_device_id'] = $devId;
        } else {
            unset($_SESSION['admin_view_device_id']);
        }
        // Refresh to apply matches
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
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
            <h1 class="text-lg font-bold text-primary dark:text-neutral-100 flex-1 flex items-center gap-2">
                <?php echo htmlspecialchars($page_title); ?>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded border border-red-200">Admin</span>
                    <?php if(isset($_SESSION['admin_view_device_id'])): ?>
                         <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded border border-blue-200">Viewing: <?php echo htmlspecialchars($_SESSION['admin_view_device_id']); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </h1>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <form method="POST" class="hidden md:flex items-center gap-2">
                <input type="text" name="admin_device_id" placeholder="Device ID (API Key)" 
                       value="<?php echo isset($_SESSION['admin_view_device_id']) ? htmlspecialchars($_SESSION['admin_view_device_id']) : ''; ?>"
                       class="h-9 px-3 text-sm rounded border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:border-primary">
                <input type="hidden" name="admin_set_device" value="1">
                <button type="submit" class="h-9 px-3 text-sm font-medium text-white bg-primary rounded hover:bg-blue-700 transition">
                    View
                </button>
            </form>
            <?php endif; ?>

            <div class="relative">
                <button class="flex h-12 px-4 cursor-pointer items-center justify-center rounded-lg text-primary dark:text-neutral-100 bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 transition" id="menu-toggle">
                    <span class="material-symbols-outlined text-xl mr-2">menu</span>
                    <span class="text-sm font-medium">Menu</span>
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-neutral-800 rounded-lg shadow-lg border border-neutral-200/80 dark:border-neutral-700/80 hidden z-50" id="dropdown-menu">
                    <div class="px-4 py-2 border-b border-neutral-100 dark:border-neutral-700">
                        <p class="text-xs text-neutral-500">Signed in as</p>
                        <p class="text-sm font-bold text-neutral-800 dark:text-neutral-100 truncate"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></p>
                    </div>
                    <a href="index.php" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
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
                    <a href="settings.php" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                        <span class="material-symbols-outlined text-lg text-primary">settings</span>
                        <span class="text-neutral-700 dark:text-neutral-100">Settings</span>
                    </a>
                    <a href="logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition last:rounded-b-lg border-t border-neutral-100 dark:border-neutral-700">
                        <span class="material-symbols-outlined text-lg">logout</span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <main class="flex flex-col gap-4 p-4 max-w-6xl w-full mx-auto flex-1">
