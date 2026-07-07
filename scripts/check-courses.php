<?php
require_once __DIR__ . '/../api/config/connection-pdo.php';

echo "Checking courses in database:\n\n";

$stmt = $pdo->query('SELECT Course_ID, Course_Name FROM tbl_course');
$courses = $stmt->fetchAll();

if (count($courses) === 0) {
    echo "No courses found!\n";
} else {
    foreach ($courses as $course) {
        echo "ID: " . $course['Course_ID'] . " - " . $course['Course_Name'] . "\n";
    }
}
?>
