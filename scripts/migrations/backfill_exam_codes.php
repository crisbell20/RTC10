<?php
require_once __DIR__ . '/../../api/includes/exam-code-utils.php';

$pdo = new PDO('mysql:host=localhost;dbname=db_rtc10;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$rows = $pdo->query("SELECT Exam_ID FROM tbl_exam WHERE Exam_Code IS NULL OR Exam_Code = ''")->fetchAll(PDO::FETCH_ASSOC);
$n = 0;
foreach ($rows as $row) {
    $code = generateUniqueExamCode($pdo);
    $stmt = $pdo->prepare('UPDATE tbl_exam SET Exam_Code = ?, Exam_Code_Generated_At = NOW() WHERE Exam_ID = ?');
    $stmt->execute([$code, $row['Exam_ID']]);
    $n++;
}
echo "Backfilled {$n} exam(s)\n";
