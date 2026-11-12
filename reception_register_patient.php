<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['reception']);

$error = "";
$success_patient = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name      = trim($_POST['full_name']);
    $dob            = $_POST['dob'] ?: null;
    $gender         = $_POST['gender'];
    $contact_number = trim($_POST['contact_number']);
    $nic_number     = trim($_POST['nic_number']);
    $address        = trim($_POST['address']);
    $patient_type   = $_POST['patient_type'] ?? 'temporary'; // New Field

    if ($full_name == '') {
        $error = "Patient name is required.";
    } elseif ($contact_number == '') {
        $error = "Contact number is required.";
    } else {
        // Duplicate check (same name + phone)
        $chk = $conn->prepare("SELECT patient_id FROM patients WHERE full_name = ? AND contact_number = ? LIMIT 1");
        $chk->bind_param("ss", $full_name, $contact_number);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            $error = "A patient with this name and phone number already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO patients (full_name, dob, gender, contact_number, nic_number, address, patient_type, visit_count, last_visit) VALUES (?, ?, ?, ?, ?, ?, ?, 1, CURDATE())");
            $stmt->bind_param("sssssss", $full_name, $dob, $gender, $contact_number, $nic_number, $address, $patient_type);

            if ($stmt->execute()) {
                $success_patient = [
                    'id'     => $conn->insert_id,
                    'name'   => $full_name,
                    'phone'  => $contact_number,
                    'gender' => $gender,
                    'type'   => $patient_type
                ];
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register Patient - Reception</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
* { box-sizing: border-box; }
body.reception-register-page { background: linear-gradient(180deg, #f5f9f5 0%, #e8f5e9 100%); min-height: 100vh; margin: 0; font-family: 'Karla', sans-serif; }
.rr-topbar { background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%); color: #fff; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #c9a227; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.rr-topbar h2 { margin: 0; color: #fff; font-family: 'Zilla Slab', serif; font-size: 22px; display: flex; align-items: center; gap: 10px; }
.rr-topbar a { color: #fff; text-decoration: none; font-size: 14.5px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.35); padding: 8px 16px; border-radius: 8px; transition: 0.2s; }
.rr-topbar a:hover { background: rgba(255,255,255,0.28); }

.rr-wrap { max-width: 900px; margin: 36px auto; padding: 0 20px; }
.rr-hero { text-align: center; margin-bottom: 28px; }
.rr-hero-icon { width: 74px; height: 74px; border-radius: 50%; background: linear-gradient(135deg, #1b5e20, #4caf50); color: #fff; font-size: 36px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 10px 26px rgba(27,94,32,0.28); margin-bottom: 14px; }
.rr-hero h1 { font-family: 'Zilla Slab', serif; font-size: 28px; color: #1b5e20; margin: 0 0 6px; }
.rr-hero p { color: #5f6f68; font-size: 14.5px; margin: 0; }

.rr-form-card { background: #fff; border-radius: 20px; box-shadow: 0 12px 40px rgba(27,94,32,0.10); padding: 0; overflow: hidden; border: 1px solid #dfece0; }
.rr-section { padding: 26px 34px; border-bottom: 1px solid #eef3ec; transition: 0.25s ease; }
.rr-section:last-of-type { border-bottom: none; }
.rr-section.focused { background: linear-gradient(90deg, #f8fbf7 0%, #ffffff 100%); }

.rr-section-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
.rr-section-icon { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #1b5e20; font-size: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.rr-section-title { font-family: 'Zilla Slab', serif; font-size: 18px; color: #1b5e20; font-weight: 700; margin: 0; }
.rr-section-sub { font-size: 12.5px; color: #5f6f68; margin: 2px 0 0; }

.rr-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 640px) { .rr-grid-2 { grid-template-columns: 1fr; } }
.rr-field { display: flex; flex-direction: column; gap: 6px; }
.rr-field label { font-size: 12.5px; font-weight: 700; color: #1b5e20; text-transform: uppercase; letter-spacing: 0.8px; }
.rr-field label .req { color: #c62828; margin-left: 3px; }

.rr-input, .rr-select, .rr-textarea { width: 100%; padding: 13px 16px; font-size: 15.5px; font-family: 'Karla', sans-serif; color: #1b5e20; background: #f8fbf7; border: 1.5px solid #dfece0; border-radius: 10px; outline: none; transition: all 0.2s ease; }
.rr-input:focus, .rr-select:focus, .rr-textarea:focus { background: #fff; border-color: #4caf50; box-shadow: 0 0 0 4px rgba(76,175,80,0.12); transform: translateY(-1px); }
.rr-input::placeholder, .rr-textarea::placeholder { color: #b0bbb4; }
.rr-textarea { resize: vertical; min-height: 84px; line-height: 1.5; }

/* Type Chips */
.type-chips { display: flex; gap: 10px; flex-wrap: wrap; }
.type-chips input[type="radio"] { display: none; }
.type-chips label { flex: 1; min-width: 140px; padding: 12px 14px; border: 1.5px solid #dfece0; border-radius: 10px; background: #f8fbf7; text-align: center; cursor: pointer; font-size: 14px; font-weight: 600; color: #5f6f68; transition: all 0.18s ease; text-transform: none; letter-spacing: 0; }
.type-chips label:hover { border-color: #c8e6c9; background: #fff; }
.type-chips input[type="radio"]:checked + label { background: linear-gradient(135deg, #1b5e20, #4caf50); border-color: #1b5e20; color: #fff; box-shadow: 0 6px 16px rgba(27,94,32,0.25); transform: translateY(-2px); }
.type-chips input[type="radio"][value="temporary"]:checked + label { background: linear-gradient(135deg, #c9a227, #d4a017); border-color: #b8860b; box-shadow: 0 6px 16px rgba(201,162,39,0.25); }

.rr-submit-area { padding: 24px 34px 30px; background: linear-gradient(180deg, #f8fbf7 0%, #eef7ef 100%); display: flex; gap: 12px; flex-wrap: wrap; justify-content: flex-end; align-items: center; }
.rr-btn-secondary { background: #fff; color: #5f6f68; padding: 13px 22px; border-radius: 10px; border: 1.5px solid #dfece0; text-decoration: none; font-size: 14.5px; font-weight: 600; transition: 0.18s; }
.rr-btn-secondary:hover { background: #fff; color: #1b5e20; border-color: #c8e6c9; }
.rr-btn-submit { background: linear-gradient(135deg, #1b5e20 0%, #4caf50 100%); color: #fff; border: none; padding: 14px 30px; border-radius: 10px; font-size: 15px; font-weight: 700; font-family: 'Karla', sans-serif; cursor: pointer; box-shadow: 0 6px 18px rgba(27,94,32,0.28); transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px; }
.rr-btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(27,94,32,0.35); background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%); }
.rr-error { margin: 20px 34px 0; background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; border-left: 5px solid #c62828; padding: 14px 18px; border-radius: 10px; font-size: 14.5px; display: flex; align-items: center; gap: 10px; }

/* Success Screen */
.rr-success { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 20px; padding: 46px 40px; text-align: center; box-shadow: 0 16px 50px rgba(27,94,32,0.15); border: 1px solid #dfece0; }
.rr-success-check { width: 76px; height: 76px; border-radius: 50%; background: linear-gradient(135deg, #4caf50, #2e7d32); color: #fff; font-size: 42px; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; box-shadow: 0 10px 26px rgba(76,175,80,0.35); }
.rr-success h2 { font-family: 'Zilla Slab', serif; color: #1b5e20; font-size: 24px; margin: 0 0 6px; }
.rr-success p { color: #5f6f68; margin: 0 0 24px; font-size: 14.5px; }
.rr-success-card { background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border-radius: 14px; padding: 20px 22px; text-align: left; margin-bottom: 26px; border: 1px solid #c8e6c9; }
.rr-success-card .row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14.5px; border-bottom: 1px dashed #c8e6c9; }
.rr-success-card .row:last-child { border-bottom: none; }
.rr-success-card .row .k { color: #5f6f68; font-weight: 600; }
.rr-success-card .row .v { color: #1b5e20; font-weight: 700; }
.rr-success-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
</style>
</head>
<body class="reception-register-page">

<div class="rr-topbar">
    <h2>🛎️ Register New Patient</h2>
    <a href="reception_dashboard.php">&larr; Back to Dashboard</a>
</div>

<?php if ($success_patient): ?>
    <div class="rr-success">
        <div class="rr-success-check">✓</div>
        <h2>Patient Registered!</h2>
        <p>The patient has been added to the system successfully.</p>

        <div class="rr-success-card">
            <div class="row">
                <span class="k">Patient ID</span>
                <span class="v">
                    <?php echo ($success_patient['type'] == 'regular' ? 'R-' : 'T-') . str_pad($success_patient['id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
            </div>
            <div class="row"><span class="k">Full Name</span><span class="v"><?php echo htmlspecialchars($success_patient['name']); ?></span></div>
            <div class="row"><span class="k">Phone</span><span class="v"><?php echo htmlspecialchars($success_patient['phone']); ?></span></div>
            <div class="row"><span class="k">Type</span><span class="v" style="text-transform: capitalize;"><?php echo htmlspecialchars($success_patient['type']); ?></span></div>
        </div>

        <div class="rr-success-actions">
            <a href="issue_token.php?patient_id=<?php echo $success_patient['id']; ?>" class="rr-btn-submit" style="text-decoration:none;">🎫 Issue Token Now</a>
            <a href="reception_register_patient.php" class="rr-btn-secondary">+ Register Another</a>
        </div>
    </div>
<?php else: ?>

<div class="rr-wrap">
    <div class="rr-hero">
        <div class="rr-hero-icon">👤</div>
        <h1>New Patient Registration</h1>
        <p>Fill in the patient's details to add them to the hospital records.</p>
    </div>

    <form method="POST" class="rr-form-card" id="regForm">
        <?php if ($error): ?>
            <div class="rr-error"><span>⚠️</span><span><?php echo htmlspecialchars($error); ?></span></div>
        <?php endif; ?>

        <div class="rr-section">
            <div class="rr-section-head">
                <div class="rr-section-icon">👤</div>
                <div>
                    <h3 class="rr-section-title">Personal Information</h3>
                    <p class="rr-section-sub">Basic identity details of the patient</p>
                </div>
            </div>

            <div class="rr-field" style="margin-bottom:16px;">
                <label>Full Name <span class="req">*</span></label>
                <input type="text" name="full_name" class="rr-input" placeholder="e.g. Nimal Perera" required autofocus>
            </div>

            <div class="rr-grid-2">
                <div class="rr-field">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="rr-input">
                </div>
                <div class="rr-field">
                    <label>NIC Number</label>
                    <input type="text" name="nic_number" class="rr-input" placeholder="e.g. 199012345678">
                </div>
            </div>

            <div class="rr-field" style="margin-top:16px;">
                <label>Gender</label>
                <div class="type-chips">
                    <input type="radio" name="gender" id="g-male" value="Male" checked>
                    <label for="g-male">♂ Male</label>
                    <input type="radio" name="gender" id="g-female" value="Female">
                    <label for="g-female">♀ Female</label>
                    <input type="radio" name="gender" id="g-other" value="Other">
                    <label for="g-other">⚧ Other</label>
                </div>
            </div>
        </div>

        <div class="rr-section">
            <div class="rr-section-head">
                <div class="rr-section-icon">📞</div>
                <div>
                    <h3 class="rr-section-title">Contact Information</h3>
                    <p class="rr-section-sub">How we can reach the patient</p>
                </div>
            </div>

            <div class="rr-field" style="margin-bottom:16px;">
                <label>Phone Number <span class="req">*</span></label>
                <input type="text" name="contact_number" class="rr-input" placeholder="e.g. 077 123 4567" required>
            </div>

            <div class="rr-field">
                <label>Home Address</label>
                <textarea name="address" class="rr-textarea" placeholder="Enter the patient's home address..."></textarea>
            </div>
        </div>

        <!-- NEW: Patient Type Section -->
        <div class="rr-section" style="background: #fffbee;">
            <div class="rr-section-head">
                <div class="rr-section-icon" style="background: #fff8e1; color: #8a6d1a;">⭐</div>
                <div>
                    <h3 class="rr-section-title" style="color: #8a6d1a;">Patient Category</h3>
                    <p class="rr-section-sub">Select if this is a regular or one-time patient</p>
                </div>
            </div>
            
            <div class="rr-field">
                <div class="type-chips">
                    <input type="radio" name="patient_type" id="type-temp" value="temporary" checked>
                    <label for="type-temp">🕒 Temporary (One-time)</label>

                    <input type="radio" name="patient_type" id="type-reg" value="regular">
                    <label for="type-reg">⭐ Regular Patient</label>
                </div>
            </div>
        </div>

        <div class="rr-submit-area">
            <a href="reception_dashboard.php" class="rr-btn-secondary">Cancel</a>
            <button type="submit" class="rr-btn-submit"><span>✓</span><span>Register Patient</span></button>
        </div>
    </form>
</div>
<?php endif; ?>
</body>
</html>