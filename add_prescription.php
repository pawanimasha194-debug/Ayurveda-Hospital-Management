<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['doctor']);

$error = "";

$pre_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$from_token_id  = isset($_GET['token_id']) ? intval($_GET['token_id']) 
                  : (isset($_POST['from_token_id']) ? intval($_POST['from_token_id']) : 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $prescription_date = $_POST['prescription_date'];
    $notes = trim($_POST['notes']);
    $medicine_ids = $_POST['medicine_id'] ?? [];
    $quantities = $_POST['prescribed_quantity'] ?? [];
    $dosages = $_POST['dosage_instructions'] ?? [];
    $dispense_types = $_POST['dispense_type'] ?? [];
    $external_notes = $_POST['external_note'] ?? [];

    $valid_rows = [];
    for ($i = 0; $i < count($medicine_ids); $i++) {
        if ($medicine_ids[$i] == '') continue;
        $valid_rows[] = [
            'med_id' => intval($medicine_ids[$i]),
            'qty'    => intval($quantities[$i] ?? 0),
            'dosage' => trim($dosages[$i] ?? ''),
            'dtype'  => ($dispense_types[$i] ?? 'inhouse') === 'external' ? 'external' : 'inhouse',
            'enote'  => trim($external_notes[$i] ?? ''),
        ];
    }

    if ($patient_id == 0 || $doctor_id == 0 || count($valid_rows) == 0) {
        $error = "Please select patient, doctor, and at least one medicine.";
    } else {
        $conn->begin_transaction();
        try {
            // ALUTH: token_id එකත් INSERT කරනවා
            $stmt = $conn->prepare("INSERT INTO prescriptions (token_id, patient_id, doctor_id, prescription_date, notes, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("iiiss", $from_token_id, $patient_id, $doctor_id, $prescription_date, $notes);
            $stmt->execute();
            $prescription_id = $conn->insert_id;

            $stmt2 = $conn->prepare("INSERT INTO prescription_details (prescription_id, medicine_id, prescribed_quantity, dosage_instructions, dispense_type, external_note) VALUES (?, ?, ?, ?, ?, ?)");
            $has_inhouse = false;
            foreach ($valid_rows as $r) {
                if ($r['dtype'] === 'inhouse') $has_inhouse = true;
                $enote = ($r['dtype'] === 'external') ? $r['enote'] : '';
                $stmt2->bind_param("iiisss", $prescription_id, $r['med_id'], $r['qty'], $r['dosage'], $r['dtype'], $enote);
                $stmt2->execute();
            }

            if (!$has_inhouse) {
                $conn->query("UPDATE prescriptions SET status='Issued' WHERE prescription_id=" . intval($prescription_id));
            }

            $conn->commit();

            // ටෝකන් එක COMPLETED කිරීම
            if ($from_token_id > 0) {
                $conn->query("UPDATE tokens SET status='Completed', completed_at=NOW() WHERE token_id=" . intval($from_token_id));
            }

            header("Location: view_prescription.php?id=" . $prescription_id);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Something went wrong. Try again.";
        }
    }
}

$patients = $conn->query("SELECT patient_id, full_name FROM patients ORDER BY full_name");
$doctors = $conn->query("SELECT doctor_id, full_name FROM doctors ORDER BY full_name");
$medicines = $conn->query("SELECT medicine_id, medicine_name, medicine_name_si FROM medicines ORDER BY medicine_name");

$medicineOptions = "<option value=''>-- Select Medicine --</option>";
while ($m = $medicines->fetch_assoc()) {
    $si_name = !empty($m['medicine_name_si']) ? " (" . $m['medicine_name_si'] . ")" : "";
    $medicineOptions .= "<option value='{$m['medicine_id']}'>" . htmlspecialchars($m['medicine_name'] . $si_name) . "</option>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Prescription - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.med-row { display: grid; grid-template-columns: 2fr 0.7fr 1.8fr auto; gap: 8px; margin-bottom: 8px; align-items: center; }
@media (max-width: 820px) { .med-row { grid-template-columns: 1fr; } }
.med-row-wrapper { background: #fffdf5; border: 1px solid #ffe082; border-radius: 12px; padding: 12px 14px 10px; margin-bottom: 12px; }
.med-row-wrapper .med-row { margin-bottom: 8px; }
.external-note-wrap { display: flex; align-items: center; gap: 8px; background: #fff8e1; border: 1.5px solid #ffd54f; border-radius: 9px; padding: 6px 12px; transition: 0.2s ease; }
.external-note-wrap:focus-within { border-color: #c9a227; box-shadow: 0 0 0 4px rgba(201,162,39,0.15); background: #fffdf5; }
.external-note-wrap .note-icon { font-size: 15px; flex-shrink: 0; }
.external-note-wrap input.external-note-input { flex: 1; border: none !important; outline: none !important; background: transparent !important; padding: 7px 0 !important; font-size: 13.5px !important; font-family: 'Karla', sans-serif; color: #8a6d1a !important; box-shadow: none !important; width: auto !important; margin: 0 !important; }
.external-note-wrap input.external-note-input::placeholder { color: #c9a227; opacity: 0.75; font-style: italic; }
.rx-tabs { display: flex; gap: 8px; margin: 8px 0 18px; padding: 6px; background: #f1f8e9; border-radius: 14px; border: 1px solid #dfece0; }
.rx-tab { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px 18px; background: transparent; border: none; border-radius: 10px; font-family: 'Karla', sans-serif; font-size: 14.5px; font-weight: 700; color: #5f6f68; cursor: pointer; transition: all 0.22s ease; letter-spacing: 0.3px; }
.rx-tab:hover { color: #1b5e20; }
.rx-tab.active { background: #ffffff; color: #1b5e20; box-shadow: 0 3px 12px rgba(27,94,32,0.14); }
.rx-tab.tab-external.active { background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%); color: #8a6d1a; box-shadow: 0 3px 12px rgba(201,162,39,0.28); }
.rx-tab.tab-external:hover { color: #8a6d1a; }
.rx-tab .tab-icon { font-size: 17px; }
.rx-tab .tab-count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 11px; background: #dfece0; color: #1b5e20; font-size: 11.5px; font-weight: 800; font-family: 'Zilla Slab', serif; transition: 0.2s; }
.rx-tab.active .tab-count { background: #1b5e20; color: #fff; }
.rx-tab.tab-external.active .tab-count { background: #c9a227; color: #fff; }
.rx-tab.tab-external .tab-count { background: #ffe082; color: #8a6d1a; }
.tab-panel { display: none; animation: tabFade 0.28s ease-out; }
.tab-panel.active { display: block; }
@keyframes tabFade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.tab-info { display: flex; align-items: center; gap: 10px; padding: 11px 16px; border-radius: 10px; font-size: 13.5px; margin-bottom: 14px; line-height: 1.5; }
.tab-info.inhouse { background: #e8f5e9; border-left: 4px solid #4caf50; color: #1b5e20; }
.tab-info.external { background: #fff8e1; border-left: 4px solid #c9a227; color: #8a6d1a; }
.tab-info .ico { font-size: 18px; }
.tab-add-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; border: 1.5px dashed #c8e6c9; background: #f8fbf7; color: #1b5e20; font-family: 'Karla', sans-serif; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; margin-top: 4px; }
.tab-add-btn:hover { background: #e8f5e9; border-style: solid; border-color: #4caf50; transform: translateY(-1px); }
.tab-add-btn.external { border-color: #ffe082; color: #8a6d1a; background: #fffdf5; }
.tab-add-btn.external:hover { background: #fff8e1; border-color: #c9a227; }
.remove-row-btn { background: #f8d7d7; color: #c62828; border: 1px solid #ef9a9a; border-radius: 8px; cursor: pointer; padding: 9px 11px; font-size: 14px; font-weight: 700; transition: 0.15s; line-height: 1; }
.remove-row-btn:hover { background: #c62828; color: #fff; border-color: #c62828; }
button[type="submit"].save-btn { width: 100%; margin-top: 24px; padding: 15px; background: linear-gradient(135deg, #1b5e20 0%, #4caf50 100%); color: #fff; border: none; border-radius: 12px; font-size: 16.5px; font-weight: 700; font-family: 'Karla', sans-serif; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 6px 18px rgba(27,94,32,0.28); letter-spacing: 0.4px; }
button[type="submit"].save-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(27,94,32,0.35); }
</style>
</head>
<body>

<div class="topbar">
    <h2>New Prescription</h2>
    <a href="prescriptions.php">&larr; Back to Prescriptions</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>Prescription Details</h3>
        <form method="POST" id="rxForm">
            <?php if ($from_token_id > 0): ?>
                <input type="hidden" name="from_token_id" value="<?php echo $from_token_id; ?>">
                <div class="info-note" style="background:#e8f5e9;padding:12px 16px;border-radius:8px;border-left:4px solid #4caf50;color:#1b5e20;font-size:14.5px;margin-bottom:14px;">
                    🎫 Creating prescription from <b>Token #<?php echo str_pad($from_token_id, 3, '0', STR_PAD_LEFT); ?></b>.
                </div>
            <?php endif; ?>

            <label>Patient *</label>
            <select name="patient_id" required>
                <option value="">-- Select Patient --</option>
                <?php while ($p = $patients->fetch_assoc()): ?>
                    <option value="<?php echo $p['patient_id']; ?>" <?php echo ($pre_patient_id == $p['patient_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['full_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Doctor *</label>
            <select name="doctor_id" required>
                <option value="">-- Select Doctor --</option>
                <?php while ($d = $doctors->fetch_assoc()): ?>
                    <option value="<?php echo $d['doctor_id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                <?php endwhile; ?>
            </select>

            <label>Prescription Date *</label>
            <input type="date" name="prescription_date" value="<?php echo date('Y-m-d'); ?>" required>

            <label>Notes</label>
            <textarea name="notes" rows="2"></textarea>

            <hr style="margin: 22px 0 4px;">
            <label style="margin-bottom: 10px;">Prescribed Medicines *</label>

            <div class="rx-tabs">
                <button type="button" class="rx-tab tab-inhouse active" data-tab="inhouse">
                    <span class="tab-icon">🏥</span> <span>In-house</span> <span class="tab-count" id="count-inhouse">1</span>
                </button>
                <button type="button" class="rx-tab tab-external" data-tab="external">
                    <span class="tab-icon">📤</span> <span>External</span> <span class="tab-count" id="count-external">0</span>
                </button>
            </div>

            <div class="tab-panel active" id="panel-inhouse">
                <div class="tab-info inhouse">
                    <span class="ico">🏥</span> <span>These medicines will be <b>issued from our hospital pharmacy</b>.</span>
                </div>
                <div id="rows-inhouse">
                    <div class="med-row">
                        <select name="medicine_id[]"><?php echo $medicineOptions; ?></select>
                        <input type="number" name="prescribed_quantity[]" placeholder="Qty" min="1">
                        <input type="text" name="dosage_instructions[]" placeholder="Dosage e.g. 1 tsp twice daily">
                        <input type="hidden" name="dispense_type[]" value="inhouse">
                        <input type="hidden" name="external_note[]" value="">
                        <button type="button" class="remove-row-btn" onclick="removeRow(this,'inhouse')">✕</button>
                    </div>
                </div>
                <button type="button" class="tab-add-btn" onclick="addRow('inhouse')"><span>+</span> Add In-house Medicine</button>
            </div>

            <div class="tab-panel" id="panel-external">
                <div class="tab-info external">
                    <span class="ico">📤</span> <span>These medicines will be printed on a <b>separate slip</b>. You can add a note per medicine below.</span>
                </div>
                <div id="rows-external">
                    <div class="med-row-wrapper">
                        <div class="med-row">
                            <select name="medicine_id[]"><?php echo $medicineOptions; ?></select>
                            <input type="number" name="prescribed_quantity[]" placeholder="Qty" min="1">
                            <input type="text" name="dosage_instructions[]" placeholder="Dosage e.g. 1 tsp twice daily">
                            <input type="hidden" name="dispense_type[]" value="external">
                            <button type="button" class="remove-row-btn" onclick="removeRow(this,'external')">✕</button>
                        </div>
                        <div class="external-note-wrap">
                            <span class="note-icon">📝</span>
                            <input type="text" name="external_note[]" class="external-note-input" placeholder="Note for pharmacy (optional) — e.g. 'Only brand X'">
                        </div>
                    </div>
                </div>
                <button type="button" class="tab-add-btn external" onclick="addRow('external')"><span>+</span> Add External Medicine</button>
            </div>

            <button type="submit" class="save-btn">Save Prescription</button>
            <?php if ($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
        </form>
    </div>
</div>

<script>
const medicineOptionsHtml = `<?php echo addslashes($medicineOptions); ?>`;
document.querySelectorAll('.rx-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        document.querySelectorAll('.rx-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-' + target).classList.add('active');
    });
});
function addRow(type) {
    const container = document.getElementById('rows-' + type);
    if (type === 'external') {
        const wrapper = document.createElement('div');
        wrapper.className = 'med-row-wrapper';
        wrapper.innerHTML = `
            <div class="med-row">
                <select name="medicine_id[]">${medicineOptionsHtml}</select>
                <input type="number" name="prescribed_quantity[]" placeholder="Qty" min="1">
                <input type="text" name="dosage_instructions[]" placeholder="Dosage e.g. 1 tsp twice daily">
                <input type="hidden" name="dispense_type[]" value="external">
                <button type="button" class="remove-row-btn" onclick="removeRow(this,'external')">✕</button>
            </div>
            <div class="external-note-wrap">
                <span class="note-icon">📝</span>
                <input type="text" name="external_note[]" class="external-note-input" placeholder="Note for pharmacy (optional)">
            </div>
        `;
        container.appendChild(wrapper);
    } else {
        const row = document.createElement('div');
        row.className = 'med-row';
        row.innerHTML = `
            <select name="medicine_id[]">${medicineOptionsHtml}</select>
            <input type="number" name="prescribed_quantity[]" placeholder="Qty" min="1">
            <input type="text" name="dosage_instructions[]" placeholder="Dosage e.g. 1 tsp twice daily">
            <input type="hidden" name="dispense_type[]" value="inhouse">
            <input type="hidden" name="external_note[]" value="">
            <button type="button" class="remove-row-btn" onclick="removeRow(this,'inhouse')">✕</button>
        `;
        container.appendChild(row);
    }
    updateCounts();
}
function removeRow(btn, type) {
    const container = document.getElementById('rows-' + type);
    const target = (type === 'external') ? btn.closest('.med-row-wrapper') : btn.closest('.med-row');
    if (!target) return;
    const totalRows = container.querySelectorAll(type === 'external' ? '.med-row-wrapper' : '.med-row').length;
    if (totalRows > 1) {
        target.remove();
    } else {
        target.querySelectorAll('select').forEach(s => s.value = '');
        target.querySelectorAll('input[type="text"]').forEach(i => { if (i.name === 'dosage_instructions[]' || i.name === 'external_note[]') i.value = ''; });
        target.querySelectorAll('input[type="number"]').forEach(i => i.value = '');
    }
    updateCounts();
}
function updateCounts() {
    ['inhouse', 'external'].forEach(type => {
        const container = document.getElementById('rows-' + type);
        const selector = (type === 'external') ? '.med-row-wrapper' : '.med-row';
        let count = 0;
        container.querySelectorAll(selector).forEach(r => {
            const sel = r.querySelector('select[name="medicine_id[]"]');
            if (sel && sel.value !== '') count++;
        });
        document.getElementById('count-' + type).textContent = count;
    });
}
document.addEventListener('change', (e) => { if (e.target.name === 'medicine_id[]') updateCounts(); });
updateCounts();
</script>
</body>
</html>