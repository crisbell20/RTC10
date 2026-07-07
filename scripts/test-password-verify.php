<?php
require_once __DIR__ . '/../api/config/connection-pdo.php';

$email = 'admin@rtc.com';
$password = 'password123';

$query = "SELECT u.*, r.Role_Name FROM tbl_user u 
          LEFT JOIN tbl_role r ON u.Role_ID = r.Role_ID 
          WHERE u.Email = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== User Data ===\n";
echo "Columns: " . implode(', ', array_keys($user)) . "\n\n";

echo "Password_Hash (capital): " . (isset($user['Password_Hash']) ? 'EXISTS' : 'NULL') . "\n";
echo "password_hash (lowercase): " . (isset($user['password_hash']) ? 'EXISTS' : 'NULL') . "\n\n";

$passwordHash = $user['Password_Hash'] ?? $user['password_hash'] ?? '';
echo "Hash value: " . substr($passwordHash, 0, 20) . "...\n";
echo "Hash length: " . strlen($passwordHash) . "\n\n";

echo "Testing password_verify:\n";
$result = password_verify($password, $passwordHash);
echo "Result: " . ($result ? 'SUCCESS' : 'FAIL') . "\n";

if (!$result) {
    echo "\nTrying to generate new hash:\n";
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    echo "New hash: " . substr($newHash, 0, 20) . "...\n";
    echo "Verify new hash: " . (password_verify($password, $newHash) ? 'SUCCESS' : 'FAIL') . "\n";
}
?>
