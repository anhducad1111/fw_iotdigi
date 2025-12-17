<?php
// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'auth_check.php';
require_once 'db/database_config.php';

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- CONFIGURATION ---
$BANK_ID = "TIMO"; 
$ACCOUNT_NO = "0943079599"; 
$ACCOUNT_NAME = "Nguyen Anh Duc";
$TEMPLATE = "compact2"; 

$targetDeviceId = getTargetDeviceId();
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$view = $_GET['view'] ?? 'bill'; // 'bill' or 'history'

// Handle Actions (Admin)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['invoice_id'])) {
        $invoiceId = intval($_POST['invoice_id']);
        $status = ($_POST['action'] === 'mark_paid') ? 'paid' : 'unpaid';
        $paidAt = ($status === 'paid') ? "NOW()" : "NULL";
        
        $sql = "UPDATE monthly_usage SET payment_status = '$status', paid_at = $paidAt WHERE id = $invoiceId";
        $conn->query($sql);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

// Data Fetching
$data = [];
$historyData = [];

// Shared Time Params
$selectedMonth = $_GET['month'] ?? date('m');
$selectedYear = $_GET['year'] ?? date('Y');
$historyYear = $_GET['history_year'] ?? date('Y');

if ($view === 'history') {
    // FETCH HISTORY DATA
    if ($isAdmin) {
        // Admin sees history for ALL devices for selected year
        // Or filtered by specific device if selected
        $sql = "SELECT m.*, u.full_name 
                FROM monthly_usage m 
                JOIN users u ON m.device_id = u.api_key 
                WHERE m.year = $historyYear";
        if ($targetDeviceId) {
            $sql .= " AND m.device_id = '$targetDeviceId'";
        }
        $sql .= " ORDER BY m.month DESC, m.device_id ASC";
    } else {
        // User sees their own history
        $sql = "SELECT * FROM monthly_usage 
               WHERE device_id = '$targetDeviceId' AND year = $historyYear 
               ORDER BY month DESC";
    }
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $historyData[] = $row;
        }
    }

} else {
    // FETCH BILL DATA (Single Month)
    if ($isAdmin) {
        $sql = "SELECT m.*, u.full_name, u.username 
                FROM monthly_usage m 
                JOIN users u ON m.device_id = u.api_key 
                WHERE m.year = $selectedYear AND m.month = $selectedMonth";
        if ($targetDeviceId) {
            $sql .= " AND m.device_id = '$targetDeviceId'";
        }
    } else {
        if ($targetDeviceId) {
            $sql = "SELECT * FROM monthly_usage 
                    WHERE device_id = '$targetDeviceId' AND year = $selectedYear AND month = $selectedMonth";
        }
    }

    if (isset($sql)) {
        $result = $conn->query($sql);
        if ($isAdmin) {
            if ($result) while ($row = $result->fetch_assoc()) $data[] = $row;
        } else {
            if ($result && $row = $result->fetch_assoc()) $data = $row;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Payment & Billing</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
                    }
                }
            }
        };
    </script>
    <style>body { min-height: 100vh; }</style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-neutral-900 dark:text-neutral-100">
<?php 
$page_title = "Payment & Billing";
include 'top.php'; 
?>

<main class="flex flex-col gap-4 p-4 max-w-4xl w-full mx-auto flex-1">
    
    <!-- Controls Section -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-neutral-800 p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 shadow-sm">
        
        <?php if ($view === 'history'): ?>
            <!-- History Mode Controls -->
            <form method="GET" class="flex items-center gap-3">
                <input type="hidden" name="view" value="history">
                <label class="font-medium text-sm">History Year:</label>
                <select name="history_year" class="px-3 py-2 border rounded-lg dark:bg-neutral-700 dark:border-neutral-600 text-sm min-w-[100px] cursor-pointer" onchange="this.form.submit()">
                    <?php 
                    $currentY = date('Y');
                    for($y=$currentY; $y >= $currentY-2; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $historyYear) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>

            <a href="payment.php?view=bill" class="flex items-center gap-2 text-sm font-medium text-primary dark:text-blue-400 hover:text-blue-700 bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-lg transition">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Back to Bill
            </a>

        <?php else: ?>
            <!-- Bill Mode Controls -->
            <form method="GET" class="flex items-center gap-3 flex-1">
                <input type="hidden" name="view" value="bill">
                <label class="font-medium text-sm whitespace-nowrap">Billing Period:</label>
                <select name="month" class="px-3 py-2 border rounded-lg dark:bg-neutral-700 dark:border-neutral-600 text-sm min-w-[120px] cursor-pointer" onchange="this.form.submit()">
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i == $selectedMonth) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="px-3 py-2 border rounded-lg dark:bg-neutral-700 dark:border-neutral-600 text-sm min-w-[100px] cursor-pointer" onchange="this.form.submit()">
                    <?php 
                    $currentY = date('Y');
                    for($y=$currentY; $y >= $currentY-2; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $selectedYear) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>

            <a href="payment.php?view=history" class="group flex items-center gap-2 text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:text-primary dark:hover:text-white bg-neutral-100 dark:bg-neutral-700 hover:bg-neutral-200 dark:hover:bg-neutral-600 px-4 py-2 rounded-lg transition ml-auto">
                <span class="material-symbols-outlined text-lg group-hover:scale-110 transition">history</span>
                Payment History
            </a>
        <?php endif; ?>
    </div>

    <?php if ($view === 'history'): ?>
        <!-- HISTORY VIEW -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
                <h2 class="text-lg font-bold">Payment History (<?php echo $historyYear; ?>)</h2>
            </div>
            
            <?php if (empty($historyData)): ?>
                <div class="p-8 text-center text-neutral-500">No records found for <?php echo $historyYear; ?>.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-50 dark:bg-neutral-700/50 border-b border-neutral-200 dark:border-neutral-700">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Month</th>
                                <?php if($isAdmin): ?><th class="px-6 py-4 font-semibold">User / Device</th><?php endif; ?>
                                <th class="px-6 py-4 font-semibold text-right">Consumption</th>
                                <th class="px-6 py-4 font-semibold text-right">Amount</th>
                                <th class="px-6 py-4 font-semibold text-center">Status</th>
                                <th class="px-6 py-4 font-semibold text-right">Paid Date</th>
                                <th class="px-6 py-4 font-semibold text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            <?php foreach($historyData as $row): ?>
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-700/30 transition">
                                <td class="px-6 py-4 font-medium"><?php echo date('F', mktime(0,0,0, $row['month'], 10)); ?></td>
                                <?php if($isAdmin): ?>
                                    <td class="px-6 py-4">
                                        <p><?php echo htmlspecialchars($row['full_name']); ?></p>
                                        <span class="text-xs text-neutral-500"><?php echo htmlspecialchars($row['device_id']); ?></span>
                                    </td>
                                <?php endif; ?>
                                <td class="px-6 py-4 text-right"><?php echo number_format($row['consumption'], 2); ?> m³</td>
                                <td class="px-6 py-4 text-right font-bold text-primary dark:text-blue-400">
                                    <?php echo number_format($row['cost'], 0); ?> ₫
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if($row['payment_status'] === 'paid'): ?>
                                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium border border-green-200">
                                            Paid
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs font-medium border border-yellow-200">
                                            Unpaid
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-neutral-500">
                                    <?php echo $row['paid_at'] ? date('d/m/Y H:i', strtotime($row['paid_at'])) : '--'; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if(!$isAdmin): ?>
                                        <a href="payment.php?view=bill&month=<?php echo $row['month']; ?>&year=<?php echo $row['year']; ?>" class="text-primary hover:underline text-xs">
                                            View Bill
                                        </a>
                                    <?php else: ?>
                                        <!-- Admin Actions can go here if needed, or redirect to bill view -->
                                        <a href="payment.php?view=bill&month=<?php echo $row['month']; ?>&year=<?php echo $row['year']; ?>" class="text-primary hover:underline text-xs">
                                            Manage
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- EXISTING BILL VIEW LOGIC (Admin & User) -->
        <?php if ($isAdmin): ?>
        <!-- ADMIN INTERFACE -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
                 <h2 class="text-lg font-bold">Invoices Management</h2>
                 <p class="text-sm text-neutral-500">Period: <?php echo "$selectedMonth/$selectedYear"; ?></p>
            </div>
            
            <?php if (empty($data)): ?>
                <div class="p-8 text-center text-neutral-500">No invoices found for this period.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 dark:bg-neutral-700/50 border-b border-neutral-200 dark:border-neutral-700">
                        <tr>
                            <th class="px-6 py-4 font-semibold">User / Device</th>
                            <th class="px-6 py-4 font-semibold text-right">Consumption</th>
                            <th class="px-6 py-4 font-semibold text-right">Amount (VND)</th>
                            <th class="px-6 py-4 font-semibold text-center">Status</th>
                            <th class="px-6 py-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        <?php foreach($data as $row): ?>
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-700/30 transition">
                            <td class="px-6 py-4">
                                <p class="font-medium"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                <p class="text-xs text-neutral-500">ID: <?php echo htmlspecialchars($row['device_id']); ?></p>
                            </td>
                            <td class="px-6 py-4 text-right"><?php echo number_format($row['consumption'], 2); ?> m³</td>
                            <td class="px-6 py-4 text-right font-bold text-primary dark:text-blue-400">
                                <?php echo number_format($row['cost'], 0); ?> ₫
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if($row['payment_status'] === 'paid'): ?>
                                    <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium border border-green-200">
                                        <span class="material-symbols-outlined text-sm">check_circle</span> Paid
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs font-medium border border-yellow-200">
                                        <span class="material-symbols-outlined text-sm">pending</span> Unpaid
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form method="POST">
                                    <input type="hidden" name="invoice_id" value="<?php echo $row['id']; ?>">
                                    <?php if($row['payment_status'] === 'paid'): ?>
                                        <button type="submit" name="action" value="mark_unpaid" class="text-xs text-neutral-500 hover:text-red-600 underline">
                                            Mark Unpaid
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="mark_paid" class="bg-primary text-white text-xs px-3 py-1.5 rounded hover:bg-blue-700 transition shadow-sm">
                                            Confirm Payment
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- USER INTERFACE -->
        <?php if (empty($data)): ?>
             <div class="bg-white dark:bg-neutral-800 p-8 rounded-xl border border-neutral-200 dark:border-neutral-700 text-center shadow-sm">
                 <span class="material-symbols-outlined text-4xl text-neutral-300 mb-2">receipt_long</span>
                 <p class="text-neutral-500">No invoice data available for <?php echo "$selectedMonth/$selectedYear"; ?>.</p>
             </div>
        <?php else: ?>
             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Bill Details -->
                <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-primary dark:text-blue-400">Water Bill</h2>
                            <span class="text-sm font-medium bg-neutral-100 dark:bg-neutral-700 px-2 py-1 rounded">
                                <?php echo date('F Y', mktime(0,0,0, $selectedMonth, 1, $selectedYear)); ?>
                            </span>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between py-2 border-b border-neutral-100 dark:border-neutral-700">
                                <span class="text-neutral-500">Device ID</span>
                                <span class="font-medium"><?php echo htmlspecialchars($data['device_id']); ?></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-neutral-100 dark:border-neutral-700">
                                <span class="text-neutral-500">Consumption</span>
                                <span class="font-medium"><?php echo number_format($data['consumption'], 2); ?> m³</span>
                            </div>
                            
                            <!-- Tier Breakdown -->
                            <div class="bg-neutral-50 dark:bg-neutral-700/30 rounded-lg p-3 text-sm space-y-1">
                                <?php if($data['tier_1_usage'] > 0) echo "<div class='flex justify-between'><span class='text-neutral-400'>Tier 1 (0-10)</span><span>{$data['tier_1_usage']} m³</span></div>"; ?>
                                <?php if($data['tier_2_usage'] > 0) echo "<div class='flex justify-between'><span class='text-neutral-400'>Tier 2 (10-20)</span><span>{$data['tier_2_usage']} m³</span></div>"; ?>
                                <?php if($data['tier_3_usage'] > 0) echo "<div class='flex justify-between'><span class='text-neutral-400'>Tier 3 (20-30)</span><span>{$data['tier_3_usage']} m³</span></div>"; ?>
                                <?php if($data['tier_4_usage'] > 0) echo "<div class='flex justify-between'><span class='text-neutral-400'>Tier 4 (>30)</span><span>{$data['tier_4_usage']} m³</span></div>"; ?>
                            </div>

                            <div class="flex justify-between items-end pt-4">
                                <span class="text-lg font-bold text-neutral-700 dark:text-neutral-300">Total Amount</span>
                                <span class="text-3xl font-extrabold text-primary dark:text-blue-400"><?php echo number_format($data['cost'], 0); ?> ₫</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-neutral-200 dark:border-neutral-700">
                        <div class="flex items-center gap-3">
                            <?php if($data['payment_status'] === 'paid'): ?>
                                <div class="h-10 w-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                    <span class="material-symbols-outlined">check</span>
                                </div>
                                <div>
                                    <p class="font-bold text-green-700 dark:text-green-400">Payment Successful</p>
                                    <p class="text-xs text-neutral-500">Paid on <?php echo $data['paid_at']; ?></p>
                                </div>
                            <?php else: ?>
                                <div class="h-10 w-10 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center">
                                    <span class="material-symbols-outlined">priority_high</span>
                                </div>
                                <div>
                                    <p class="font-bold text-yellow-700 dark:text-yellow-400">Payment Pending</p>
                                    <p class="text-xs text-neutral-500">Please scan QR code to pay</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment QR Section -->
                <?php if($data['payment_status'] === 'unpaid' && $data['cost'] > 0): ?>
                    <?php 
                        // Generate VietQR URL
                        $amount = intval($data['cost']);
                        $desc = "BILL " . $data['device_id'] . " M$selectedMonth";
                        $descEnc = urlencode($desc);
                        $accNameEnc = urlencode($ACCOUNT_NAME);
                        $qrUrl = "https://img.vietqr.io/image/{$BANK_ID}-{$ACCOUNT_NO}-{$TEMPLATE}.png?amount={$amount}&addInfo={$descEnc}&accountName={$accNameEnc}";
                    ?>
                    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm flex flex-col items-center justify-center text-center">
                        <h3 class="text-lg font-bold mb-4">Scan to Pay with VietQR</h3>
                        <div class="p-4 bg-white rounded-xl shadow-lg border border-neutral-100 mb-4">
                            <img src="<?php echo $qrUrl; ?>" alt="VietQR Payment" class="w-64 h-64 object-contain">
                        </div>
                        <p class="text-sm text-neutral-500 max-w-xs">
                             Use any banking app to scan this QR code.
                             <br>Transfer content: <b class="text-neutral-800 dark:text-neutral-200"><?php echo htmlspecialchars($desc); ?></b>
                        </p>
                        <p class="text-xs text-neutral-400 mt-4">
                            Note: After payment, please contact admin to update status.
                        </p>
                    </div>
                <?php elseif($data['cost'] == 0): ?>
                    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm flex flex-col items-center justify-center text-center">
                         <p class="text-neutral-500">No payment required for 0 consumption.</p>
                    </div>
                <?php endif; ?>
             </div>
        <?php endif; ?>

    <?php endif; /* End Bill View */ ?>
    <?php endif; /* End Main Logic */ ?>

</main>
</body>
</html>
