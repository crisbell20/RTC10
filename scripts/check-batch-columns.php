<?php
require_once __DIR__ . '/../api/config/connection-pdo.php';

echo "Checking tbl_batch columns:\n\n";

$result = $pdo->query('SHOW COLUMNS FROM tbl_batch');
while ($row = $result->fetch()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
