<?php
include 'config.php';

$accounts = [
    ['admin',      'admin123',     'System Administrator', 'admin',      '0110000001'],
    ['doctor',     'doctor123',    'Default Doctor',       'doctor',     '0110000002'],
    ['pharmacist', 'pharma123',    'Default Pharmacist',   'pharmacist', '0110000003'],
    ['reception',  'reception123', 'Reception Desk',       'reception',  '0110000004'],
];

echo "<pre style='font-family:monospace;padding:20px;'>";
foreach ($accounts as $acc) {
    list($u, $p, $fn, $r, $c) = $acc;
    $hashed = password_hash($p, PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->bind_param("s", $u);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE users SET password=?, full_name=?, role=?, contact_number=? WHERE username=?");
        $stmt->bind_param("sssss", $hashed, $fn, $r, $c, $u);
        $stmt->execute();
        echo "Updated: $u\n";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, contact_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $u, $hashed, $fn, $r, $c);
        $stmt->execute();
        echo "Created: $u\n";
    }
}
echo "\n========================================\n";
echo "Login Credentials:\n";
echo "  admin      / admin123\n";
echo "  doctor     / doctor123\n";
echo "  pharmacist / pharma123\n";
echo "  reception  / reception123\n";
echo "========================================\n";
echo "\n>>> DELETE this file after use! <<<";
echo "</pre>";
?>