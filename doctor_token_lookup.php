<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['doctor']);

$today = date('Y-m-d');
$error = "";
$token = null;
$matched_patient = null;

// ---- Handle "Start Consultation" ----
if (isset($_GET['start']) && is_numeric($_GET['start'])) {
    $id = intval($_GET['start']);
    $token_num = isset($_GET['token']) ? intval($_GET['token']) : 0;

    $doctor_id = null;
    $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE full_name = ? LIMIT 1");
    $stmt->bind_param("s", $_SESSION['full_name']);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    if ($doc) $doctor_id = $doc['doctor_id'];

    if ($doctor_id) {
        $u = $conn->prepare("UPDATE tokens SET status='In Consultation', called_at=NOW(), doctor_id=? WHERE token_id=?");
        $u->bind_param("ii", $doctor_id, $id);
    } else {
        $u = $conn->prepare("UPDATE tokens SET status='In Consultation', called_at=NOW() WHERE token_id=?");
        $u->bind_param("i", $id);
    }
    $u->execute();
    header("Location: doctor_token_lookup.php?token=" . $token_num);
    exit();
}

// ---- Handle Token Lookup ----
if (isset($_GET['token']) && $_GET['token'] !== '') {
    $token_number = intval($_GET['token']);
    if ($token_number <= 0) {
        $error = "Please enter a valid token number.";
    } else {
        $stmt = $conn->prepare("SELECT t.*, d.full_name AS doctor_name 
                                FROM tokens t 
                                LEFT JOIN doctors d ON t.doctor_id = d.doctor_id 
                                WHERE t.token_number = ? AND t.token_date = ?");
        $stmt->bind_param("is", $token_number, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows == 0) {
            $error = "No token #" . str_pad($token_number, 3, '0', STR_PAD_LEFT) . " found for today.";
        } else {
            $token = $res->fetch_assoc();

            // Try to find matching patient record
            if ($token['phone_number']) {
                $p = $conn->prepare("SELECT * FROM patients WHERE full_name = ? AND contact_number = ? LIMIT 1");
                $p->bind_param("ss", $token['patient_name'], $token['phone_number']);
            } else {
                $p = $conn->prepare("SELECT * FROM patients WHERE full_name = ? LIMIT 1");
                $p->bind_param("s", $token['patient_name']);
            }
            $p->execute();
            $matched_patient = $p->get_result()->fetch_assoc();
        }
    }
}

// ---- Today's active queue for quick access ----
$active = $conn->query("SELECT t.*, d.full_name AS doctor_name 
                        FROM tokens t 
                        LEFT JOIN doctors d ON t.doctor_id=d.doctor_id 
                        WHERE t.token_date='$today' AND t.status IN ('Waiting','In Consultation') 
                        ORDER BY t.token_number ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Token Lookup - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
/* ============================================================
   Doctor Token Lookup — Clean Centered Layout
   ============================================================ */

* { box-sizing: border-box; }

body.doctor-lookup-page {
    background: linear-gradient(180deg, #f5f9f5 0%, #e8f5e9 100%);
    min-height: 100vh;
    margin: 0;
    padding: 0;
    font-family: 'Karla', sans-serif;
}

.doctor-lookup-topbar {
    background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
    color: #fff;
    padding: 16px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px solid #c9a227;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.doctor-lookup-topbar h2 {
    margin: 0;
    color: #fff;
    font-family: 'Zilla Slab', serif;
    font-size: 22px;
    font-weight: 700;
}
.doctor-lookup-topbar a {
    color: #fff;
    text-decoration: none;
    font-size: 14.5px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.35);
    padding: 8px 16px;
    border-radius: 8px;
    transition: 0.2s;
}
.doctor-lookup-topbar a:hover {
    background: rgba(255,255,255,0.28);
}

.lookup-wrap {
    max-width: 720px;
    margin: 50px auto;
    padding: 0 20px;
}

/* ============================================================
   SEARCH BAR — Fixed Overlap
   ============================================================ */

.search-hero {
    text-align: center;
    margin-bottom: 34px;
}
.search-hero .hero-icon {
    font-size: 52px;
    display: block;
    margin-bottom: 8px;
}
.search-hero h1 {
    font-family: 'Zilla Slab', serif;
    color: #1b5e20;
    font-size: 30px;
    font-weight: 700;
    margin: 0 0 8px;
}
.search-hero p {
    color: #5f6f68;
    font-size: 15px;
    margin: 0;
}

/* The search bar — single rounded pill */
.search-bar {
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 2px solid #c8e6c9;
    border-radius: 50px;
    padding: 6px 6px 6px 22px;
    box-shadow: 0 8px 26px rgba(27,94,32,0.10);
    transition: all 0.25s ease;
    max-width: 560px;
    margin: 0 auto;
    gap: 10px; /* Adds gap between input and button */
}
.search-bar:focus-within {
    border-color: #4caf50;
    box-shadow: 0 12px 32px rgba(76,175,80,0.22);
    transform: translateY(-2px);
}

.search-bar .search-icon {
    font-size: 20px;
    color: #a5b0aa;
    flex-shrink: 0;
    font-weight: 700;
}
.search-bar:focus-within .search-icon {
    color: #4caf50;
}

/* CRITICAL — override global input styles to fix shrinking */
.search-bar input[type="number"] {
    flex: 1 1 auto !important;
    width: 100% !important;
    min-width: 0 !important; /* Important for flex inputs */
    border: none !important;
    outline: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: 18px !important;
    font-weight: 600;
    font-family: 'Karla', sans-serif;
    color: #1b5e20;
    padding: 14px 0 !important;
    margin: 0 !important;
    letter-spacing: 0.5px;
}
.search-bar input[type="number"]::placeholder {
    color: #b0bbb4;
    font-weight: 500;
}
.search-bar input[type="number"]::-webkit-outer-spin-button,
.search-bar input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* CRITICAL — override global button styles */
.search-bar button[type="submit"],
.search-bar .search-button {
    flex: 0 0 auto !important;
    width: auto !important;
    margin: 0 !important;
    background: linear-gradient(135deg, #1b5e20 0%, #4caf50 100%) !important;
    color: #fff !important;
    border: none !important;
    border-radius: 50px !important;
    padding: 14px 30px !important;
    font-size: 15px !important;
    font-weight: 700;
    font-family: 'Karla', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 7px;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(27,94,32,0.25);
    line-height: 1;
}
.search-bar button[type="submit"]:hover,
.search-bar .search-button:hover {
    background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%) !important;
    box-shadow: 0 6px 18px rgba(27,94,32,0.35);
    transform: scale(1.03);
}

/* Error message */
.search-error-box {
    max-width: 560px;
    margin: 20px auto 0;
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
    border-left: 5px solid #c62828;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 14.5px;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: shake 0.4s ease-out;
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
}

/* Result Card */
.result-card {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 10px 35px rgba(27,94,32,0.12);
    border: 1px solid #dfece0;
    animation: fadeUp 0.4s ease-out;
    margin-bottom: 26px;
    margin-top: 40px;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}

.result-header {
    background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
    padding: 24px 30px;
    display: flex;
    align-items: center;
    gap: 22px;
    border-bottom: 3px solid #c9a227;
    flex-wrap: wrap;
}
.result-token-badge {
    background: linear-gradient(135deg, #1b5e20, #4caf50);
    color: #fff;
    font-family: 'Zilla Slab', serif;
    font-weight: 700;
    font-size: 32px;
    padding: 14px 24px;
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(27,94,32,0.3);
    letter-spacing: 2px;
}
.result-name-block { flex: 1; min-width: 200px; }
.result-name {
    font-family: 'Zilla Slab', serif;
    font-size: 24px;
    color: #1b5e20;
    font-weight: 700;
    margin-bottom: 6px;
}
.result-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 13.5px;
    color: #5f6f68;
    align-items: center;
}

.status-pill {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-pill.Waiting { background: #fff8e1; color: #8a6d1a; }
.status-pill.InConsultation { background: #e1f5fe; color: #0277bd; }
.status-pill.Completed { background: #e8f5e9; color: #2e7d32; }
.status-pill.Cancelled { background: #ffebee; color: #c62828; }

.result-body { padding: 24px 30px; }

.match-box {
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 14.5px;
    line-height: 1.5;
}
.match-box.success { background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20; }
.match-box.warn    { background: #fff8e1; border: 1px solid #ffe082; color: #8a6d1a; }
.match-box .match-icon {
    width: 32px; height: 32px; border-radius: 50%;
    flex-shrink: 0; display: flex; align-items: center;
    justify-content: center; font-weight: 700; font-size: 16px;
}
.match-box.success .match-icon { background: #4caf50; color: #fff; }
.match-box.warn .match-icon { background: #ffa000; color: #fff; }

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 6px;
}
.info-block {
    background: #f8fbf7;
    border: 1px solid #eef3ec;
    border-radius: 10px;
    padding: 14px 16px;
}
.info-block .label {
    display: block;
    font-size: 11px;
    color: #5f6f68;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    font-weight: 700;
    margin-bottom: 6px;
}
.info-block .value {
    font-size: 15.5px;
    color: #1b5e20;
    line-height: 1.6;
    word-break: break-word;
}
.info-block .value.empty {
    color: #a5b0aa;
    font-style: italic;
    font-size: 14px;
}

.result-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    padding: 20px 30px;
    background: #fafdfa;
    border-top: 1px solid #eef3ec;
}
.result-actions .btn {
    padding: 10px 18px;
    font-size: 14.5px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Active Queue */
.queue-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px 28px;
    box-shadow: 0 2px 14px rgba(27,94,32,0.07);
    border: 1px solid #eef3ec;
    margin-top: 30px;
}
.queue-card h4 {
    margin: 0 0 16px;
    color: #1b5e20;
    font-family: 'Zilla Slab', serif;
    font-size: 17px;
}
.queue-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.queue-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f1f8e9;
    border: 1.5px solid #c8e6c9;
    border-radius: 10px;
    padding: 10px 16px;
    text-decoration: none;
    color: #1b5e20;
    transition: 0.18s ease;
}
.queue-chip:hover {
    background: #1b5e20;
    color: #fff;
    border-color: #1b5e20;
    transform: translateY(-2px);
}
.queue-chip:hover .status-pill { background: rgba(255,255,255,0.25); color: #fff; }
.queue-chip .num {
    font-family: 'Zilla Slab', serif;
    font-weight: 700;
    font-size: 17px;
}
.queue-chip .name { font-size: 13.5px; opacity: 0.85; }
.queue-empty {
    color: #a5b0aa;
    font-size: 14px;
    font-style: italic;
    text-align: center;
    padding: 12px 0;
}
</style>
</head>
<body class="doctor-lookup-page">

<div class="doctor-lookup-topbar">
    <h2>ගම්පහ බෙහෙත් ශාලාව &mdash; Token Lookup</h2>
    <a href="dashboard.php">&larr; Back to Dashboard</a>
</div>

<div class="lookup-wrap">

    <!-- SEARCH HERO -->
    <div class="search-hero">
        <span class="hero-icon">🔍</span>
        <h1>Enter Token Number</h1>
        <p>Type the token number printed on the patient's slip.</p>
    </div>

    <!-- THE SEARCH BAR -->
    <form method="GET" action="doctor_token_lookup.php">
        <div class="search-bar">
            <span class="search-icon">#</span>
            <input
                type="number"
                name="token"
                placeholder="Enter token number..."
                min="1"
                value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>"
                required
                autofocus>
            <button type="submit" class="search-button">
                <span>🔍</span>
                <span>Search</span>
            </button>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="search-error-box">
            <span>⚠️</span>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- RESULT CARD -->
    <?php if ($token): ?>
    <div class="result-card">
        <div class="result-header">
            <div class="result-token-badge">
                #<?php echo str_pad($token['token_number'], 3, '0', STR_PAD_LEFT); ?>
            </div>
            <div class="result-name-block">
                <div class="result-name"><?php echo htmlspecialchars($token['patient_name']); ?></div>
                <div class="result-meta">
                    <span>🕒 <?php echo date('g:i A', strtotime($token['created_at'])); ?></span>
                    <?php if ($token['doctor_name']): ?>
                        <span>🩺 <?php echo htmlspecialchars($token['doctor_name']); ?></span>
                    <?php endif; ?>
                    <span class="status-pill <?php echo str_replace(' ', '', $token['status']); ?>">
                        <?php echo $token['status']; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="result-body">
            <?php if ($matched_patient): ?>
            <div class="match-box success">
                <div class="match-icon">✓</div>
                <div>
                    <b>Registered patient found</b><br>
                    This person already exists in patient records (ID #<?php echo $matched_patient['patient_id']; ?>).
                </div>
            </div>
            <?php else: ?>
            <div class="match-box warn">
                <div class="match-icon">!</div>
                <div>
                    <b>No matching patient record found.</b><br>
                    You'll need to register this patient before writing a prescription.
                </div>
            </div>
            <?php endif; ?>

            <div class="info-grid">
                <div class="info-block">
                    <span class="label">📞 Phone Number</span>
                    <div class="value <?php echo $token['phone_number'] ? '' : 'empty'; ?>">
                        <?php echo htmlspecialchars($token['phone_number'] ?: 'Not provided'); ?>
                    </div>
                </div>
                <div class="info-block">
                    <span class="label">🩺 Status</span>
                    <div class="value"><?php echo $token['status']; ?></div>
                </div>
                <div class="info-block" style="grid-column: 1 / -1;">
                    <span class="label">📋 Illness / Complaint</span>
                    <div class="value <?php echo $token['illness_details'] ? '' : 'empty'; ?>">
                        <?php echo htmlspecialchars($token['illness_details'] ?: 'No complaint recorded'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="result-actions">
            <?php if ($token['status'] == 'Waiting'): ?>
                <a href="doctor_token_lookup.php?token=<?php echo $token['token_number']; ?>&start=<?php echo $token['token_id']; ?>"
                   class="btn" style="background:#2980b9;">▶ Start Consultation</a>
            <?php endif; ?>

            <?php if ($matched_patient): ?>
                <a href="add_prescription.php?patient_id=<?php echo $matched_patient['patient_id']; ?>&token_id=<?php echo $token['token_id']; ?>"
                   class="btn" style="background:#2e7d32;">📝 Create Prescription</a>
                <a href="edit_patient.php?id=<?php echo $matched_patient['patient_id']; ?>"
                   class="btn" style="background:#607d8b;">👤 View Patient Record</a>
            <?php else: ?>
                <a href="add_patient.php?from_token=<?php echo $token['token_id']; ?>&name=<?php echo urlencode($token['patient_name']); ?>&phone=<?php echo urlencode($token['phone_number']); ?>"
                   class="btn" style="background:#c9a227; color:#1b5e20;">👤 Register Patient</a>
            <?php endif; ?>

            <a href="tokens.php" class="btn" style="background:#78909c;">📋 View Full Queue</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ACTIVE QUEUE -->
    <div class="queue-card">
        <h4>🎫 Today's Active Queue</h4>
        <?php if ($active && $active->num_rows > 0): ?>
            <div class="queue-grid">
                <?php while ($a = $active->fetch_assoc()): ?>
                    <a href="doctor_token_lookup.php?token=<?php echo $a['token_number']; ?>" class="queue-chip">
                        <span class="num">#<?php echo str_pad($a['token_number'], 3, '0', STR_PAD_LEFT); ?></span>
                        <span class="name"><?php echo htmlspecialchars($a['patient_name']); ?></span>
                        <span class="status-pill <?php echo str_replace(' ', '', $a['status']); ?>"
                              style="font-size:10.5px; padding:3px 9px;">
                            <?php echo $a['status']; ?>
                        </span>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="queue-empty">No active tokens in queue right now.</div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>