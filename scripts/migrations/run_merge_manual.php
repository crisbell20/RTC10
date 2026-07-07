<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../api/config/connection-pdo.php';

echo "=== RUNNING USER-BATCH MERGE MIGRATION (MANUAL) ===\n\n";

try {
    $pdo->beginTransaction();
    
    // Step 1: Add columns to tbl_batch
    echo "Step 1: Adding columns to tbl_batch...\n";
    try {
        $pdo->exec("ALTER TABLE `tbl_batch` 
            ADD COLUMN `User_ID` int(11) DEFAULT NULL AFTER `Section_ID`,
            ADD COLUMN `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' AFTER `Date_Ended`,
            ADD COLUMN `Date_Enrolled` datetime DEFAULT CURRENT_TIMESTAMP AFTER `Status`");
        echo "   ✓ Columns added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   (columns already exist, skipping)\n";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Add foreign key
    echo "\nStep 2: Adding foreign key constraint...\n";
    try {
        $pdo->exec("ALTER TABLE `tbl_batch`
            ADD CONSTRAINT `fk_batch_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user`(`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE");
        echo "   ✓ Foreign key added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "   (foreign key already exists, skipping)\n";
        } else {
            throw $e;
        }
    }
    
    // Step 3: Migrate data
    echo "\nStep 3: Migrating data from tbl_user_batch...\n";
    $result = $pdo->exec("UPDATE `tbl_batch` b
        INNER JOIN (
            SELECT Batch_ID, MIN(User_ID) as User_ID, MIN(Date_Enrolled) as Date_Enrolled, Status
            FROM `tbl_user_batch`
            GROUP BY Batch_ID
        ) ub ON b.Batch_ID = ub.Batch_ID
        SET b.User_ID = ub.User_ID,
            b.Date_Enrolled = ub.Date_Enrolled,
            b.Status = ub.Status");
    echo "   ✓ Migrated $result batch(es)\n";
    
    // Step 4: Drop tbl_user_batch
    echo "\nStep 4: Dropping tbl_user_batch table...\n";
    $pdo->exec("DROP TABLE IF EXISTS `tbl_user_batch`");
    echo "   ✓ Table dropped\n";
    
    // Step 5: Add index
    echo "\nStep 5: Adding index...\n";
    try {
        $pdo->exec("ALTER TABLE `tbl_batch` ADD INDEX `idx_batch_user` (`User_ID`)");
        echo "   ✓ Index added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "   (index already exists, skipping)\n";
        } else {
            throw $e;
        }
    }
    
    $pdo->commit();
    
    echo "\n✓ Migration completed successfully!\n\n";
    
    // Verify
    echo "=== POST-MIGRATION VERIFICATION ===\n\n";
    
    echo "1. New tbl_batch structure:\n";
    $columns = $pdo->query("SHOW COLUMNS FROM tbl_batch")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n2. Migrated batch data:\n";
    $batches = $pdo->query("
        SELECT b.Batch_ID, b.Batch_Name, u.Fullname as Owner, b.Status
        FROM tbl_batch b
        LEFT JOIN tbl_user u ON b.User_ID = u.User_ID
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($batches as $batch) {
        $owner = $batch['Owner'] ?? 'No owner';
        echo "   - {$batch['Batch_Name']}: {$owner} ({$batch['Status']})\n";
    }
    
    echo "\n3. Checking if tbl_user_batch still exists:\n";
    try {
        $pdo->query("SELECT 1 FROM tbl_user_batch LIMIT 1");
        echo "   ⚠️  WARNING: tbl_user_batch still exists!\n";
    } catch (Exception $e) {
        echo "   ✓ tbl_user_batch has been dropped\n";
    }
    
    echo "\n=== MIGRATION COMPLETE ===\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
