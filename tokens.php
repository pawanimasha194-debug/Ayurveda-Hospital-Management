<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['reception']);

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action == 'cancel') {
        $conn->query("UPDATE tokens SET status='Cancelled' WHERE token_id=$id");
    } elseif ($action == 'complete') {
        $conn->query("UPDATE tokens SET status='Completed', completed_at=NOW() WHERE token_id=$id");
    } elseif ($action == 'consult') {
        $conn->query("UPDATE tokens SET status='In Consultation', called_at=NOW() WHERE token_id=$id");
    }
    header("Location: tokens.php?status=" . urlencode($_GET['status'] ?? 'all'));
    exit();
}

$today = date('Y-m-d');
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT t.*, d.full_name AS doctor_name FROM tokens t LEFT JOIN doctors d ON t.doctor_id = d.doctor_id WHERE t.token_date = '$today'";
if ($filter !== 'all') {
    $safe = $conn->real_escape_string($filter);
    $sql .= " AND t.status = '$safe'";
}
$sql .= " ORDER BY t.token_number ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Token Queue - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="reception-style.css">
<style>
.tokens-wrapper { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(27,94,32,0.08); padding: 24px 26px; border: 1px solid #dfece0; }
.filter-tabs { display: flex; gap: 10px; margin-bottom: 22px; flex-wrap: wrap; border-bottom: 1px solid #eef3ec; padding-bottom: 16px; }
.filter-tabs a { padding: 8px 18px; background: #f8fbf7; border: 1px solid #dfece0; border-radius: 20px; text-decoration: none; color: #5f6f68; font-size: 14px; font-weight: 600; transition: all 0.2s ease; }
.filter-tabs a:hover { border-color: #4caf50; color: #1b5e20; background: #fff; }
.filter-tabs a.active { background: #1b5e20; color: #fff; border-color: #1b5e20; box-shadow: 0 4px 10px rgba(27,94,32,0.2); }
.tokens-table { width: 100%; border-collapse: collapse; }
.tokens-table th { background: #f1f8e9; color: #1b5e20; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 12px; text-align: left; border-bottom: 2px solid #c8e6c9; font-weight: 700; }
.tokens-table td { padding: 14px 12px; border-bottom: 1px solid #eef3ec; font-size: 14.5px; color: #263238; vertical-align: middle; }
.tokens-table tr:hover { background: #fafdfa; }
.tokens-table tr:last-child td { border-bottom: none; }
.rec-status { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.rec-status-Waiting { background: #fff8e1; color: #8a6d1a; }
.rec-status-InConsultation { background: #e1f5fe; color: #0277bd; }
.rec-status-Completed { background: #e8f5e9; color: #2e7d32; }
.rec-status-Cancelled { background: #ffebee; color: #c62828; }
.tokens-table td.actions { display: flex; gap: 8px; }
.tokens-table td.actions a { padding: 6px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 600; text-decoration: none; transition: 0.2s; }
.btn-call { background: #2980b9; color: #fff; }
.btn-call:hover { background: #1c5980; }
.btn-complete { background: #2e7d32; color: #fff; }
.btn-complete:hover { background: #1b5e20; }
.btn-cancel { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
.btn-cancel:hover { background: #c62828; color: #fff; }
.btn-slip { background: #607d8b; color: #fff; }
.btn-slip:hover { background: #455a64; }
.empty-state { text-align: center; padding: 50px 20px; color: #a5b0aa; }
.empty-state .icon { font-size: 40px; margin-bottom: 10px; display: block; opacity: 0.5; }
.token-chip { background: #1b5e20; color: #fff; padding: 5px 12px; border-radius: 6px; font-family: 'Zilla Slab', serif; font-weight: 700; font-size: 15px; display: inline-block; }
.complaint-cell { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>
</head>
<body class="reception-body">

<div class="topbar reception-topbar">
    <!-- SINHALA NAMA ADD KARA -->
    <h2>ගම්පහ බෙහෙත් ශාලාව &mdash; Token Queue</h2>
    <a href="reception_dashboard.php">&larr; Back to Dashboard</a>
</div>

<div class="reception-shell">
    <div class="tokens-wrapper">
        <div class="filter-tabs">
            <a href="?status=all" class="<?php echo $filter=='all'?'active':''; ?>">All Tokens</a>
            <a href="?status=Waiting" class="<?php echo $filter=='Waiting'?'active':''; ?>">Waiting</a>
            <a href="?status=In Consultation" class="<?php echo $filter=='In Consultation'?'active':''; ?>">In Consultation</a>
            <a href="?status=Completed" class="<?php echo $filter=='Completed'?'active':''; ?>">Completed</a>
            <a href="?status=Cancelled" class="<?php echo $filter=='Cancelled'?'active':''; ?>">Cancelled</a>
        </div>

        <table class="tokens-table">
            <thead>
                <tr>
                    <th width="80">Token</th>
                    <th>Patient Name</th>
                    <th>Phone</th>
                    <th>Complaint</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows == 0): ?>
                    <tr>
                        <td colspan="7"><div class="empty-state"><span class="icon">📭</span>No tokens found for this filter.</div></td>
                    </tr>
                <?php else: while ($r = $result->fetch_assoc()): ?>
                <tr>
                    <td><span class="token-chip">#<?php echo str_pad($r['token_number'], 3, '0', STR_PAD_LEFT); ?></span></td>
                    <td><b><?php echo htmlspecialchars($r['patient_name']); ?></b></td>
                    <td><?php echo htmlspecialchars($r['phone_number'] ?: '-'); ?></td>
                    <td class="complaint-cell" title="<?php echo htmlspecialchars($r['illness_details']); ?>"><?php echo htmlspecialchars($r['illness_details'] ?: '-'); ?></td>
                    <td><?php echo date('g:i A', strtotime($r['created_at'])); ?></td>
                    <td><span class="rec-status rec-status-<?php echo str_replace(' ', '', $r['status']); ?>"><?php echo $r['status']; ?></span></td>
                    <td class="actions">
                        <?php if ($r['status'] == 'Waiting'): ?>
                            <a href="?action=consult&id=<?php echo $r['token_id']; ?>&status=<?php echo urlencode($filter); ?>" class="btn-call">Call In</a>
                            <a href="?action=cancel&id=<?php echo $r['token_id']; ?>&status=<?php echo urlencode($filter); ?>" class="btn-cancel" onclick="return confirm('Cancel this token?');">Cancel</a>
                        <?php elseif ($r['status'] == 'In Consultation'): ?>
                            <a href="?action=complete&id=<?php echo $r['token_id']; ?>&status=<?php echo urlencode($filter); ?>" class="btn-complete">Complete</a>
                        <?php else: ?>
                            <a href="print_token.php?id=<?php echo $r['token_id']; ?>" class="btn-slip" target="_blank">Print Slip</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>