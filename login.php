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
<link rel="stylesheet" href="reception-style.css">
</head>
<body class="login-page">

<div class="login-box login-box-wide">
    <div class="login-logo">🌿</div>
    <div class="login-hospital-name">Gampaha Beheth Sappuwa</div>
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
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
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

        // Auto-fill username if empty or still a default
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