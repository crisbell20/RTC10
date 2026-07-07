<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../api/config/connection-pdo.php';

echo "=== VERIFYING DATABASE MERGE ===\n\n";

$allGood = true;

// 1. Check tbl_batch has new columns
echo "1. Checking tbl_batch structure...\n";
$columns = $pdo->query("SHOW COLUMNS FROM tbl_batch")->fetchAll(PDO::FETCH_ASSOC);
$columnNames = array_column($columns, 'Field');

$requiredColumns = ['User_ID', 'Status', 'Date_Enrolled'];
foreach ($requiredColumns as $col) {
    if (in_array($col, $columnNames)) {
        echo "   ✓ Column '$col' exists\n";
    } else {
        echo "   ✗ Column '$col' is MISSING!\n";
        $allGood = false;
    }
}

// 2. Check tbl_user_batch is dropped
echo "\n2. Checking if tbl_user_batch is dropped...\n";
try {
    $pdo->query("SELECT 1 FROM tbl_user_batch LIMIT 1");
    echo "   ✗ tbl_user_batch still exists!\n";
    $allGood = false;
} catch (Exception $e) {
    echo "   ✓ tbl_user_batch has been dropped\n";
}

// 3. Check foreign key exists
echo "\n3. Checking foreign key constraint...\n";
$fks = $pdo->query("
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'tbl_batch' 
    AND COLUMN_NAME = 'User_ID'
    AND REFERENCED_TABLE_NAME = 'tbl_user'
")->fetchAll(PDO::FETCH_ASSOC);

if (count($fks) > 0) {
    echo "   ✓ Foreign key constraint exists: {$fks[0]['CONSTRAINT_NAME']}\n";
} else {
    echo "   ✗ Foreign key constraint is MISSING!\n";
    $allGood = false;
}

// 4. Check data integrity
echo "\n4. Checking data integrity...\n";
$batchCount = $pdo->query("SELECT COUNT(*) as cnt FROM tbl_batch")->fetch();
echo "   Total batches: {$batchCount['cnt']}\n";

$withUsers = $pdo->query("SELECT COUNT(*) as cnt FROM tbl_batch WHERE User_ID IS NOT NULL")->fetch();
echo "   Batches with users: {$withUsers['cnt']}\n";

$withoutUsers = $pdo->query("SELECT COUNT(*) as cnt FROM tbl_batch WHERE User_ID IS NULL")->fetch();
echo "   Batches without users: {$withoutUsers['cnt']}\n";

// 5. Test a sample query
echo "\n5. Testing sample query (batches by user)...\n";
try {
    $stmt = $pdo->prepare("
        SELECT b.Batch_ID, b.Batch_Name, b.Status
        FROM tbl_batch b
        WHERE b.User_ID = ?
    ");
    $stmt->execute([3]); // Test with user ID 3
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ Query executed successfully (found " . count($result) . " batches)\n";
} catch (Exception $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . "\n";
    $allGood = false;
}

// 6. Test exam filtering query
echo "\n6. Testing exam filtering query...\n";
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt
        FROM tbl_exam e
        INNER JOIN tbl_exam_batch eb ON e.Exam_ID = eb.Exam_ID
        INNER JOIN tbl_batch b ON eb.Batch_ID = b.Batch_ID
        WHERE b.User_ID = ? AND b.Status = 'Active'
    ");
    $stmt->execute([3]);
    $result = $stmt->fetch();
    echo "   ✓ Exam filtering query works (found {$result['cnt']} exams)\n";
} catch (Exception $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . "\n";
    $allGood = false;
}

echo "\n=== VERIFICATION " . ($allGood ? "PASSED" : "FAILED") . " ===\n";

if ($allGood) {
    echo "\n✓ All checks passed! The database merge is complete and working correctly.\n";
} else {
    echo "\n✗ Some checks failed. Please review the errors above.\n";
}
?>
