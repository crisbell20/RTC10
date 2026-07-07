<?php
// DEPRECATED: This file is kept for backward compatibility only
// All functionality has been merged into batches.php
// Please update your code to use batches.php with the new action names:
// - 'assign' -> 'assign_user'
// - 'remove' -> 'remove_user'
// - 'update_status' -> 'update_user_status'

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['Admin', 'CCMD'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/connection-pdo.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? [];
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? '');

// Map old actions to new actions for backward compatibility
$actionMap = [
    'assign' => 'assign_user',
    'remove' => 'remove_user',
    'update_status' => 'update_user_status'
];

if (isset($actionMap[$action])) {
    $action = $actionMap[$action];
    if (isset($input['action'])) {
        $input['action'] = $action;
    }
}

// Forward all requests to batches.php
$_GET['action'] = $action;
$_POST['action'] = $action;

// Include the merged batches.php file
require __DIR__ . '/batches.php';
?>
