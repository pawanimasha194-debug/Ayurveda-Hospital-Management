<?php
include 'config.php';

$error = "";
$selected_role = isset($_POST['role']) ? $_POST['role'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($role == '') {
        $error = "Please select a role first.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password, full_name, role FROM users WHERE username = ? AND role = ?");
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                if ($user['role'] === 'reception') {
                    header("Location: reception_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No " . ucfirst($role) . " account found with that username.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Gampaha Beheth Sappuwa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
    /* ============================================================
       LOGIN PAGE SPECIFIC STYLES (With Background Photo)
       ============================================================ */
    * { box-sizing: border-box; }

    body.login-page {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: 'Karla', sans-serif;
        /* මෙතන තමයි අර පින්තූරේ ආයේ දාලා තියෙන්නේ */
        background: linear-gradient(180deg, rgba(18, 61, 22, 0.6) 0%, rgba(27, 94, 32, 0.75) 100%),
                    url('images/login-bg.jpg') center 25% / cover no-repeat fixed;
        position: relative;
        overflow: hidden;
    }

    .login-box-wrapper {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 520px;
        padding: 20px;
    }

    /* Glassmorphism Card */
    .login-box {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        padding: 45px 40px;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        text-align: center;
    }

    .login-logo {
        width: 70px; height: 70px; border-radius: 50%;
        background: linear-gradient(135deg, #1b5e20, #4caf50);
        display: flex; align-items: center; justify-content: center;
        font-size: 32px; margin: -80px auto 15px;
        box-shadow: 0 8px 20px rgba(27,94,32,0.4);
        border: 4px solid #fff;
    }

    .login-hospital-name {
        color: #1b5e20; font-family: 'Zilla Slab', serif; font-weight: 700;
        font-size: 24px; margin-bottom: 4px;
    }

    .login-box h2 {
        color: #5f6f68; font-size: 15px; font-weight: 500; font-family: 'Karla', sans-serif;
        margin: 0 0 25px;
    }

    /* --- Role Selection Cards --- */
    .role-label {
        color: #1b5e20; font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase;
        margin-bottom: 12px; text-align: left; font-weight: 700;
    }

    .role-grid {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 25px;
    }

    .role-card {
        background: #f8fbf7; border: 1.5px solid #dfece0; border-radius: 12px;
        padding: 12px 5px; text-align: center; cursor: pointer; transition: all 0.2s ease;
        color: #5f6f68;
    }
    
    .role-card:hover { background: #fff; border-color: #c8e6c9; transform: translateY(-2px); }
    
    .role-card.selected {
        background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
        border-color: #1b5e20; color: #fff;
        box-shadow: 0 8px 20px rgba(27,94,32,0.3);
        transform: translateY(-3px) scale(1.02);
    }

    .role-icon { font-size: 22px; display: block; margin-bottom: 5px; }
    .role-name { font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px; }

    /* --- Form Inputs --- */
    .login-box input[type="text"],
    .login-box input[type="password"] {
        width: 100%;
        padding: 14px 16px;
        font-size: 15px;
        font-family: 'Karla', sans-serif;
        color: #1b5e20;
        background: #ffffff;
        border: 1.5px solid #dfece0;
        border-radius: 10px;
        outline: none;
        transition: all 0.2s ease;
        margin-bottom: 12px;
    }

    .login-box input:focus {
        border-color: #4caf50;
        box-shadow: 0 0 0 4px rgba(76,175,80,0.2);
        transform: translateY(-1px);
    }

    /* --- Button --- */
    .login-box button {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #1b5e20 0%, #4caf50 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 700;
        font-family: 'Karla', sans-serif;
        cursor: pointer;
        margin-top: 8px;
        box-shadow: 0 6px 18px rgba(27,94,32,0.3);
        transition: all 0.2s ease;
    }

    .login-box button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(27,94,32,0.4);
        background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%);
    }

    /* --- Error Message --- */
    .error-msg {
        background: #ffebee;
        color: #c62828;
        padding: 10px;
        border-radius: 8px;
        font-size: 13.5px;
        margin-top: 15px;
        border: 1px solid #ef9a9a;
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
</style>
</head>
<body class="login-page">

<div class="login-box-wrapper">
    <div class="login-box">
        <div class="login-logo">🌿</div>
        <div class="login-hospital-name">ගම්පහ බෙහෙත් ශාලාව</div>
        <h2>Ayurveda Hospital Management System</h2>

        <form method="POST" action="login.php" id="loginForm" autocomplete="off">
            <div class="role-label">Select Your Role</div>
            <div class="role-grid">
                <div class="role-card" data-role="admin">
                    <div class="role-icon">👤</div>
                    <div class="role-name">Admin</div>
                </div>
                <div class="role-card" data-role="doctor">
                    <div class="role-icon">🩺</div>
                    <div class="role-name">Doctor</div>
                </div>
                <div class="role-card" data-role="pharmacist">
                    <div class="role-icon">💊</div>
                    <div class="role-name">Pharmacist</div>
                </div>
                <div class="role-card" data-role="reception">
                    <div class="role-icon">🛎️</div>
                    <div class="role-name">Reception</div>
                </div>
            </div>
            
            <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($selected_role); ?>">

            <input type="text" name="username" id="usernameInput" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            
            <button type="submit">Login</button>
        </form>

        <?php if ($error): ?>
            <div class="error-msg">
                <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const roleCards = document.querySelectorAll('.role-card');
    const roleInput = document.getElementById('roleInput');
    const usernameInput = document.getElementById('usernameInput');
    const defaultUsernames = ['admin', 'doctor', 'pharmacist', 'reception'];

    if (roleInput.value) {
        roleCards.forEach(c => {
            if (c.dataset.role === roleInput.value) c.classList.add('selected');
        });
    }

    roleCards.forEach(card => {
        card.addEventListener('click', () => {
            roleCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const role = card.dataset.role;
            roleInput.value = role;

            if (!usernameInput.value || defaultUsernames.includes(usernameInput.value)) {
                usernameInput.value = role;
            }
            usernameInput.placeholder = `Enter ${role} username`;
        });
    });

    document.getElementById('loginForm').addEventListener('submit', (e) => {
        if (!roleInput.value) {
            e.preventDefault();
            alert('Please select a role first.');
        }
    });
</script>

</body>
</html>