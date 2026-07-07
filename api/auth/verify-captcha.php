<?php
session_start();
header('Content-Type: application/json');

$request_method = $_SERVER['REQUEST_METHOD'];

if ($request_method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check if CAPTCHA session exists
    if (!isset($_SESSION['captcha_user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No pending verification']);
        exit;
    }
    
    // Complete login - set session variables
    $_SESSION['user_id'] = $_SESSION['captcha_user_id'];
    $_SESSION['user_role'] = $_SESSION['captcha_role'];
    $_SESSION['user_name'] = $_SESSION['captcha_name'];
    $_SESSION['user_email'] = $_SESSION['captcha_email'];
    $_SESSION['must_change_password'] = $_SESSION['captcha_must_change_password'] ?? 0;
    
    // Clear CAPTCHA session
    unset($_SESSION['captcha_user_id']);
    unset($_SESSION['captcha_role']);
    unset($_SESSION['captcha_name']);
    unset($_SESSION['captcha_email']);
    unset($_SESSION['captcha_must_change_password']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Verification successful'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
