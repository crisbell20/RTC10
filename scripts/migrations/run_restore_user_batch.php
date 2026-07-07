<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../api/config/connection-pdo.php';

echo "=== RESTORING tbl_user_batch MIGRATION ===\n\n";

try {
    $sql = file_get_contents(__DIR__ . '/restore_user_batch.sql');
    if ($sql === false) {
        throw new Exception('Could not read restore_user_batch.sql');
    }

    $pdo->exec($sql);

    $count = $pdo->query('SELECT COUNT(*) FROM tbl_user_batch')->fetchColumn();
    $remaining = $pdo->query('SELECT COUNT(*) FROM tbl_batch WHERE User_ID IS NOT NULL')->fetchColumn();

    echo "Migration completed successfully.\n";
    echo "  tbl_user_batch rows: {$count}\n";
    echo "  tbl_batch rows still using User_ID for enrollment: {$remaining}\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
