<?php
/**
 * Auto-close exams that are past their deadline
 * Run this script periodically (e.g., every 5 minutes via cron)
 * 
 * Cron example: */5 * * * * php /path/to/RTC10/scripts/auto-close-exams.php
 */

require_once __DIR__ . '/../api/config/connection-pdo.php';

try {
    // Find exams that should be closed
    // Status = Published AND Deadline + 30 minutes has passed
    $sql = "
        UPDATE tbl_exam 
        SET Status = 'Closed' 
        WHERE Status = 'Published' 
        AND Deadline IS NOT NULL 
        AND DATE_ADD(Deadline, INTERVAL 30 MINUTE) < NOW()
    ";
    
    $result = $pdo->exec($sql);
    
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] Auto-closed $result exam(s)\n";
    
    // Log the closed exams
    if ($result > 0) {
        error_log("Auto-closed $result exam(s) at $timestamp");
    }
    
} catch (PDOException $e) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] Error: " . $e->getMessage() . "\n";
    error_log("Auto-close exams error: " . $e->getMessage());
}
