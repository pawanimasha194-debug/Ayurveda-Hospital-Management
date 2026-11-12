<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] === 'reception') {
    header("Location: reception_dashboard.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

$stats = [];
if ($role == 'admin' || $role == 'doctor') {
    $stats['patients'] = $conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
    $stats['doctors'] = $conn->query("SELECT COUNT(*) c FROM doctors")->fetch_assoc()['c'];
}
if ($role == 'admin' || $role == 'pharmacist') {
    $stats['low_stock'] = $conn->query("SELECT COUNT(*) c FROM medicines WHERE current_stock <= reorder_level")->fetch_assoc()['c'];
    $stats['expiring'] = $conn->query("SELECT COUNT(*) c FROM medicine_batches WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetch_assoc()['c'];
}
if ($role == 'admin' || $role == 'doctor' || $role == 'pharmacist') {
    $stats['pending_prescriptions'] = $conn->query("SELECT COUNT(*) c FROM prescriptions WHERE status IN ('Pending','Partially Issued')")->fetch_assoc()['c'];
}
if ($role == 'admin') {
    $stats['total_medicines'] = $conn->query("SELECT COUNT(*) c FROM medicines")->fetch_assoc()['c'];
}

$today = date('Y-m-d');
$today_active = 0;
$today_total = 0;
if ($role == 'doctor' || $role == 'admin') {
    $today_active = $conn->query("SELECT COUNT(*) c FROM tokens WHERE token_date='$today' AND status IN ('Waiting','In Consultation')")->fetch_assoc()['c'];
    $today_total  = $conn->query("SELECT COUNT(*) c FROM tokens WHERE token_date='$today'")->fetch_assoc()['c'];
}

$pending_issues_count = 0;
if ($role == 'pharmacist' || $role == 'admin') {
    $pending_issues_count = $conn->query("SELECT COUNT(DISTINCT p.prescription_id) c FROM prescriptions p JOIN prescription_details pd ON pd.prescription_id = p.prescription_id WHERE p.status IN ('Pending', 'Partially Issued') AND pd.dispense_type = 'inhouse'")->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
/* Dashboard Card Styles */
.dash-quick-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 18px; margin: 22px 0 26px; }
.dash-quick-card { position: relative; display: flex; align-items: center; gap: 18px; padding: 24px 26px; border-radius: 18px; text-decoration: none; color: #fff; overflow: hidden; transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s ease; box-shadow: 0 10px 26px rgba(0,0,0,0.14); min-height: 130px; isolation: isolate; }
.dash-quick-card::before { content: ''; position: absolute; inset: 0; background: linear-gradient(120deg, rgba(255,255,255,0.12) 0%, transparent 50%); opacity: 0; transition: opacity 0.3s ease; z-index: 1; }
.dash-quick-card:hover::before { opacity: 1; }
.dash-quick-card:hover { transform: translateY(-6px); box-shadow: 0 20px 42px rgba(0,0,0,0.22); }
.dash-quick-card .deco { position: absolute; border-radius: 50%; z-index: 0; pointer-events: none; }
.dash-quick-card .deco-1 { width: 160px; height: 160px; top: -60px; right: -50px; background: rgba(255,255,255,0.10); }
.dash-quick-card .deco-2 { width: 100px; height: 100px; bottom: -40px; left: -30px; background: rgba(255,255,255,0.07); }
.dash-quick-card .q-icon { width: 66px; height: 66px; border-radius: 16px; background: rgba(255,255,255,0.22); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; font-size: 32px; flex-shrink: 0; position: relative; z-index: 2; border: 1px solid rgba(255,255,255,0.35); transition: transform 0.35s ease; box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
.dash-quick-card:hover .q-icon { transform: rotate(-6deg) scale(1.08); }
.dash-quick-card .q-text { flex: 1; min-width: 0; position: relative; z-index: 2; }
.dash-quick-card .q-title { font-family: 'Zilla Slab', serif; font-size: 20px; font-weight: 700; color: #fff; margin: 0 0 4px; letter-spacing: 0.3px; }
.dash-quick-card .q-sub { font-size: 13px; color: rgba(255,255,255,0.88); margin: 0; line-height: 1.5; }
.dash-quick-card .q-arrow { width: 42px; height: 42px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff; flex-shrink: 0; position: relative; z-index: 2; transition: all 0.28s ease; border: 1px solid rgba(255,255,255,0.3); }
.dash-quick-card:hover .q-arrow { background: #fff; color: var(--q-accent, #1b5e20); transform: translateX(5px); }
.dash-quick-card .q-badge { position: absolute; top: 18px; right: 18px; background: rgba(255,255,255,0.95); color: var(--q-accent, #1b5e20); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; font-family: 'Zilla Slab', serif; z-index: 2; box-shadow: 0 4px 12px rgba(0,0,0,0.12); letter-spacing: 0.3px; }
.dash-quick-card .q-shimmer { position: absolute; top: 0; left: -60%; width: 45%; height: 100%; background: linear-gradient(115deg, transparent 0%, rgba(255,255,255,0.18) 45%, rgba(255,255,255,0.38) 50%, rgba(255,255,255,0.18) 55%, transparent 100%); transform: skewX(-20deg); z-index: 3; pointer-events: none; transition: left 0.8s ease; }
.dash-quick-card:hover .q-shimmer { left: 120%; }
.dash-quick-card.token-card { background: linear-gradient(135deg, #d4a017 0%, #c9a227 55%, #b8860b 100%); --q-accent: #8a6d1a; }
.dash-quick-card.token-card:hover { box-shadow: 0 20px 42px rgba(201,162,39,0.42); }
.dash-quick-card.rx-card { background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 55%, #4caf50 100%); --q-accent: #1b5e20; }
.dash-quick-card.rx-card:hover { box-shadow: 0 20px 42px rgba(27,94,32,0.42); }
.dash-quick-card.issue-card { background: linear-gradient(135deg, #0d47a1 0%, #1565c0 55%, #1e88e5 100%); --q-accent: #0d47a1; }
.dash-quick-card.issue-card:hover { box-shadow: 0 20px 42px rgba(21,101,192,0.45); }
.dash-quick-card.po-card { background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 55%, #ab47bc 100%); --q-accent: #6a1b9a; }
.dash-quick-card.po-card:hover { box-shadow: 0 20px 42px rgba(106,27,154,0.45); }
.dash-quick-card { animation: dashIn 0.5s ease-out both; }
.dash-quick-card:nth-child(1) { animation-delay: 0.05s; }
.dash-quick-card:nth-child(2) { animation-delay: 0.15s; }
.dash-quick-card:nth-child(3) { animation-delay: 0.25s; }
@keyframes dashIn { from { opacity: 0; transform: translateY(18px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
.dash-section-title { font-family: 'Zilla Slab', serif; color: var(--primary); font-size: 18px; margin: 4px 0 4px; display: flex; align-items: center; gap: 10px; }
.dash-section-title::before { content: ''; width: 4px; height: 20px; background: var(--gold); border-radius: 2px; }
.dash-section-sub { color: var(--ink-soft); font-size: 13.5px; margin: 0 0 14px 14px; }
</style>
</head>
<body class="dashboard-page">

<div class="topbar">
    <h2>ගම්පහ බෙහෙත් ශාලාව &mdash; Gampaha Beheth Sappuwa</h2>
    <div class="user-info">
        Welcome, <?php echo htmlspecialchars($full_name); ?> (<?php echo htmlspecialchars($role); ?>)
        
        <!-- THEME TOGGLE BUTTON -->
        <button class="theme-toggle-btn" id="themeToggle" title="Toggle Light/Dark Theme">🌙</button>

        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="app-shell">
    <div class="sidebar">
        <?php if ($role == 'admin' || $role == 'doctor'): ?>
        <a href="patients.php"><span class="icon">🧑‍🤝‍🧑</span> Patients</a>
        <a href="doctors.php"><span class="icon">🩺</span> Doctors</a>
        <a href="doctor_token_lookup.php"><span class="icon">🎫</span> Token Lookup</a>
        <?php endif; ?>
        <?php if ($role == 'admin' || $role == 'pharmacist'): ?>
        <a href="medicines.php"><span class="icon">💊</span> Medicines & Stock</a>
        <a href="suppliers.php"><span class="icon">🚚</span> Suppliers</a>
        <a href="receive_medicine.php"><span class="icon">📥</span> Receive Medicines</a>
        <a href="purchase_orders.php"><span class="icon">🛒</span> Purchase Orders</a>
        <a href="low_stock_order_list.php"><span class="icon">⚡</span> Auto Order List</a>
        <?php endif; ?>
        <?php if ($role == 'admin' || $role == 'doctor' || $role == 'pharmacist'): ?>
        <a href="prescriptions.php"><span class="icon">📝</span> Prescriptions</a>
        <?php endif; ?>
        <?php if ($role == 'admin' || $role == 'pharmacist'): ?>
        <a href="pending_issues.php"><span class="icon">📤</span> Issue Medicines</a>
        <?php endif; ?>
        <?php if ($role == 'admin' || $role == 'reception'): ?>
        <a href="tokens.php"><span class="icon">🎟️</span> Token Queue</a>
        <?php endif; ?>
        <a href="reports.php"><span class="icon">📊</span> Reports</a>
        <?php if ($role == 'admin'): ?>
        <a href="manage_users.php"><span class="icon">👤</span> Manage Users</a>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <h3>Dashboard</h3>
        <div class="welcome-panel">
            <p>Welcome back, <b><?php echo htmlspecialchars($full_name); ?></b>. Use the menu on the left to manage <?php echo ($role == 'doctor') ? 'patients, doctors, and prescriptions.' : (($role == 'pharmacist') ? 'medicine stock, suppliers, and prescription issuing.' : 'every part of the hospital system.'); ?></p>
        </div>

        <?php if ($role == 'doctor' || $role == 'admin'): ?>
        <h4 class="dash-section-title" style="margin-top: 26px;">Fast Access</h4>
        <p class="dash-section-sub">Jump straight to what you need</p>
        <div class="dash-quick-row">
            <a href="doctor_token_lookup.php" class="dash-quick-card token-card">
                <span class="q-shimmer"></span><span class="deco deco-1"></span><span class="deco deco-2"></span>
                <?php if ($today_active > 0): ?><span class="q-badge"><?php echo $today_active; ?> waiting</span><?php endif; ?>
                <div class="q-icon">🎫</div>
                <div class="q-text"><div class="q-title">Token Lookup</div><p class="q-sub">Enter a patient's token number to view their details and start the consultation.</p></div>
                <div class="q-arrow">→</div>
            </a>
            <a href="prescriptions.php" class="dash-quick-card rx-card">
                <span class="q-shimmer"></span><span class="deco deco-1"></span><span class="deco deco-2"></span>
                <?php if (isset($stats['pending_prescriptions']) && $stats['pending_prescriptions'] > 0): ?><span class="q-badge"><?php echo $stats['pending_prescriptions']; ?> pending</span><?php endif; ?>
                <div class="q-icon">📝</div>
                <div class="q-text"><div class="q-title">Prescriptions</div><p class="q-sub">View all prescriptions, write new ones, or track what's pending at the pharmacy.</p></div>
                <div class="q-arrow">→</div>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($role == 'pharmacist'): ?>
        <h4 class="dash-section-title" style="margin-top: 26px;">Fast Access</h4>
        <p class="dash-section-sub">Jump straight to what you need</p>
        <div class="dash-quick-row">
            <a href="pending_issues.php" class="dash-quick-card issue-card">
                <span class="q-shimmer"></span><span class="deco deco-1"></span><span class="deco deco-2"></span>
                <?php if ($pending_issues_count > 0): ?><span class="q-badge"><?php echo $pending_issues_count; ?> pending</span><?php endif; ?>
                <div class="q-icon">📤</div>
                <div class="q-text"><div class="q-title">Issue Medicines</div><p class="q-sub">See prescriptions waiting for in-house medicines and issue them to patients.</p></div>
                <div class="q-arrow">→</div>
            </a>
            <a href="prescriptions.php" class="dash-quick-card rx-card">
                <span class="q-shimmer"></span><span class="deco deco-1"></span><span class="deco deco-2"></span>
                <?php if (isset($stats['pending_prescriptions']) && $stats['pending_prescriptions'] > 0): ?><span class="q-badge"><?php echo $stats['pending_prescriptions']; ?> total</span><?php endif; ?>
                <div class="q-icon">📝</div>
                <div class="q-text"><div class="q-title">Prescriptions</div><p class="q-sub">Browse all prescriptions, view external pharmacy slips, and track history.</p></div>
                <div class="q-arrow">→</div>
            </a>
            <a href="purchase_orders.php" class="dash-quick-card po-card">
                <span class="q-shimmer"></span><span class="deco deco-1"></span><span class="deco deco-2"></span>
                <div class="q-icon">🛒</div>
                <div class="q-text"><div class="q-title">Purchase Orders</div><p class="q-sub">Create and print purchase orders to request new stock from your suppliers.</p></div>
                <div class="q-arrow">→</div>
            </a>
        </div>
        <?php endif; ?>

        <div class="summary-row" style="margin-top: 22px;">
            <?php if (isset($stats['patients'])): ?>
            <a href="patients.php" class="summary-card" style="text-decoration:none; display:block;"><div class="value"><?php echo $stats['patients']; ?></div><div class="label">Total Patients</div></a>
            <a href="doctors.php" class="summary-card" style="text-decoration:none; display:block;"><div class="value"><?php echo $stats['doctors']; ?></div><div class="label">Total Doctors</div></a>
            <?php endif; ?>
            <?php if (isset($stats['pending_prescriptions'])): ?>
            <a href="prescriptions.php" class="summary-card" style="text-decoration:none; display:block;"><div class="value"><?php echo $stats['pending_prescriptions']; ?></div><div class="label">Pending Prescriptions</div></a>
            <?php endif; ?>
            <?php if (isset($stats['low_stock'])): ?>
            <a href="medicines.php" class="summary-card" style="text-decoration:none; display:block; <?php echo $stats['low_stock'] > 0 ? 'border-left-color: var(--danger);' : ''; ?>"><div class="value" style="<?php echo $stats['low_stock'] > 0 ? 'color: var(--danger);' : ''; ?>"><?php echo $stats['low_stock']; ?></div><div class="label">Low Stock Medicines</div></a>
            <a href="reports.php" class="summary-card" style="text-decoration:none; display:block; <?php echo $stats['expiring'] > 0 ? 'border-left-color: var(--gold);' : ''; ?>"><div class="value"><?php echo $stats['expiring']; ?></div><div class="label">Batches Expiring Soon</div></a>
            <?php endif; ?>
            <?php if (isset($stats['total_medicines'])): ?>
            <a href="medicines.php" class="summary-card" style="text-decoration:none; display:block;"><div class="value"><?php echo $stats['total_medicines']; ?></div><div class="label">Medicines Tracked</div></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- THEME TOGGLE SCRIPT -->
<script>
    const themeToggleBtn = document.getElementById('themeToggle');
    const currentTheme = localStorage.getItem('theme') || 'light';

    // Set initial theme
    if (currentTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        themeToggleBtn.textContent = '☀️'; // Sun icon for switching back to light
    }

    // Toggle theme
    themeToggleBtn.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme');
        if (theme === 'dark') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            themeToggleBtn.textContent = '🌙';
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            themeToggleBtn.textContent = '☀️';
        }
    });
</script>

</body>
</html>