<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['reception']);

$today = date('Y-m-d');

$stats['total_today'] = $conn->query("SELECT COUNT(*) c FROM tokens WHERE token_date='$today'")->fetch_assoc()['c'];
$stats['waiting']     = $conn->query("SELECT COUNT(*) c FROM tokens WHERE token_date='$today' AND status='Waiting'")->fetch_assoc()['c'];
$stats['in_consult']  = $conn->query("SELECT COUNT(*) c FROM tokens WHERE token_date='$today' AND status='In Consultation'")->fetch_assoc()['c'];
$stats['completed']   = $conn->query("SELECT COUNT(*) c FROM tokens WHERE token_date='$today' AND status='Completed'")->fetch_assoc()['c'];

$serving = $conn->query("SELECT t.*, d.full_name AS doctor_name FROM tokens t LEFT JOIN doctors d ON t.doctor_id=d.doctor_id WHERE t.token_date='$today' AND t.status='In Consultation' ORDER BY t.called_at DESC LIMIT 5");
$recent  = $conn->query("SELECT * FROM tokens WHERE token_date='$today' ORDER BY token_id DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reception Dashboard - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="reception-style.css">
<style>
.reception-app-shell { display: flex; min-height: calc(100vh - 70px); }
.reception-sidebar { width: 230px; background: #1b5e20; padding: 22px 0; flex-shrink: 0; }
.reception-sidebar a { display: flex; align-items: center; gap: 10px; color: #ffffff; text-decoration: none; padding: 14px 24px; font-size: 15.5px; font-weight: 600; border-left: 4px solid transparent; transition: 0.2s; }
.reception-sidebar a .icon { font-size: 18px; }
.reception-sidebar a:hover, .reception-sidebar a.active { background: #4caf50; border-left-color: #c9a227; }
.reception-main { flex: 1; padding: 30px; background: rgba(245, 249, 245, 0.85); }
.hero-cards { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }

/* Cleanup Button */
.cleanup-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; color: #c62828; border: 1.5px solid #ef9a9a; padding: 10px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; margin-top: 20px; }
.cleanup-btn:hover { background: #ffebee; border-color: #c62828; }
</style>
</head>
<body class="reception-body">

<div class="topbar reception-topbar">
    <h2>ගම්පහ බෙහෙත් ශාලාව &mdash; Reception Desk</h2>
    <div class="user-info">
        Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="reception-app-shell">
    <div class="reception-sidebar">
        <a href="reception_dashboard.php" class="active"><span class="icon">📊</span> Dashboard</a>
        <a href="issue_token.php"><span class="icon">🎫</span> Issue Token</a>
        <a href="reception_register_patient.php"><span class="icon">👤</span> Register Patient</a>
        <a href="tokens.php"><span class="icon">📋</span> Token Queue</a>
        <a href="reception_bills.php"><span class="icon">🧾</span> Print Bills</a>
    </div>

    <div class="reception-main">
        <h3 class="rec-heading">Good day, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?> 🌿</h3>
        <p class="rec-sub">Manage incoming patients and the daily queue.</p>

        <h4 class="action-section-title">Quick Actions</h4>
        <p class="action-section-sub">Choose what you'd like to do</p>

        <div class="hero-cards">
            <a href="issue_token.php" class="hero-card issue">
                <span class="shimmer"></span><span class="deco deco-1"></span><span class="deco deco-2"></span><span class="deco deco-3"></span>
                <span class="hero-badge"><?php echo $stats['total_today']; ?> today</span>
                <div class="hero-icon">🎫</div>
                <div class="hero-title">Issue New Token</div>
                <div class="hero-sub">Register a new incoming patient and generate a queue token.</div>
                <div class="hero-arrow">→</div>
            </a>

            <a href="reception_register_patient.php" class="hero-card register">
                <span class="shimmer"></span><span class="deco deco-1"></span><span class="deco deco-2"></span><span class="deco deco-3"></span>
                <div class="hero-icon">👤</div>
                <div class="hero-title">Register Patient</div>
                <div class="hero-sub">Add a new patient profile with full details to hospital records.</div>
                <div class="hero-arrow">→</div>
            </a>

            <a href="tokens.php" class="hero-card view">
                <span class="shimmer"></span><span class="deco deco-1"></span><span class="deco deco-2"></span><span class="deco deco-3"></span>
                <span class="hero-badge"><?php echo $stats['waiting']; ?> waiting</span>
                <div class="hero-icon">📋</div>
                <div class="hero-title">View All Tokens</div>
                <div class="hero-sub">See the full patient queue for today and update statuses.</div>
                <div class="hero-arrow">→</div>
            </a>

            <a href="reception_bills.php" class="hero-card" style="background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 50%, #4caf50 100%); --card-accent: #1b5e20;">
                <span class="shimmer"></span><span class="deco deco-1"></span><span class="deco deco-2"></span><span class="deco deco-3"></span>
                <div class="hero-icon">🧾</div>
                <div class="hero-title">Print Bills</div>
                <div class="hero-sub">Print patient invoices including consultation fees and medicines.</div>
                <div class="hero-arrow">→</div>
            </a>
        </div>

        <div class="rec-stats-row" style="margin-top: 30px;">
            <div class="rec-stat-card total"><div class="rec-stat-value"><?php echo $stats['total_today']; ?></div><div class="rec-stat-label">Tokens Today</div></div>
            <div class="rec-stat-card waiting"><div class="rec-stat-value"><?php echo $stats['waiting']; ?></div><div class="rec-stat-label">Waiting</div></div>
            <div class="rec-stat-card serving"><div class="rec-stat-value"><?php echo $stats['in_consult']; ?></div><div class="rec-stat-label">In Consultation</div></div>
            <div class="rec-stat-card done"><div class="rec-stat-value"><?php echo $stats['completed']; ?></div><div class="rec-stat-label">Completed</div></div>
        </div>

        <?php if ($serving->num_rows > 0): ?>
        <div class="now-serving-box">
            <div class="now-serving-label">🟢 Now Serving</div>
            <div class="now-serving-list">
                <?php while ($s = $serving->fetch_assoc()): ?>
                <div class="now-serving-item">
                    <div class="token-mini">#<?php echo str_pad($s['token_number'], 3, '0', STR_PAD_LEFT); ?></div>
                    <div class="now-serving-info"><b><?php echo htmlspecialchars($s['patient_name']); ?></b><span><?php echo htmlspecialchars($s['doctor_name'] ?? 'Awaiting doctor'); ?></span></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="rec-recent">
            <h4>Recent Tokens</h4>
            <table class="rec-table">
                <tr><th>Token</th><th>Patient</th><th>Phone</th><th>Status</th><th>Time</th></tr>
                <?php if ($recent->num_rows == 0): ?>
                    <tr><td colspan="5" style="text-align:center;color:#888;">No tokens issued yet today.</td></tr>
                <?php else: while ($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td><span class="token-chip">#<?php echo str_pad($r['token_number'], 3, '0', STR_PAD_LEFT); ?></span></td>
                    <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['phone_number']); ?></td>
                    <td><span class="rec-status rec-status-<?php echo str_replace(' ', '', $r['status']); ?>"><?php echo $r['status']; ?></span></td>
                    <td><?php echo date('g:i A', strtotime($r['created_at'])); ?></td>
                </tr>
                <?php endwhile; endif; ?>
            </table>
            
            <a href="cleanup_patients.php" class="cleanup-btn" onclick="return confirm('Are you sure you want to remove temporary patients who haven\'t visited in 1 year?');">
                🗑️ Cleanup Old Temporary Patients (1 Year+)
            </a>
        </div>
    </div>
</div>

</body>
</html>