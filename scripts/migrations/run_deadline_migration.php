<?php
/**
 * Run the deadline migration
 */

require_once __DIR__ . '/../../api/config/connection-pdo.php';

try {
    echo "Adding Deadline column to tbl_exam...\n";
    
    // Add the Deadline column
    $sql = "ALTER TABLE `tbl_exam` 
            ADD COLUMN `Deadline` datetime DEFAULT NULL 
            COMMENT 'Deadline to start the exam' 
            AFTER `Schedule_Date`";
    
    $pdo->exec($sql);
    echo "✓ Deadline column added successfully\n\n";
    
    // Update existing exams
    echo "Updating existing exams with default deadlines...\n";
    $sql = "UPDATE `tbl_exam` 
            SET `Deadline` = DATE_ADD(`Schedule_Date`, INTERVAL 24 HOUR) 
            WHERE `Schedule_Date` IS NOT NULL AND `Deadline` IS NULL";
    
    $result = $pdo->exec($sql);
    echo "✓ Updated $result exam(s) with default deadlines\n\n";
    
    echo "Migration completed successfully!\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'Deadline' already exists. Migration skipped.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
