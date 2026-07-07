<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../api/config/connection-pdo.php';
require_once __DIR__ . '/../../api/includes/exam-code-utils.php';

echo "=== EXAM CODE MIGRATION ===\n\n";

try {
    $statements = [
        "ALTER TABLE `tbl_exam` ADD COLUMN `Exam_Code` varchar(12) DEFAULT NULL",
        "ALTER TABLE `tbl_exam` ADD COLUMN `Exam_Code_Generated_At` datetime DEFAULT NULL",
        "ALTER TABLE `tbl_exam` ADD COLUMN `Exam_Code_Reset_Count` int(11) NOT NULL DEFAULT 0",
        "ALTER TABLE `tbl_exam` ADD COLUMN `Is_Archived` tinyint(1) NOT NULL DEFAULT 0",
        "ALTER TABLE `tbl_exam` ADD COLUMN `Archived_At` datetime DEFAULT NULL",
        "CREATE TABLE IF NOT EXISTS `tbl_exam_code_attempt` (
            `Attempt_ID` int(11) NOT NULL AUTO_INCREMENT,
            `Exam_ID` int(11) NOT NULL,
            `User_ID` int(11) NOT NULL,
            `IP_Address` varchar(45) DEFAULT NULL,
            `Success` tinyint(1) NOT NULL DEFAULT 0,
            `Attempted_At` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`Attempt_ID`),
            KEY `idx_attempt_lookup` (`Exam_ID`,`User_ID`,`Attempted_At`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($statements as $sql) {
        try {
            $pdo->exec($sql);
            echo "OK: " . substr($sql, 0, 80) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                echo "SKIP (already applied): " . substr($sql, 0, 60) . "...\n";
                continue;
            }
            throw $e;
        }
    }

    $missing = $pdo->query("SELECT Exam_ID FROM tbl_exam WHERE Exam_Code IS NULL OR Exam_Code = ''")->fetchAll(PDO::FETCH_ASSOC);
    $backfilled = 0;
    foreach ($missing as $row) {
        $code = generateUniqueExamCode($pdo);
        $stmt = $pdo->prepare('UPDATE tbl_exam SET Exam_Code = ?, Exam_Code_Generated_At = NOW() WHERE Exam_ID = ?');
        $stmt->execute([$code, $row['Exam_ID']]);
        $backfilled++;
    }

    echo "\nMigration completed.\n";
    echo "  Exams backfilled with codes: {$backfilled}\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
