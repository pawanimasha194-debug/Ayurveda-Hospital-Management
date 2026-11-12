<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['reception']);

$error = "";
$issued_token = null;

$preselect_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_name    = trim($_POST['patient_name']);
    $phone_number    = trim($_POST['phone_number']);
    $illness_details = trim($_POST['illness_details']);
    $linked_patient_id = isset($_POST['linked_patient_id']) ? intval($_POST['linked_patient_id']) : 0;

    if ($patient_name == '') {
        $error = "Patient name is required.";
    } else {
        $today = date('Y-m-d');
        $r = $conn->query("SELECT COALESCE(MAX(token_number), 0) AS max_t FROM tokens WHERE token_date='$today'");
        $next = intval($r->fetch_assoc()['max_t']) + 1;

        $created_by = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO tokens (token_number, token_date, patient_name, phone_number, illness_details, status, created_by) VALUES (?, ?, ?, ?, ?, 'Waiting', ?)");
        $stmt->bind_param("issssi", $next, $today, $patient_name, $phone_number, $illness_details, $created_by);

        if ($stmt->execute()) {
            // රෝගියා සිස්ටම් එකේ ඉන්නවා නම්, එයාගේ Visit Count එක විතරක් වැඩි කරන්න (Type එක වෙනස් වෙන්නේ නෑ)
            if ($linked_patient_id > 0) {
                $conn->query("UPDATE patients SET visit_count = visit_count + 1, last_visit = '$today' WHERE patient_id = $linked_patient_id");
            }

            $issued_token = [
                'number'  => $next,
                'name'    => $patient_name,
                'phone'   => $phone_number,
                'illness' => $illness_details,
                'id'      => $conn->insert_id
            ];
        } else {
            $error = "Something went wrong. Try again.";
        }
    }
}

// Fetch all patients for the picker
$patients_list = [];
$pres = $conn->query("SELECT patient_id, full_name, contact_number, gender, patient_type, visit_count FROM patients ORDER BY full_name ASC");
if ($pres) {
    while ($p = $pres->fetch_assoc()) {
        $patients_list[] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Issue Token - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="reception-style.css">
<style>
.it-wrap { max-width: 720px; margin: 32px auto; padding: 0 20px; }
.it-hero { text-align: center; margin-bottom: 26px; }
.it-hero-icon { width: 68px; height: 68px; border-radius: 50%; background: linear-gradient(135deg, #c9a227, #d4a017); color: #fff; font-size: 34px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 10px 26px rgba(201,162,39,0.35); margin-bottom: 12px; }
.it-hero h1 { font-family: 'Zilla Slab', serif; font-size: 26px; color: #1b5e20; margin: 0 0 4px; }
.it-hero p { color: #5f6f68; font-size: 14px; margin: 0; }

.patient-picker { background: #fff; border: 2px dashed #c8e6c9; border-radius: 18px; padding: 24px 26px; margin-bottom: 22px; position: relative; transition: 0.25s ease; }
.patient-picker.active { border-style: solid; border-color: #4caf50; background: linear-gradient(180deg, #f8fbf7 0%, #ffffff 100%); box-shadow: 0 10px 26px rgba(27,94,32,0.08); }
.picker-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; gap: 12px; flex-wrap: wrap; }
.picker-head-left { display: flex; align-items: center; gap: 10px; }
.picker-head-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #1b5e20; font-size: 18px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.picker-title { font-family: 'Zilla Slab', serif; font-size: 17px; color: #1b5e20; font-weight: 700; margin: 0; }
.picker-sub { font-size: 12.5px; color: #5f6f68; margin: 2px 0 0; }
.picker-clear { background: transparent; color: #c62828; border: 1px solid #ef9a9a; padding: 6px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: 0.15s; font-family: 'Karla', sans-serif; }
.picker-clear:hover { background: #ffebee; }

.picker-search-wrap { position: relative; margin-bottom: 10px; margin-top: 5px; }
.picker-search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 16px; color: #a5b0aa; pointer-events: none; transition: 0.2s; z-index: 10; }
.picker-search { width: 100% !important; padding: 14px 16px 14px 45px !important; border: 1.5px solid #dfece0 !important; border-radius: 10px !important; font-size: 15px !important; font-family: 'Karla', sans-serif !important; color: #1b5e20 !important; background: #f8fbf7 !important; outline: none !important; transition: 0.2s !important; box-shadow: none !important; }
.picker-search:focus { border-color: #4caf50 !important; background: #fff !important; box-shadow: 0 0 0 4px rgba(76,175,80,0.12) !important; }
.picker-search-wrap:focus-within .picker-search-icon { color: #4caf50; }

.patient-list { max-height: 0; overflow-y: auto; transition: max-height 0.35s ease; }
.patient-list.open { max-height: 260px; border-top: 1px solid #eef3ec; padding-top: 8px; margin-top: 4px; }
.patient-item { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 10px; cursor: pointer; transition: 0.15s; margin-bottom: 4px; }
.patient-item:hover { background: #f1f8e9; }
.patient-item.selected { background: linear-gradient(135deg, #1b5e20, #2e7d32); color: #fff; }
.patient-avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-family: 'Zilla Slab', serif; font-size: 15px; flex-shrink: 0; background: #e8f5e9; color: #1b5e20; }
.patient-item.selected .patient-avatar { background: rgba(255,255,255,0.25); color: #fff; }
.patient-item-info { flex: 1; min-width: 0; }
.patient-item-name { font-weight: 600; font-size: 14.5px; color: #1b5e20; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.patient-item.selected .patient-item-name { color: #fff; }
.patient-item-meta { font-size: 12.5px; color: #5f6f68; }
.patient-item.selected .patient-item-meta { color: rgba(255,255,255,0.85); }

/* Badges for Regular / Temporary */
.badge-regular { background: #e8f5e9; color: #2e7d32; padding: 2px 8px; border-radius: 10px; font-size: 10.5px; font-weight: bold; border: 1px solid #a5d6a7; margin-left: 6px; }
.badge-new { background: #fff8e1; color: #8a6d1a; padding: 2px 8px; border-radius: 10px; font-size: 10.5px; font-weight: bold; border: 1px solid #ffe082; margin-left: 6px; }
.patient-item.selected .badge-regular, .patient-item.selected .badge-new { background: rgba(255,255,255,0.2); color: #fff; border-color: rgba(255,255,255,0.4); }

.patient-item-id { font-family: 'Zilla Slab', serif; font-weight: 700; font-size: 12px; background: rgba(27,94,32,0.1); color: #1b5e20; padding: 4px 9px; border-radius: 6px; flex-shrink: 0; }
.patient-item.selected .patient-item-id { background: rgba(255,255,255,0.2); color: #fff; }
.no-patient-msg { padding: 20px; text-align: center; color: #a5b0aa; font-size: 14px; font-style: italic; }

.autofill-badge { display: inline-flex; align-items: center; gap: 6px; background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; text-transform: uppercase; margin-left: 8px; opacity: 0; transform: scale(0.8); transition: all 0.3s ease; }
.autofill-badge.show { opacity: 1; transform: scale(1); }
.autofill-badge::before { content: '✓'; font-size: 12px; }

.issue-form { background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(27,94,32,0.08); padding: 28px 30px 32px; border: 1px solid #dfece0; }
.issue-form h3 { font-family: 'Zilla Slab', serif; color: #1b5e20; font-size: 18px; margin: 0 0 4px; }
.issue-form .form-sub { color: #5f6f68; font-size: 13px; margin: 0 0 20px; }
.issue-form label { display: block; font-size: 12.5px; font-weight: 700; color: #1b5e20; text-transform: uppercase; margin: 14px 0 6px; }
.issue-form label .req { color: #c62828; margin-left: 3px; }
.issue-form input[type="text"], .issue-form textarea { width: 100%; padding: 13px 16px; font-size: 15.5px; font-family: 'Karla', sans-serif; color: #1b5e20; background: #f8fbf7; border: 1.5px solid #dfece0; border-radius: 10px; outline: none; transition: all 0.25s ease; }
.issue-form input:focus, .issue-form textarea:focus { background: #fff; border-color: #4caf50; box-shadow: 0 0 0 4px rgba(76,175,80,0.12); transform: translateY(-1px); }
.issue-form textarea { resize: vertical; min-height: 90px; line-height: 1.5; }
.issue-form input::placeholder, .issue-form textarea::placeholder { color: #b0bbb4; }
.issue-form input.autofilled { animation: autofillPulse 0.9s ease-out; }
@keyframes autofillPulse { 0% { background: #fff8e1; border-color: #ffc107; box-shadow: 0 0 0 4px rgba(255,193,7,0.25); } 100% { background: #f8fbf7; border-color: #dfece0; box-shadow: none; } }
.issue-form button[type="submit"] { width: 100%; margin-top: 24px; padding: 15px; background: linear-gradient(135deg, #1b5e20 0%, #4caf50 100%); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 8px; }
.issue-form button[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(27,94,32,0.35); }
.issue-error { margin-top: 18px; background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; padding: 12px 16px; border-radius: 10px; font-size: 14px; }

.token-success { max-width: 620px; margin: 30px auto; background: #fff; border-radius: 18px; padding: 40px; text-align: center; box-shadow: 0 12px 40px rgba(27,94,32,0.15); border: 1px solid #dfece0; }
.success-check { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #4caf50, #2e7d32); color: #fff; font-size: 36px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
.token-success h2 { margin: 6px 0 20px; color: #1b5e20; font-family: 'Zilla Slab', serif; }
.big-token { background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 60%, #4caf50 100%); color: #fff; border-radius: 14px; padding: 24px 20px 28px; margin: 0 auto 24px; }
.big-token-label { font-size: 13px; letter-spacing: 3px; opacity: 0.85; margin-bottom: 6px; }
.big-token-number { font-family: 'Zilla Slab', serif; font-size: 72px; font-weight: 700; line-height: 1; }
.token-details { text-align: left; background: #f8fbf7; border-radius: 10px; padding: 16px 20px; margin-bottom: 22px; font-size: 15px; line-height: 1.9; border: 1px solid #dfece0; }
.token-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
</style>
</head>
<body class="reception-body">

<div class="topbar reception-topbar">
    <h2>🎫 Issue New Token</h2>
    <a href="reception_dashboard.php">&larr; Back to Dashboard</a>
</div>

<?php if ($issued_token): ?>
    <div class="token-success">
        <div class="success-check">✓</div>
        <h2>Token Issued Successfully!</h2>
        <div class="big-token">
            <div class="big-token-label">TOKEN NUMBER</div>
            <div class="big-token-number">#<?php echo str_pad($issued_token['number'], 3, '0', STR_PAD_LEFT); ?></div>
        </div>
        <div class="token-details">
            <div><b>Patient:</b> <?php echo htmlspecialchars($issued_token['name']); ?></div>
            <?php if ($issued_token['phone']): ?><div><b>Phone:</b> <?php echo htmlspecialchars($issued_token['phone']); ?></div><?php endif; ?>
            <?php if ($issued_token['illness']): ?><div><b>Complaint:</b> <?php echo htmlspecialchars($issued_token['illness']); ?></div><?php endif; ?>
            <div><b>Date & Time:</b> <?php echo date('d M Y, g:i A'); ?></div>
        </div>
        <div class="token-actions">
            <a href="print_token.php?id=<?php echo $issued_token['id']; ?>" class="btn" target="_blank">🖨 Print Slip</a>
            <a href="issue_token.php" class="btn btn-edit">+ Issue Another</a>
            <a href="reception_dashboard.php" class="btn" style="background:#607d8b;">Dashboard</a>
        </div>
    </div>
<?php else: ?>

<div class="it-wrap">
    <div class="it-hero">
        <div class="it-hero-icon">🎫</div>
        <h1>Issue New Token</h1>
        <p>Pick an existing patient or enter details manually.</p>
    </div>

    <div class="patient-picker <?php echo $preselect_id > 0 ? 'active' : ''; ?>" id="patientPicker">
        <div class="picker-head">
            <div class="picker-head-left">
                <div class="picker-head-icon">👥</div>
                <div>
                    <h3 class="picker-title">Select Existing Patient</h3>
                    <p class="picker-sub">Type a name to filter. Click a patient to auto-fill.</p>
                </div>
            </div>
            <button type="button" class="picker-clear" id="clearPatient" style="display:none;">✕ Clear</button>
        </div>

        <div class="picker-search-wrap">
            <span class="picker-search-icon">🔍</span>
            <input type="text" class="picker-search" id="patientSearch" placeholder="Search patient name or phone..." autocomplete="off">
        </div>

        <div class="patient-list" id="patientList">
            <?php if (count($patients_list) == 0): ?>
                <div class="no-patient-msg">No registered patients yet.</div>
            <?php else: foreach ($patients_list as $p):
                $initial = strtoupper(mb_substr($p['full_name'], 0, 1));
                $badge = ($p['patient_type'] == 'regular') ? '<span class="badge-regular">Regular</span>' : '<span class="badge-new">Temporary</span>';
                $prefix = ($p['patient_type'] == 'regular') ? 'R-' : 'T-';
            ?>
                <div class="patient-item"
                     data-id="<?php echo $p['patient_id']; ?>"
                     data-name="<?php echo htmlspecialchars($p['full_name']); ?>"
                     data-phone="<?php echo htmlspecialchars($p['contact_number']); ?>"
                     data-search="<?php echo strtolower(htmlspecialchars($p['full_name'] . ' ' . $p['contact_number'])); ?>">
                    <div class="patient-avatar"><?php echo $initial; ?></div>
                    <div class="patient-item-info">
                        <div class="patient-item-name">
                            <?php echo htmlspecialchars($p['full_name']); ?> <?php echo $badge; ?>
                        </div>
                        <div class="patient-item-meta"><?php echo htmlspecialchars($p['contact_number'] ?: 'No phone'); ?></div>
                    </div>
                    <!-- Patient ID with Prefix -->
                    <div class="patient-item-id">#<?php echo $prefix . str_pad($p['patient_id'], 4, '0', STR_PAD_LEFT); ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <form method="POST" class="issue-form" id="tokenForm">
        <h3>Patient Details</h3>
        <p class="form-sub">Auto-filled if you picked a patient above.</p>
        <input type="hidden" name="linked_patient_id" id="linkedPatientId" value="0">

        <label>Patient Full Name <span class="req">*</span> <span class="autofill-badge" id="nameBadge">Auto-filled</span></label>
        <input type="text" name="patient_name" id="patientName" placeholder="e.g. Nimal Perera" required autofocus>

        <label>Phone Number <span class="autofill-badge" id="phoneBadge">Auto-filled</span></label>
        <input type="text" name="phone_number" id="patientPhone" placeholder="e.g. 077 123 4567">

        <label>Illness / Complaint Details</label>
        <textarea name="illness_details" id="patientIllness" rows="4" placeholder="Describe the patient's complaint or symptoms..."></textarea>

        <button type="submit"><span>🎫</span><span>Generate Token</span></button>
        <?php if ($error): ?><div class="issue-error"><span>⚠️</span><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>
    </form>
</div>

<script>
const patientItems = document.querySelectorAll('.patient-item');
const searchInput = document.getElementById('patientSearch');
const patientList = document.getElementById('patientList');
const picker = document.getElementById('patientPicker');
const clearBtn = document.getElementById('clearPatient');
const nameInput = document.getElementById('patientName');
const phoneInput = document.getElementById('patientPhone');
const linkedInput = document.getElementById('linkedPatientId');
const nameBadge = document.getElementById('nameBadge');
const phoneBadge = document.getElementById('phoneBadge');
let selectedItem = null;

function selectPatient(item) {
    patientItems.forEach(i => i.classList.remove('selected'));
    item.classList.add('selected');
    selectedItem = item;
    nameInput.value = item.dataset.name;
    phoneInput.value = item.dataset.phone;
    linkedInput.value = item.dataset.id;
    [nameInput, phoneInput].forEach(inp => {
        inp.classList.remove('autofilled'); void inp.offsetWidth; inp.classList.add('autofilled');
    });
    nameBadge.classList.add('show');
    if (item.dataset.phone) phoneBadge.classList.add('show'); else phoneBadge.classList.remove('show');
    clearBtn.style.display = 'inline-block';
    picker.classList.add('active');
    patientList.classList.remove('open');
    searchInput.value = item.dataset.name;
}

function clearSelection() {
    patientItems.forEach(i => i.classList.remove('selected'));
    selectedItem = null; nameInput.value = ''; phoneInput.value = ''; linkedInput.value = '0'; searchInput.value = '';
    nameBadge.classList.remove('show'); phoneBadge.classList.remove('show'); clearBtn.style.display = 'none';
    picker.classList.remove('active'); patientList.classList.remove('open'); nameInput.focus();
}

patientItems.forEach(item => { item.addEventListener('click', () => selectPatient(item)); });
clearBtn.addEventListener('click', clearSelection);
searchInput.addEventListener('focus', () => patientList.classList.add('open'));
searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    patientList.classList.add('open');
    patientItems.forEach(item => { item.style.display = item.dataset.search.includes(q) ? '' : 'none'; });
    if (selectedItem && !selectedItem.dataset.search.includes(q)) {
        patientItems.forEach(i => i.classList.remove('selected'));
        selectedItem = null; linkedInput.value = '0'; nameBadge.classList.remove('show'); phoneBadge.classList.remove('show'); clearBtn.style.display = 'none';
    }
});
document.addEventListener('click', (e) => { if (!picker.contains(e.target)) patientList.classList.remove('open'); });
const preselectId = <?php echo $preselect_id; ?>;
if (preselectId > 0) { const target = document.querySelector(`.patient-item[data-id="${preselectId}"]`); if (target) setTimeout(() => selectPatient(target), 150); }
</script>

<?php endif; ?>
</body>
</html>