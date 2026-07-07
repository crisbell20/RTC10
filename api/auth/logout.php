<?php
session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// If accessed directly via browser (GET), redirect to login page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: ../../login.php');
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
?>
