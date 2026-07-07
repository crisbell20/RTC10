<?php
header('Content-Type: application/json');
session_start();

// Require admin role
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/connection-pdo.php';

if (!isset($pdo)) {
    if (isset($conn) && $conn instanceof PDO) {
        $pdo = $conn;
    } elseif (isset($dbh) && $dbh instanceof PDO) {
        $pdo = $dbh;
    } elseif (isset($db) && $db instanceof PDO) {
        $pdo = $db;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection unavailable']);
        exit;
    }
}

try {
    $stats = [];

    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM tbl_user");
    $stats['total_users'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total courses (treated as active courses)
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM tbl_course");
    $stats['total_courses'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Users by role
    $stmt = $pdo->prepare("
        SELECT r.Role_Name, COUNT(u.User_ID) AS count
        FROM tbl_role r
        LEFT JOIN tbl_user u ON u.Role_ID = r.Role_ID
        GROUP BY r.Role_ID, r.Role_Name
    ");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['by_role'][$row['Role_Name']] = (int) $row['count'];
    }

    echo json_encode(['success' => true, 'stats' => $stats]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
