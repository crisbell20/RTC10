<?php
/**
 * Test login directly
 */

require_once __DIR__ . '/../api/config/connection-pdo.php';

$email = 'admin@rtc.com';
$password = 'password123';

echo "Testing login for: $email\n";
echo "Password: $password\n\n";

// Query the user
$query = "SELECT u.*, r.Role_Name FROM tbl_user u 
          LEFT JOIN tbl_role r ON u.Role_ID = r.Role_ID 
          WHERE u.Email = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "✗ User not found!\n";
    exit;
}

echo "✓ User found:\n";
echo "  - User ID: {$user['User_ID']}\n";
echo "  - Name: {$user['Fullname']}\n";
echo "  - Email: {$user['Email']}\n";
echo "  - Role: {$user['Role_Name']}\n";
echo "  - Status: {$user['Status']}\n";
echo "  - Password Hash: " . substr($user['Password_Hash'], 0, 20) . "...\n\n";

// Test password
$isValid = password_verify($password, $user['Password_Hash']);

if ($isValid) {
    echo "✓ Password verification: SUCCESS\n";
    echo "\nLogin should work with:\n";
    echo "Email: admin@rtc.com\n";
    echo "Password: password123\n";
} else {
    echo "✗ Password verification: FAILED\n";
    echo "\nTrying to fix...\n";
    
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE tbl_user SET Password_Hash = ? WHERE Email = ?");
    $stmt->execute([$newHash, $email]);
    
    echo "✓ Password updated. Try logging in again.\n";
}
